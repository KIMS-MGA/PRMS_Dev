<?php

namespace App\Services;

use App\Models\Record;
use App\Models\Module;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Models\RecordApproval;
use App\Models\RecordHistory;
use App\Notifications\DynamicNotification;
use App\Mail\StageNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class RecordApprovalService
{
    /**
     * Submit a record for approval.
     *
     * @param Record $record
     * @param Module $module
     * @param User $user
     * @return void
     */
    public function submitForApproval(Record $record, Module $module, User $user): void
    {
        if (!in_array($record->status, ['Draft', 'Returned'])) {
            throw new \InvalidArgumentException('This record cannot be submitted in its current state.');
        }

        $targetModuleId = $module->source_module_id ?? $module->id;
        $firstStage = WorkflowStage::where('module_id', $targetModuleId)
            ->orderBy('order')
            ->first();

        if (!$firstStage) {
            throw new \RuntimeException('No approval stages are configured for this module.');
        }

        // No cleanup of prior-cycle record_approvals rows is needed on resubmission.
        //
        // Why no cleanup is necessary:
        //
        //   record_approvals serves a dual purpose:
        //     1. Active-cycle queue visibility check  (whereNotExists subquery in ApprovalQueue)
        //     2. Immutable audit trail for the Approval Log (ALL rows, every cycle)
        //
        //   The ApprovalQueue whereNotExists subquery is fully cycle-scoped:
        //     WHERE record_approvals.submission_cycle = records.submission_cycle
        //   This means a row from cycle N (e.g. submission_cycle=1) cannot match
        //   when the record is on cycle N+1 (records.submission_cycle=2).  Prior-cycle
        //   finalized rows — whether 'approved', 'forwarded', or 'returned' — are
        //   invisible to the queue filter and never prevent a reviewer from seeing
        //   a resubmitted proposal.
        //
        //   Deleting prior-cycle rows was the original fix for the queue-visibility bug
        //   (Fix 1), written before cycle-scoping was added to the subquery.  Now that
        //   the queue is cycle-scoped, deletion is both unnecessary for correctness
        //   AND actively harmful: it wipes out the Approval Log history that users
        //   need to see (e.g. a Secretariat APPROVED or reviewer FORWARDED entry from
        //   a prior cycle disappears from the log after resubmission).
        //
        // All finalized action rows ('approved', 'forwarded', 'returned') are now
        // preserved unconditionally as immutable audit history.
        //
        // Cycle-scoped queue example (two-cycle scenario):
        //   Cycle 1: Secretariat approves (submission_cycle=1) → reviewer returns (cycle=1)
        //   → Proponent resubmits → cycle incremented to 2
        //   → NO DELETE — all cycle-1 rows survive as audit history ✓
        //   Cycle 2: new 'submitted' row written with submission_cycle=2
        //   → Queue subquery: submission_cycle = records.submission_cycle = 2
        //       Cycle-1 approved row has submission_cycle=1 → subquery finds NO match
        //       → whereNotExists = TRUE → Secretariat sees the resubmitted proposal ✓
        //   → Approval Log fetches ALL rows → shows full history across all cycles ✓
        $newCycle = $record->submission_cycle + 1;

        $record->update([
            'status'           => 'Submitted',
            'current_stage_id' => $firstStage->id,
            'stage_entered_at' => now(),
            'submission_cycle' => $newCycle,
        ]);

        RecordApproval::create([
            'record_id'        => $record->id,
            'stage_id'         => $firstStage->id,
            'user_id'          => $user->id,
            'action'           => 'submitted',
            'submission_cycle' => $newCycle,
        ]);

        RecordHistory::create([
            'record_id' => $record->id,
            'user_id'   => $user->id,
            'action'    => 'submitted',
        ]);

        $this->notifyStageUsers($record, $module, $firstStage, "A record in {$module->name} requires your approval.");
    }

    /**
     * Approve a record and advance it to the next workflow stage (or Complete).
     *
     * @param Record $record
     * @param Module $module
     * @param User $user
     * @param string|null $comment
     * @return void
     */
    public function approve(Record $record, Module $module, User $user, ?string $comment = null, bool $autoAdvance = false): bool
    {
        $currentStage = $record->currentStage;

        RecordApproval::create([
            'record_id'        => $record->id,
            'stage_id'         => $currentStage?->id,
            'user_id'          => $user->id,
            'action'           => 'approved',
            'comment'          => $comment ?: null,
            'reviewed_at'      => now(),
            'submission_cycle' => $record->submission_cycle,
        ]);

        RecordHistory::create([
            'record_id'    => $record->id,
            'user_id'      => $user->id,
            'action'       => 'approved',
            'changes_json' => $comment ? ['comment' => $comment] : null,
        ]);

        if (!$autoAdvance && $currentStage && $currentStage->stage_type === 'review') {
            if (!$this->allReviewersFinalized($record, $currentStage)) {
                return false;
            }

            // All reviewers have finalized. If ANY reviewer submitted a 'returned'
            // decision the return path wins — an approve decision cannot override a
            // peer's return decision.
            if ($this->anyReviewerReturned($record, $currentStage)) {
                $record->update(['status' => 'Returned', 'current_stage_id' => null]);

                $submitter = User::find($record->created_by);
                $submitter?->notify(new DynamicNotification("Your record in {$module->name} has been returned for revision.", $record->id, $module->slug));

                return true;
            }
        }

        $targetModuleId = $module->source_module_id ?? $module->id;
        $isFinal = !$currentStage || $currentStage->is_final_approval;

        if (!$isFinal) {
            $nextStage = WorkflowStage::where('module_id', $targetModuleId)
                ->where('order', '>', $currentStage->order)
                ->orderBy('order')
                ->first();

            if ($nextStage) {
                $record->update([
                    'status'           => $nextStage->default_status ?? 'Under Review',
                    'current_stage_id' => $nextStage->id,
                    'stage_entered_at' => now()
                ]);
                $this->notifyStageUsers($record, $module, $nextStage, "A record in {$module->name} has advanced and requires your approval.");
                return true;
            }
        }

        $record->update(['status' => 'Completed', 'current_stage_id' => null]);

        // Notify submitter
        $submitter = User::find($record->created_by);
        $submitter?->notify(new DynamicNotification("Your record in {$module->name} has been completed.", $record->id, $module->slug));

        return true;
    }

    /**
     * Forward a record to a branching stage.
     *
     * @param Record $record
     * @param Module $module
     * @param User $user
     * @param int $branchIndex
     * @param string|null $comment
     * @return void
     */
    public function forwardToBranch(Record $record, Module $module, User $user, int $branchIndex, ?string $comment = null, bool $autoAdvance = false): bool
    {
        $currentStage = $record->currentStage;
        $branches = $currentStage?->branches_json ?? [];
        $branch = $branches[$branchIndex] ?? null;

        if (!$branch || empty($branch['stage_id'])) {
            throw new \InvalidArgumentException('Invalid branch.');
        }

        $targetStage = WorkflowStage::find($branch['stage_id']);
        $label = $branch['label'];

        RecordApproval::create([
            'record_id'        => $record->id,
            'stage_id'         => $currentStage?->id,
            'user_id'          => $user->id,
            'action'           => 'forwarded',
            'comment'          => $comment ?: null,
            'reviewed_at'      => now(),
            'submission_cycle' => $record->submission_cycle,
        ]);

        RecordHistory::create([
            'record_id'    => $record->id,
            'user_id'      => $user->id,
            'action'       => 'forwarded',
            'changes_json' => ['path' => $label, 'to_stage' => $targetStage?->name],
        ]);

        if (!$autoAdvance && $currentStage && $currentStage->stage_type === 'review') {
            if (!$this->allReviewersFinalized($record, $currentStage)) {
                return false;
            }

            // All reviewers have finalized. If ANY reviewer submitted a 'returned'
            // decision the return path wins — a forward decision cannot override a
            // peer's return decision.
            if ($this->anyReviewerReturned($record, $currentStage)) {
                $record->update(['status' => 'Returned', 'current_stage_id' => null]);

                $submitter = User::find($record->created_by);
                $submitter?->notify(new DynamicNotification("Your record in {$module->name} has been returned for revision.", $record->id, $module->slug));

                return true;
            }
        }

        $record->update([
            'status'           => $targetStage?->default_status ?? 'Under Review',
            'current_stage_id' => $targetStage?->id,
            'stage_entered_at' => now(),
        ]);

        if ($targetStage) {
            $this->notifyStageUsers($record, $module, $targetStage, "A record in {$module->name} has been forwarded ({$label}) and requires your action.");
        }

        return true;
    }

    /**
     * Return a record for revision.
     *
     * @param Record $record
     * @param Module $module
     * @param User $user
     * @param string $comment
     * @return void
     */
    public function returnForRevision(Record $record, Module $module, User $user, string $comment): void
    {
        RecordApproval::create([
            'record_id'        => $record->id,
            'stage_id'         => $record->current_stage_id,
            'user_id'          => $user->id,
            'action'           => 'returned',
            'comment'          => $comment,
            'submission_cycle' => $record->submission_cycle,
        ]);

        RecordHistory::create([
            'record_id'    => $record->id,
            'user_id'      => $user->id,
            'action'       => 'returned',
            'changes_json' => ['comment' => $comment],
        ]);

        $record->update(['status' => 'Returned', 'current_stage_id' => null]);

        $submitter = User::find($record->created_by);
        $submitter?->notify(new DynamicNotification("Your record in {$module->name} has been returned for revision.", $record->id, $module->slug));
    }

    /**
     * Mark text editor review as done, checking if all required reviewers have completed.
     *
     * @param Record $record
     * @param Module $module
     * @param User $user
     * @param string $fieldSlug
     * @return bool True if all reviewers done and auto-advanced to Next/Complete
     */
    public function markReviewDone(Record $record, Module $module, User $user, string $fieldSlug): bool
    {
        if (!$this->canReview($user, $module->slug)) {
            abort(403);
        }

        // Upsert the review record for this user
        \DB::table('text_editor_reviews')->updateOrInsert(
            [
                'record_id'  => $record->id,
                'field_slug' => $fieldSlug,
                'user_id'    => $user->id,
            ],
            [
                'reviewed_at' => now(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );

        // Count all unique non-super-admin users with review-{slug} permission
        $permission = \Spatie\Permission\Models\Permission::where('name', "review-{$module->slug}")
            ->where('guard_name', 'web')->first();

        $reviewerCount = 0;
        if ($permission) {
            $superAdminRole = Role::where('name', 'super admin')->first();
            $reviewerCount = \DB::table('model_has_roles')
                ->join('role_has_permissions', 'model_has_roles.role_id', '=', 'role_has_permissions.role_id')
                ->where('role_has_permissions.permission_id', $permission->id)
                ->where('model_has_roles.model_type', User::class)
                ->when($superAdminRole, fn($q) => $q->where('model_has_roles.role_id', '!=', $superAdminRole->id))
                ->distinct()
                ->count('model_has_roles.model_id');
        }

        $doneCount = \DB::table('text_editor_reviews')
            ->where('record_id', $record->id)
            ->where('field_slug', $fieldSlug)
            ->count();

        if ($reviewerCount > 0 && $doneCount >= $reviewerCount) {
            // All reviewers done — auto-advance to next stage
            $this->approve($record, $module, $user, null, true);
            return true;
        }

        return false;
    }

    /**
     * Check if a user can edit a specific record.
     * Proponents may only edit records they created; privileged roles have no restriction.
     */
    public function canEditRecord(User $user, string $moduleSlug, ?Record $record = null): bool
    {
        if ($user->hasRole('super admin')) return true;
        if (!$user->can("edit-{$moduleSlug}")) return false;

        // Proponents are restricted to records they own
        if ($user->hasRole('Proponent') && $record && $record->created_by !== $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Authorize that the user has approval permissions for the record's current stage.
     *
     * @param Record|null $record
     * @param User $user
     * @param string $moduleSlug
     * @return void
     */
    public function authorizeApprovalAction(?Record $record, User $user, string $moduleSlug): void
    {
        if (!$record) abort(403);
        if ($user->hasRole('super admin')) return;

        $stage = $record->currentStage;

        if ($stage && $stage->approver_role_id) {
            $role = Role::find($stage->approver_role_id);
            if ($role && $user->hasRole($role->name)) return;
        }

        if ($user->can("approve-{$moduleSlug}")) return;

        abort(403);
    }

    /**
     * Check if a user can review this module's records.
     *
     * @param User $user
     * @param string $moduleSlug
     * @return bool
     */
    public function canReview(User $user, string $moduleSlug): bool
    {
        if ($user->hasRole('super admin')) return false;
        return $user->can("review-{$moduleSlug}");
    }

    /**
     * Check if a user has action rights (approve/reject/forward) in the current stage.
     *
     * @param Record|null $record
     * @param User $user
     * @param string $moduleSlug
     * @return bool
     */
    public function canAct(?Record $record, User $user, string $moduleSlug): bool
    {
        if (!$record || !$record->current_stage_id) return false;
        if ($user->hasRole('super admin')) return true;

        $stage = $record->currentStage;

        if ($stage && $stage->approver_role_id) {
            $role = Role::find($stage->approver_role_id);
            if ($role && $user->hasRole($role->name)) return true;
        }

        return $user->can("approve-{$moduleSlug}");
    }

    /**
     * Returns true when every user in the stage's approver role has a finalized
     * RecordApproval row (reviewed_at IS NOT NULL) for the current record + stage
     * in the CURRENT submission cycle.
     *
     * Scoping to submission_cycle prevents prior-cycle decisions from being counted
     * toward the new cycle's quorum, which would cause the record to skip reviewer
     * confirmation after a return-and-resubmit cycle.
     *
     * Counts any action (approved, forwarded, returned) — the caller is responsible
     * for subsequently deciding what outcome to apply.
     */
    private function allReviewersFinalized(Record $record, WorkflowStage $stage): bool
    {
        if (!$stage->approver_role_id) {
            return true;
        }

        $role = Role::find($stage->approver_role_id);
        if (!$role) {
            return true;
        }

        $reviewerCount = User::role($role->name)->count();
        if ($reviewerCount === 0) {
            return true;
        }

        $finalizedCount = RecordApproval::finalizedReviewerCount(
            $record->id,
            $stage->id,
            $record->submission_cycle
        );

        return $finalizedCount >= $reviewerCount;
    }

    /**
     * Returns true if at least one reviewer has a finalized 'returned' decision
     * for the given record + stage in the CURRENT submission cycle.  Scoping to
     * the current cycle prevents prior-cycle return decisions from bleeding into
     * the new cycle and incorrectly short-circuiting a fresh approve/forward action.
     */
    private function anyReviewerReturned(Record $record, WorkflowStage $stage): bool
    {
        return RecordApproval::where('record_id', $record->id)
            ->where('stage_id', $stage->id)
            ->where('submission_cycle', $record->submission_cycle)
            ->where('action', 'returned')
            ->whereNotNull('reviewed_at')
            ->exists();
    }

    /**
     * Send stage notifications to configured users.
     *
     * @param Record $record
     * @param Module $module
     * @param WorkflowStage $stage
     * @param string $message
     * @return void
     */
    private function notifyStageUsers(Record $record, Module $module, WorkflowStage $stage, string $message): void
    {
        $configured = $stage->notify_on_enter_json ?? [];

        if (empty($configured)) {
            // Legacy fallback: DB-notify approver role users only
            if (!$stage->approver_role_id) return;
            $role = Role::find($stage->approver_role_id);
            if (!$role) return;
            foreach (User::role($role->name)->get() as $approver) {
                $this->sendStageNotification($record, $module, $approver, $message);
            }
            return;
        }

        $recordUrl = $record->id && $module->slug
            ? url("/app/{$module->slug}/{$record->id}")
            : null;

        foreach ($configured as $recipient) {
            $type  = $recipient['type'] ?? '';
            $value = $recipient['value'] ?? '';

            try {
                if ($type === 'submitter') {
                    $this->sendStageNotification($record, $module, User::find($record->created_by), $message);
                } elseif ($type === 'role' && $value) {
                    foreach (User::role($value)->get() as $user) {
                        $this->sendStageNotification($record, $module, $user, $message);
                    }
                } elseif ($type === 'specific_user' && $value) {
                    $this->sendStageNotification($record, $module, User::find($value), $message);
                } elseif ($type === 'specific_email' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    Mail::to($value)->send(new StageNotificationMail($message, 'PRMS Notification', $recordUrl));
                }
            } catch (\Throwable $e) {
                Log::warning('[notifyStageUsers] Failed type=' . $type . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Send database stage notification.
     *
     * @param Record $record
     * @param Module $module
     * @param User|null $user
     * @param string $message
     * @return void
     */
    private function sendStageNotification(Record $record, Module $module, ?User $user, string $message): void
    {
        if (!$user) return;
        $user->notify(new DynamicNotification($message, $record->id, $module->slug, null, true));
    }
}
