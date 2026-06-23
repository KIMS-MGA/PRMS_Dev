<?php

use App\Events\RecordSaved;
use App\Models\Module;
use App\Models\Record;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

// ── N+1 fix: same role resolved only once per event dispatch ──────────────────

it('queries the database only once per distinct role name across multiple notify_role actions', function () {
    Notification::fake();

    $role = Role::firstOrCreate(['name' => 'reviewer', 'guard_name' => 'web']);
    User::factory()->count(2)->create()->each->assignRole('reviewer');

    $module   = Module::create(['name' => 'Workflow Test', 'slug' => 'wf-test']);
    $workflow = Workflow::create([
        'module_id'       => $module->id,
        'name'            => 'Multi-notify',
        'trigger'         => 'record.submitted',
        'conditions_json' => ['conditions' => []],
    ]);

    // Two actions both referencing the same role — without once() this fires 2 role queries
    WorkflowAction::create([
        'workflow_id' => $workflow->id,
        'type'        => 'notify_role',
        'config_json' => ['role_name' => 'reviewer', 'message' => 'Action 1'],
    ]);
    WorkflowAction::create([
        'workflow_id' => $workflow->id,
        'type'        => 'notify_role',
        'config_json' => ['role_name' => 'reviewer', 'message' => 'Action 2'],
    ]);

    $user   = User::factory()->create();
    $record = Record::create([
        'module_id'  => $module->id,
        'data'       => [],
        'status'     => 'Submitted',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    DB::enableQueryLog();

    event(new RecordSaved($record, 'record.submitted'));

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Count how many times the role→user query ran (contains 'roles' table join or 'role_has_permissions')
    $roleQueries = collect($queries)->filter(
        fn($q) => str_contains($q['query'], 'roles') && str_contains($q['query'], 'users')
    );

    // Spatie generates 2 SQL statements per User::role() call.
    // With cache: 1 DB call × 2 SQL = 2. Without cache: 2 DB calls × 2 SQL = 4.
    expect($roleQueries->count())->toBe(2);
});

it('caches per role name — a repeated role in three actions produces fewer queries than without cache', function () {
    Notification::fake();

    Role::firstOrCreate(['name' => 'editor',   'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'reviewer',  'guard_name' => 'web']);
    User::factory()->create()->assignRole('editor');
    User::factory()->create()->assignRole('reviewer');

    $module   = Module::create(['name' => 'Workflow Test 2', 'slug' => 'wf-test-2']);
    $workflow = Workflow::create([
        'module_id'       => $module->id,
        'name'            => 'Three-action',
        'trigger'         => 'record.submitted',
        'conditions_json' => ['conditions' => []],
    ]);

    // editor — reviewer — editor again: without cache = 3 DB calls; with cache = 2 (editor cached)
    WorkflowAction::create(['workflow_id' => $workflow->id, 'type' => 'notify_role', 'config_json' => ['role_name' => 'editor',   'message' => 'A']]);
    WorkflowAction::create(['workflow_id' => $workflow->id, 'type' => 'notify_role', 'config_json' => ['role_name' => 'reviewer',  'message' => 'B']]);
    WorkflowAction::create(['workflow_id' => $workflow->id, 'type' => 'notify_role', 'config_json' => ['role_name' => 'editor',   'message' => 'C']]);

    $user   = User::factory()->create();
    $record = Record::create([
        'module_id'  => $module->id,
        'data'       => [],
        'status'     => 'Submitted',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    DB::enableQueryLog();
    event(new RecordSaved($record, 'record.submitted'));
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $roleQueries = collect($queries)->filter(
        fn($q) => str_contains($q['query'], 'roles') && str_contains($q['query'], 'users')
    );

    // Spatie = 2 SQL per User::role() call.
    // With cache: 2 distinct roles × 2 SQL = 4. Without cache: 3 actions × 2 SQL = 6.
    expect($roleQueries->count())->toBe(4);
});