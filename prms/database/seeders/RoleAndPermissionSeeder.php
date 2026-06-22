<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionsTable = config('permission.table_names.permissions', 'permissions');
        $rolesTable = config('permission.table_names.roles', 'roles');
        $roleHasPermissionsTable = config('permission.table_names.role_has_permissions', 'role_has_permissions');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table($roleHasPermissionsTable)->truncate();
        DB::table($rolesTable)->truncate();
        DB::table($permissionsTable)->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $permissions = [
            ['id' => 1, 'name' => 'view-policy_proposals', 'guard_name' => 'web', 'created_at' => '2026-03-27 02:20:51', 'updated_at' => '2026-03-27 02:20:51'],
            ['id' => 2, 'name' => 'create-policy_proposals', 'guard_name' => 'web', 'created_at' => '2026-03-27 02:20:51', 'updated_at' => '2026-03-27 02:20:51'],
            ['id' => 3, 'name' => 'edit-policy_proposals', 'guard_name' => 'web', 'created_at' => '2026-03-27 02:20:51', 'updated_at' => '2026-03-27 02:20:51'],
            ['id' => 4, 'name' => 'delete-policy_proposals', 'guard_name' => 'web', 'created_at' => '2026-03-27 02:20:51', 'updated_at' => '2026-03-27 02:20:51'],
            ['id' => 5, 'name' => 'change-status-policy_proposals', 'guard_name' => 'web', 'created_at' => '2026-03-27 02:20:51', 'updated_at' => '2026-03-27 02:20:51'],
            ['id' => 6, 'name' => 'review-policy_proposals', 'guard_name' => 'web', 'created_at' => '2026-03-27 02:20:51', 'updated_at' => '2026-03-27 02:20:51'],
            ['id' => 7, 'name' => 'approve-policy_proposals', 'guard_name' => 'web', 'created_at' => '2026-03-27 02:20:51', 'updated_at' => '2026-03-27 02:20:51'],
            ['id' => 8,  'name' => 'view-consolidated_policies', 'guard_name' => 'web', 'created_at' => '2026-03-27 05:31:26', 'updated_at' => '2026-03-27 05:31:26'],
            ['id' => 9,  'name' => 'create-consolidated_policies', 'guard_name' => 'web', 'created_at' => '2026-03-27 05:31:26', 'updated_at' => '2026-03-27 05:31:26'],
            ['id' => 10, 'name' => 'edit-consolidated_policies', 'guard_name' => 'web', 'created_at' => '2026-03-27 05:31:26', 'updated_at' => '2026-03-27 05:31:26'],
            ['id' => 11, 'name' => 'delete-consolidated_policies', 'guard_name' => 'web', 'created_at' => '2026-03-27 05:31:26', 'updated_at' => '2026-03-27 05:31:26'],
            ['id' => 12, 'name' => 'change-status-consolidated_policies', 'guard_name' => 'web', 'created_at' => '2026-03-27 05:31:26', 'updated_at' => '2026-03-27 05:31:26'],
            ['id' => 13, 'name' => 'review-consolidated_policies', 'guard_name' => 'web', 'created_at' => '2026-03-27 05:31:26', 'updated_at' => '2026-03-27 05:31:26'],
            ['id' => 14, 'name' => 'approve-consolidated_policies', 'guard_name' => 'web', 'created_at' => '2026-03-27 05:31:26', 'updated_at' => '2026-03-27 05:31:26'],
        ];

        $roles = [
            ['id' => 1, 'name' => 'super admin', 'guard_name' => 'web', 'created_at' => '2026-03-27 01:07:40', 'updated_at' => '2026-03-27 01:07:40'],
            ['id' => 2, 'name' => 'Proponent', 'guard_name' => 'web', 'created_at' => '2026-03-27 02:20:51', 'updated_at' => '2026-03-27 02:20:51'],
            ['id' => 3, 'name' => 'TRC Secretariat', 'guard_name' => 'web', 'created_at' => '2026-03-27 02:20:51', 'updated_at' => '2026-03-27 02:20:51'],
            ['id' => 4, 'name' => 'Reviewer', 'guard_name' => 'web', 'created_at' => '2026-03-27 02:20:51', 'updated_at' => '2026-03-27 02:20:51'],
            ['id' => 5, 'name' => 'Receiving/Releasing', 'guard_name' => 'web', 'created_at' => '2026-04-23 10:03:35', 'updated_at' => '2026-04-23 10:03:35'],
        ];

        $roleHasPermissions = [
            ['permission_id' => 1, 'role_id' => 2],
            ['permission_id' => 2, 'role_id' => 2],
            ['permission_id' => 3, 'role_id' => 2],
            ['permission_id' => 7, 'role_id' => 3],
            ['permission_id' => 8, 'role_id' => 3],
            ['permission_id' => 6, 'role_id' => 4],
            ['permission_id' => 8, 'role_id' => 4],
            ['permission_id' => 8, 'role_id' => 5],
        ];

        DB::table($permissionsTable)->insert($permissions);
        DB::table($rolesTable)->insert($roles);
        DB::table($roleHasPermissionsTable)->insert($roleHasPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
