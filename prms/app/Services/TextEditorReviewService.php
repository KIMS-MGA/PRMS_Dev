<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkflowStage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TextEditorReviewService
{
    public function recordReview(int $recordId, string $fieldSlug, int $userId): void
    {
        DB::table('text_editor_reviews')->updateOrInsert(
            ['record_id' => $recordId, 'field_slug' => $fieldSlug, 'user_id' => $userId],
            ['reviewed_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function countDone(int $recordId, string $fieldSlug): int
    {
        return DB::table('text_editor_reviews')
            ->where('record_id', $recordId)
            ->where('field_slug', $fieldSlug)
            ->count();
    }

    /**
     * Count unique non-super-admin users who have the review-{moduleSlug} permission.
     * Used by DynamicRecordForm (permission-based reviewer count strategy).
     */
    public function countPermissionReviewers(string $moduleSlug): int
    {
        $permission = Permission::where('name', "review-{$moduleSlug}")
            ->where('guard_name', 'web')
            ->first();

        if (! $permission) {
            return 0;
        }

        $superAdminRole = Role::where('name', 'super admin')->first();

        return DB::table('model_has_roles')
            ->join('role_has_permissions', 'model_has_roles.role_id', '=', 'role_has_permissions.role_id')
            ->where('role_has_permissions.permission_id', $permission->id)
            ->where('model_has_roles.model_type', User::class)
            ->when($superAdminRole, fn ($q) => $q->where('model_has_roles.role_id', '!=', $superAdminRole->id))
            ->distinct()
            ->count('model_has_roles.model_id');
    }

    /**
     * Count users in the stage's approver role.
     * Used by DynamicRecordShow (stage-role-based reviewer count strategy).
     */
    public function countStageReviewers(WorkflowStage $stage): int
    {
        if (! $stage->approver_role_id) {
            return 0;
        }

        $role = Role::find($stage->approver_role_id);

        return $role ? User::role($role->name)->count() : 0;
    }

    public function getReviewedFields(int $recordId, int $userId): array
    {
        return DB::table('text_editor_reviews')
            ->where('record_id', $recordId)
            ->where('user_id', $userId)
            ->pluck('field_slug')
            ->all();
    }

    public function getReviewersByField(int $recordId): Collection
    {
        return DB::table('text_editor_reviews')
            ->join('users', 'text_editor_reviews.user_id', '=', 'users.id')
            ->where('text_editor_reviews.record_id', $recordId)
            ->orderBy('text_editor_reviews.reviewed_at')
            ->select('text_editor_reviews.field_slug', 'users.name')
            ->get()
            ->groupBy('field_slug');
    }
}
