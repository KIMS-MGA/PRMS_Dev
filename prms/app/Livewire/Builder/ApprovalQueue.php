<?php

namespace App\Livewire\Builder;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Module;
use App\Models\Record;
use App\Models\WorkflowStage;
use Livewire\Attributes\Layout;

class ApprovalQueue extends Component
{
    use WithPagination;

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super admin');

        if ($isSuperAdmin) {
            $pendingRecords = Record::whereNotNull('current_stage_id')
                ->with(['module', 'currentStage', 'creator'])
                ->latest()
                ->paginate(20);
        } else {
            $userRoleIds = $user->roles->pluck('id');

            $stageIds = WorkflowStage::whereIn('approver_role_id', $userRoleIds)->pluck('id');

            // Modules where user has review-{slug} or approve-{slug} permission
            $permSlugs = $user->permissions
                ->filter(fn($p) => str_starts_with($p->name, 'review-') || str_starts_with($p->name, 'approve-'))
                ->map(fn($p) => preg_replace('/^(review|approve)-/', '', $p->name));

            $permModuleIds = Module::whereIn('slug', $permSlugs)->pluck('id');

            $pendingRecords = Record::whereNotNull('current_stage_id')
                ->where(function ($q) use ($stageIds, $permModuleIds) {
                    $q->whereIn('current_stage_id', $stageIds)
                      ->orWhereIn('module_id', $permModuleIds);
                })
                // Only exclude records where the user has finalized their review
                // for the CURRENT stage specifically (not a past stage).
                ->whereNotExists(function ($q) use ($user) {
                    $q->from('record_approvals')
                      ->whereColumn('record_approvals.record_id', 'records.id')
                      ->whereColumn('record_approvals.stage_id', 'records.current_stage_id')
                      ->where('record_approvals.user_id', $user->id)
                      ->whereNotNull('record_approvals.reviewed_at');
                })
                ->with(['module', 'currentStage', 'creator'])
                ->latest()
                ->paginate(20);
        }

        return view('livewire.builder.approval-queue', compact('pendingRecords'));
    }
}
