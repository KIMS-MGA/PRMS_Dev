<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecordApproval extends Model
{
    protected $fillable = ['record_id', 'stage_id', 'user_id', 'action', 'comment', 'reviewed_at', 'submission_cycle'];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function record()
    {
        return $this->belongsTo(Record::class);
    }

    public function stage()
    {
        return $this->belongsTo(WorkflowStage::class, 'stage_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: approvals where the reviewer has formally concluded their review
     * for a given record + stage (reviewed_at is set).
     */
    public function scopeFinalized($query)
    {
        return $query->whereNotNull('reviewed_at');
    }

    /**
     * Count distinct reviewers who have finalized their review for a record + stage
     * within the given submission cycle.  Scoping to the current cycle prevents
     * prior-cycle decisions from being counted toward the new cycle's quorum.
     */
    public static function finalizedReviewerCount(int $recordId, int $stageId, int $cycle = 1): int
    {
        return static::where('record_id', $recordId)
            ->where('stage_id', $stageId)
            ->where('submission_cycle', $cycle)
            ->whereNotNull('reviewed_at')
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Determine whether a specific user has already finalized their review
     * for a given record + stage within the given submission cycle.
     */
    public static function hasUserFinalized(int $recordId, int $stageId, int $userId, int $cycle = 1): bool
    {
        return static::where('record_id', $recordId)
            ->where('stage_id', $stageId)
            ->where('user_id', $userId)
            ->where('submission_cycle', $cycle)
            ->whereNotNull('reviewed_at')
            ->exists();
    }
}
