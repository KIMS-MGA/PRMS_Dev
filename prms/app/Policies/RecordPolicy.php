<?php

namespace App\Policies;

use App\Models\Record;
use App\Models\User;
use App\Models\WorkflowStage;
use Spatie\Permission\Models\Role;

class RecordPolicy
{
    public function view(User $user, Record $record): bool
    {
        return $user->hasRole('super admin')
            || $user->can("view-{$record->module->slug}");
    }

    /**
     * @param  string  $moduleSlug  Passed explicitly because no Record exists yet at create-time.
     */
    public function create(User $user, string $moduleSlug): bool
    {
        return $user->hasRole('super admin')
            || $user->can("create-{$moduleSlug}");
    }

    public function update(User $user, Record $record): bool
    {
        return $user->hasRole('super admin')
            || $user->can("edit-{$record->module->slug}");
    }

    public function approve(User $user, Record $record): bool
    {
        if ($user->hasRole('super admin')) return true;

        $stage = $record->currentStage;
        if ($stage?->approver_role_id) {
            $role = Role::find($stage->approver_role_id);
            if ($role && $user->hasRole($role->name)) return true;
        }

        return $user->can("approve-{$record->module->slug}");
    }

    /**
     * Super admin is intentionally blocked — reviewers are a specific role subset.
     * WARNING: Gate::before() in AppServiceProvider returns true for super admin before
     * this method runs. Do NOT call Gate::allows('review', $record) for super admin
     * until the Gate::before() conflict is resolved in Phase 3.
     */
    public function review(User $user, Record $record): bool
    {
        if ($user->hasRole('super admin')) return false;

        return $user->can("review-{$record->module->slug}");
    }

    /**
     * Used by TextEditorController to gate WebSocket token validation and editor history.
     * Allows any user with any module-level permission or a matching stage approver role.
     */
    public function viewEditor(User $user, Record $record): bool
    {
        if ($user->hasRole('super admin')) return true;

        $module = $record->module;
        $slug   = $module->slug;

        if ($user->can("view-{$slug}") || $user->can("edit-{$slug}") ||
            $user->can("approve-{$slug}") || $user->can("review-{$slug}")) {
            return true;
        }

        $stageRoleIds = WorkflowStage::where('module_id', $module->id)
            ->pluck('approver_role_id')
            ->filter();

        return $user->roles->pluck('id')->intersect($stageRoleIds)->isNotEmpty();
    }
}
