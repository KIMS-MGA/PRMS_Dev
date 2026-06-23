<?php

namespace App\Services;

use App\Enums\ApprovalAction;
use App\Enums\RecordStatus;
use App\Models\Record;
use App\Models\RecordApproval;
use App\Models\RecordHistory;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Notifications\DynamicNotification;
use Illuminate\Support\Collection;

class ApprovalService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    // ── Public Interface ──────────────────────────────────────────────────────

    /**
     * Submit a record for approval.
     *
     * @throws \InvalidArgumentException  When the record is not in Draft or Returned status.
     * @throws \RuntimeException          When no workflow stages are configured for the module.
     */
    public function submit(Record $record, User $user): void
    {
        if (! in_array($record->status, [RecordStatus::Draft->value, RecordStatus::Returned->value], true)) {
            throw new \InvalidArgumentException(
                "Record cannot be submitted from status '{$record->status}'. Expected 'Draft' or 'Returned'."
            );
        }

        $moduleId   = $record->module_id;
        $firstStage = $this->findFirstStage($moduleId);

        if (! $firstStage) {
            throw new \RuntimeException(
                "No workflow stages are configured for module ID {$moduleId}."
            );
        }

        $record->update([
            'status'           => RecordStatus::Submitted->value,
            'current_stage_id' => $firstStage->id,
            'stage_entered_at' => now(),
        ]);

        $this->createApproval([
            'record_id' => $record->id,
            'stage_id'  => $firstStage->id,
            'user_id'   => $user->id,
            'action'    => ApprovalAction::Submitted->value,
        ]);

        $this->createHistory([
            'record_id' => $record->id,
            'user_id'   => $user->id,
            'action'    => ApprovalAction::Submitted->value,
        ]);

        $this->notifyStage(
            $firstStage,
            $record,
            "A record in {$this->moduleName($record)} requires your approval."
        );
    }

    /**
     * Approve the record at its current stage, advancing it or marking it Completed.
     */
    public function approve(Record $record, User $user, string $comment = ''): void
    {
        $currentStage = $record->currentStage;

        $this->createApproval([
            'record_id' => $record->id,
            'stage_id'  => $currentStage?->id,
            'user_id'   => $user->id,
            'action'    => ApprovalAction::Approved->value,
            'comment'   => $comment ?: null,
        ]);

        $this->createHistory([
            'record_id'    => $record->id,
            'user_id'      => $user->id,
            'action'       => ApprovalAction::Approved->value,
            'changes_json' => $comment ? ['comment' => $comment] : null,
        ]);

        $isFinal = ! $currentStage || $currentStage->is_final_approval;

        if (! $isFinal) {
            $nextStage = $this->findNextStage($record->module_id, $currentStage->order);

            if ($nextStage) {
                $record->update([
                    'status'           => $nextStage->default_status ?? RecordStatus::UnderReview->value,
                    'current_stage_id' => $nextStage->id,
                    'stage_entered_at' => now(),
                ]);

                $this->notifyStage(
                    $nextStage,
                    $record,
                    "A record in {$this->moduleName($record)} has advanced and requires your approval."
                );

                return;
            }
        }

        // Final stage (or no next stage) — mark Completed and notify creator.
        $record->update([
            'status'           => RecordStatus::Completed->value,
            'current_stage_id' => null,
        ]);

        $creator = $this->findCreator($record->created_by);
        $creator?->notify(new DynamicNotification(
            message:    "Your record in {$this->moduleName($record)} has been completed.",
            recordId:   $record->id,
            moduleSlug: $record->module?->slug,
            sendEmail:  false,
        ));
    }

    /**
     * Return a record for revision, resetting it to 'Returned' status.
     *
     * @throws \InvalidArgumentException  When the comment is blank.
     */
    public function returnForRevision(Record $record, User $user, string $comment): void
    {
        if (trim($comment) === '') {
            throw new \InvalidArgumentException('A comment is required when returning a record for revision.');
        }

        $this->createApproval([
            'record_id' => $record->id,
            'stage_id'  => $record->current_stage_id,
            'user_id'   => $user->id,
            'action'    => ApprovalAction::Returned->value,
            'comment'   => $comment,
        ]);

        $this->createHistory([
            'record_id'    => $record->id,
            'user_id'      => $user->id,
            'action'       => ApprovalAction::Returned->value,
            'changes_json' => ['comment' => $comment],
        ]);

        $record->update([
            'status'           => RecordStatus::Returned->value,
            'current_stage_id' => null,
            'stage_entered_at' => null,
        ]);

        $creator = $this->findCreator($record->created_by);
        $creator?->notify(new DynamicNotification(
            message:    "Your record in {$this->moduleName($record)} has been returned for revision.",
            recordId:   $record->id,
            moduleSlug: $record->module?->slug,
            sendEmail:  false,
        ));
    }

    /**
     * Forward the record to a specific branch stage.
     *
     * @throws \InvalidArgumentException  When the branch index is invalid or has no stage_id.
     */
    public function forwardToBranch(Record $record, User $user, int $branchIndex, string $comment = ''): void
    {
        $branches = $record->currentStage?->branches_json ?? [];
        $branch   = $branches[$branchIndex] ?? null;

        if ($branch === null || empty($branch['stage_id'])) {
            throw new \InvalidArgumentException(
                "Invalid branch index {$branchIndex} or missing stage_id."
            );
        }

        $targetStage = $this->findStageById((int) $branch['stage_id']);

        if ($targetStage === null) {
            throw new \RuntimeException("Branch target stage ID {$branch['stage_id']} not found.");
        }

        $this->createApproval([
            'record_id' => $record->id,
            'stage_id'  => $record->currentStage?->id,
            'user_id'   => $user->id,
            'action'    => ApprovalAction::Forwarded->value,
            'comment'   => $comment ?: null,
        ]);

        $this->createHistory([
            'record_id'    => $record->id,
            'user_id'      => $user->id,
            'action'       => ApprovalAction::Forwarded->value,
            'changes_json' => [
                'path'     => $branch['label'],
                'to_stage' => $targetStage->name,
            ],
        ]);

        $record->update([
            'status'           => $targetStage->default_status ?? RecordStatus::UnderReview->value,
            'current_stage_id' => $targetStage->id,
            'stage_entered_at' => now(),
        ]);

        $this->notifyStage(
            $targetStage,
            $record,
            "A record in {$this->moduleName($record)} has been forwarded ({$branch['label']}) and requires your action."
        );
    }

    /**
     * Auto-advance a record past its current stage (called by the deadline scheduler).
     */
    public function autoAdvance(Record $record): void
    {
        if (!in_array($record->status, [RecordStatus::Submitted->value, RecordStatus::UnderReview->value])) {
            return;
        }

        $currentStage = $record->currentStage;
        $isFinal      = $currentStage?->is_final_approval ?? true;

        if (! $isFinal && $currentStage) {
            $nextStage = $this->findNextStage($record->module_id, $currentStage->order);

            if ($nextStage) {
                $record->update([
                    'status'           => $nextStage->default_status ?? RecordStatus::UnderReview->value,
                    'current_stage_id' => $nextStage->id,
                    'stage_entered_at' => now(),
                ]);

                $this->createApproval([
                    'record_id' => $record->id,
                    'stage_id'  => $currentStage->id,
                    'user_id'   => null,
                    'action'    => ApprovalAction::AutoAdvanced->value,
                ]);

                $this->createHistory([
                    'record_id' => $record->id,
                    'user_id'   => null,
                    'action'    => ApprovalAction::AutoAdvanced->value,
                ]);

                // Legacy fallback: notify next stage's approver role directly.
                $this->notifyLegacyStage($nextStage, $record,
                    "A record has been auto-advanced to your stage after the previous stage deadline expired."
                );

                return;
            }
        }

        // Final or no next stage — auto-approve.
        $record->update([
            'status'           => RecordStatus::Completed->value,
            'current_stage_id' => null,
        ]);

        $this->createApproval([
            'record_id' => $record->id,
            'stage_id'  => $currentStage?->id,
            'user_id'   => null,
            'action'    => ApprovalAction::AutoApproved->value,
        ]);

        $this->createHistory([
            'record_id' => $record->id,
            'user_id'   => null,
            'action'    => ApprovalAction::AutoApproved->value,
        ]);

        $creator = $this->findCreator($record->created_by);
        $creator?->notify(new DynamicNotification(
            message:    "Your record in {$this->moduleName($record)} has been auto-approved (deadline expired).",
            recordId:   $record->id,
            moduleSlug: $record->module?->slug,
            sendEmail:  false,
        ));
    }

    // ── Protected hooks (overridable in tests) ────────────────────────────────

    /**
     * Return the first WorkflowStage for a module ordered by `order`.
     */
    protected function findFirstStage(int $moduleId): ?WorkflowStage
    {
        return WorkflowStage::where('module_id', $moduleId)
            ->orderBy('order')
            ->first();
    }

    /**
     * Return the next WorkflowStage after a given order value.
     */
    protected function findNextStage(int $moduleId, int $afterOrder): ?WorkflowStage
    {
        return WorkflowStage::where('module_id', $moduleId)
            ->where('order', '>', $afterOrder)
            ->orderBy('order')
            ->first();
    }

    /**
     * Return a WorkflowStage by primary key.
     */
    protected function findStageById(int $id): ?WorkflowStage
    {
        return WorkflowStage::find($id);
    }

    /**
     * Persist a RecordApproval row.
     */
    protected function createApproval(array $data): void
    {
        RecordApproval::create($data);
    }

    /**
     * Persist a RecordHistory row.
     */
    protected function createHistory(array $data): void
    {
        RecordHistory::create($data);
    }

    /**
     * Resolve the record creator by ID.
     */
    protected function findCreator(mixed $id): ?User
    {
        return User::find($id);
    }

    /**
     * Return all users belonging to $roleName (overridable in tests).
     */
    protected function getUsersByRole(string $roleName): Collection
    {
        return User::role($roleName)->get();
    }

    /**
     * Return all users who hold the given Spatie permission (overridable in tests).
     * Returns an empty collection when the permission has not been created yet.
     */
    protected function getUsersWithPermission(string $permission): Collection
    {
        try {
            return User::permission($permission)->get();
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            return collect();
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Notify stage recipients.
     * Uses NotificationService for configured recipients; falls back to legacy role-based direct notify.
     */
    private function notifyStage(WorkflowStage $stage, Record $record, string $message): void
    {
        $configured = $stage->notify_on_enter_json ?? [];

        if (! empty($configured)) {
            $this->notifications->notifyRecipients($configured, $record, $message);
            return;
        }

        // Legacy fallback — notify approver role users directly (bypasses NotificationService).
        $this->notifyLegacyStage($stage, $record, $message);
    }

    /**
     * Legacy notification path: notify approver-role users directly via DynamicNotification.
     * When no approver role is configured on the stage, falls back to notifying all users
     * who hold the approve-{module} Spatie permission directly — matching the ApprovalQueue
     * visibility logic which uses the same two paths (role OR direct permission).
     */
    private function notifyLegacyStage(WorkflowStage $stage, Record $record, string $message): void
    {
        if ($stage->approver_role_id) {
            $role = $stage->approverRole;
            if ($role) {
                foreach ($this->getUsersByRole($role->name) as $user) {
                    $user->notify(new DynamicNotification(
                        message:    $message,
                        recordId:   $record->id,
                        moduleSlug: $record->module?->slug,
                        sendEmail:  false,
                    ));
                }
            }
            return;
        }

        // No stage role configured — notify users with direct approve permission on this module.
        $moduleSlug = $record->module?->slug;
        if (! $moduleSlug) {
            return;
        }

        foreach ($this->getUsersWithPermission("approve-{$moduleSlug}") as $user) {
            $user->notify(new DynamicNotification(
                message:    $message,
                recordId:   $record->id,
                moduleSlug: $moduleSlug,
                sendEmail:  false,
            ));
        }
    }

    /**
     * Safely retrieve the module name from the record.
     */
    private function moduleName(Record $record): string
    {
        return $record->module?->name ?? 'this module';
    }
}
