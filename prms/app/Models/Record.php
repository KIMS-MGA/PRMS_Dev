<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Record extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (Record $record) {
            if (empty($record->data)) return;

            $attachmentSlugs = $record->module?->fields()
                ->where('type', 'attachment')
                ->pluck('slug')
                ->toArray() ?? [];

            foreach ($attachmentSlugs as $slug) {
                $val = $record->data[$slug] ?? null;
                if (empty($val)) continue;

                if (is_string($val)) {
                    Storage::disk('public')->delete($val);
                } elseif (is_array($val)) {
                    foreach ($val as $version) {
                        if (!empty($version['path'])) {
                            Storage::disk('public')->delete($version['path']);
                        }
                    }
                }
            }
        });
    }

    protected $fillable = ['module_id', 'data', 'status', 'current_stage_id', 'stage_entered_at', 'submission_cycle', 'assigned_to', 'created_by', 'updated_by'];

    protected $attributes = [
        'submission_cycle' => 1,
    ];

    protected $casts = [
        'data' => 'array',
        'stage_entered_at' => 'datetime',
        'submission_cycle' => 'integer',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function comments()
    {
        return $this->hasMany(RecordComment::class);
    }

    public function currentStage()
    {
        return $this->belongsTo(WorkflowStage::class, 'current_stage_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approvals()
    {
        return $this->hasMany(RecordApproval::class);
    }

    public function histories()
    {
        return $this->hasMany(RecordHistory::class);
    }

    /**
     * Build the base query for the Approval Queue for a given user.
     *
     * This is the single source of truth for "which records does this user
     * still need to act on?".  Both the queue list (ApprovalQueue component)
     * and the sidebar badge count use this method so they can never diverge.
     *
     * The whereNotExists subquery is cycle-scoped via
     *   whereColumn('record_approvals.submission_cycle', 'records.submission_cycle')
     * so that finalized rows from a prior submission cycle (e.g. cycle 1 after
     * the proponent resubmits and the record advances to cycle 2) do NOT hide
     * the record from the reviewer's queue in the new cycle.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingForUser($query, \App\Models\User $user)
    {
        if ($user->hasRole('super admin')) {
            return $query->whereNotNull('current_stage_id');
        }

        $userRoleIds = $user->roles->pluck('id');

        $stageIds = \App\Models\WorkflowStage::whereIn('approver_role_id', $userRoleIds)->pluck('id');

        $permSlugs = $user->permissions
            ->filter(fn($p) => str_starts_with($p->name, 'review-') || str_starts_with($p->name, 'approve-'))
            ->map(fn($p) => preg_replace('/^(review|approve)-/', '', $p->name));

        $permModuleIds = \App\Models\Module::whereIn('slug', $permSlugs)->pluck('id');

        return $query
            ->whereNotNull('current_stage_id')
            ->where(function ($q) use ($stageIds, $permModuleIds) {
                $q->whereIn('current_stage_id', $stageIds)
                  ->orWhereIn('module_id', $permModuleIds);
            })
            ->whereNotExists(function ($q) use ($user) {
                $q->from('record_approvals')
                  ->whereColumn('record_approvals.record_id', 'records.id')
                  ->whereColumn('record_approvals.stage_id', 'records.current_stage_id')
                  ->whereColumn('record_approvals.submission_cycle', 'records.submission_cycle')
                  ->where('record_approvals.user_id', $user->id)
                  ->whereNotNull('record_approvals.reviewed_at');
            });
    }
}
