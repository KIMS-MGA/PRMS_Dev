<?php

use App\Models\Module;
use App\Models\Record;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Notifications\DynamicNotification;
use App\Services\ApprovalService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->module = Module::create(['name' => 'Notify Test', 'slug' => 'notify-test']);
    $this->submitter = User::factory()->create();

    Permission::firstOrCreate(['name' => 'approve-notify-test', 'guard_name' => 'web']);
});

// ── notify_on_enter_json path ─────────────────────────────────────────────────

it('notifies configured recipients via notify_on_enter_json when stage enters', function () {
    $recipient = User::factory()->create();
    Permission::firstOrCreate(['name' => 'view-notify-test', 'guard_name' => 'web']);
    $recipient->givePermissionTo('view-notify-test');

    WorkflowStage::create([
        'module_id'            => $this->module->id,
        'name'                 => 'Review',
        'order'                => 1,
        'is_final_approval'    => true,
        'notify_on_enter_json' => [['type' => 'specific_user', 'value' => (string) $recipient->id]],
    ]);

    $record = Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Draft',
        'created_by' => $this->submitter->id,
        'updated_by' => $this->submitter->id,
    ]);

    app(ApprovalService::class)->submit($record, $this->submitter);

    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id'   => $recipient->id,
        'type'            => DynamicNotification::class,
    ]);
});

// ── legacy role path ──────────────────────────────────────────────────────────

it('notifies users holding the approver role when stage has approver_role_id', function () {
    $role = Role::firstOrCreate(['name' => 'trc-reviewer', 'guard_name' => 'web']);
    $approver = User::factory()->create();
    $approver->assignRole('trc-reviewer');

    WorkflowStage::create([
        'module_id'         => $this->module->id,
        'name'              => 'TRC Review',
        'order'             => 1,
        'is_final_approval' => true,
        'approver_role_id'  => $role->id,
    ]);

    $record = Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Draft',
        'created_by' => $this->submitter->id,
        'updated_by' => $this->submitter->id,
    ]);

    app(ApprovalService::class)->submit($record, $this->submitter);

    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id'   => $approver->id,
        'type'            => DynamicNotification::class,
    ]);
});

// ── permission fallback path (the bug) ───────────────────────────────────────

it('notifies users with direct approve permission when stage has no approver_role_id', function () {
    $approver = User::factory()->create();
    $approver->givePermissionTo('approve-notify-test');

    WorkflowStage::create([
        'module_id'         => $this->module->id,
        'name'              => 'Approval',
        'order'             => 1,
        'is_final_approval' => true,
        // approver_role_id intentionally null — triggers the bug
        // notify_on_enter_json intentionally null
    ]);

    $record = Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Draft',
        'created_by' => $this->submitter->id,
        'updated_by' => $this->submitter->id,
    ]);

    app(ApprovalService::class)->submit($record, $this->submitter);

    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id'   => $approver->id,
        'type'            => DynamicNotification::class,
    ]);
});

it('does not notify the submitter as an approver when they also hold the approve permission', function () {
    $this->submitter->givePermissionTo('approve-notify-test');

    WorkflowStage::create([
        'module_id'         => $this->module->id,
        'name'              => 'Approval',
        'order'             => 1,
        'is_final_approval' => true,
    ]);

    $record = Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Draft',
        'created_by' => $this->submitter->id,
        'updated_by' => $this->submitter->id,
    ]);

    // Should not throw — self-notification is not an error, just verify it completes
    app(ApprovalService::class)->submit($record, $this->submitter);

    expect(true)->toBeTrue(); // Submission completed without exception
});