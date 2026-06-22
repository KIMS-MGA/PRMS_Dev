<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('modules')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $modules = [
            [
                'id' => 1,
                'name' => 'Policy Proposals',
                'slug' => 'policy_proposals',
                'description' => 'General process flow for policy proposals',
                'default_status' => 'Draft',
                'my_records_only' => 1,
                'sort_order' => 1,
                'has_submit_button' => 1,
                'has_return_button' => 1,
                'has_draft_button' => 1,
                'created_at' => '2026-03-27 02:20:51',
                'updated_at' => '2026-06-09 14:32:47',
                'source_module_id' => null,
            ],
            [
                'id' => 2,
                'name' => 'Consolidated Policies',
                'slug' => 'consolidated_policies',
                'description' => '',
                'default_status' => 'Submitted',
                'my_records_only' => 0,
                'sort_order' => 2,
                'has_submit_button' => 0,
                'has_return_button' => 0,
                'has_draft_button' => 0,
                'created_at' => '2026-03-27 05:31:26',
                'updated_at' => '2026-03-27 05:31:26',
                'source_module_id' => 1,
            ],
        ];

        DB::table('modules')->insert($modules);
    }
}
