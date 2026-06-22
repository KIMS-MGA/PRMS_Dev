<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table($modelHasRolesTable)->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $users = [
            [
                'id' => 1,
                'name' => 'Admin',
                'email' => 'admin@prms.local',
                'theme' => 'indigo',
                'is_active' => 1,
                'email_verified_at' => '2026-04-23 01:11:22',
                'password' => bcrypt('password'),
                'two_factor_secret' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => 'qTnwFwA2YXBBJAubrAdpmS5utTZ9tfQ8rdMCLSrTb0HNYy7UIHcI90cCKnwA',
                'created_at' => '2026-03-27 01:07:40',
                'updated_at' => '2026-05-19 18:05:37',
                'google_id' => null,
            ],
            [
                'id' => 2,
                'name' => 'Proponent User',
                'email' => 'proponent@prms.local',
                'theme' => 'indigo',
                'is_active' => 1,
                'email_verified_at' => '2026-03-27 02:20:51',
                'password' => bcrypt('password'),
                'two_factor_secret' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => null,
                'created_at' => '2026-03-27 02:20:51',
                'updated_at' => '2026-03-27 02:20:51',
                'google_id' => null,
            ],
            [
                'id' => 3,
                'name' => 'TRC Secretariat User',
                'email' => 'secretariat@prms.local',
                'theme' => 'indigo',
                'is_active' => 1,
                'email_verified_at' => '2026-03-27 02:20:51',
                'password' => bcrypt('password'),
                'two_factor_secret' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => 'uVManFPcXnG3A2g6Cbm65AOqmMJsqWJT8QPrjdQ4lmshuWc8oBgpVpBruZOp',
                'created_at' => '2026-03-27 02:20:51',
                'updated_at' => '2026-06-01 11:48:56',
                'google_id' => null,
            ],
            [
                'id' => 4,
                'name' => 'Reviewer User',
                'email' => 'reviewer@prms.local',
                'theme' => 'indigo',
                'is_active' => 1,
                'email_verified_at' => '2026-03-27 02:20:51',
                'password' => bcrypt('password'),
                'two_factor_secret' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => 'eJQN03T2eK4tYzBwop43vfFScflrXrS5K6ngOUwDloXeYo9DR7GNTIxWdsdM',
                'created_at' => '2026-03-27 02:20:51',
                'updated_at' => '2026-03-27 02:20:51',
                'google_id' => null,
            ],
            [
                'id' => 5,
                'name' => 'Office of the Director',
                'email' => 'od@prms.local',
                'theme' => 'indigo',
                'is_active' => 1,
                'email_verified_at' => null,
                'password' => bcrypt('password'),
                'two_factor_secret' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => null,
                'created_at' => '2026-04-23 10:04:29',
                'updated_at' => '2026-04-23 10:08:30',
                'google_id' => null,
            ],
        ];

        $modelHasRoles = [
            ['role_id' => 1, 'model_type' => 'App\\Models\\User', 'model_id' => 1],
            ['role_id' => 2, 'model_type' => 'App\\Models\\User', 'model_id' => 2],
            ['role_id' => 3, 'model_type' => 'App\\Models\\User', 'model_id' => 3],
            ['role_id' => 4, 'model_type' => 'App\\Models\\User', 'model_id' => 4],
            ['role_id' => 5, 'model_type' => 'App\\Models\\User', 'model_id' => 5],
            ['role_id' => 1, 'model_type' => 'App\\Models\\User', 'model_id' => 6], // Exact match from dump (dangling reference)
        ];

        DB::table('users')->insert($users);
        DB::table($modelHasRolesTable)->insert($modelHasRoles);
    }
}
