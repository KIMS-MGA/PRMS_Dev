<?php

use App\Models\Module;
use App\Models\Record;
use App\Models\User;
use App\Models\WorkflowStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'web']);

    $this->module = Module::create(['name' => 'Editor Test', 'slug' => 'editor-test']);

    $owner = User::factory()->create();

    $this->record = Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Draft',
        'created_by' => $owner->id,
        'updated_by' => $owner->id,
    ]);

    $slug = $this->module->slug;
    Permission::firstOrCreate(['name' => "view-{$slug}",    'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => "edit-{$slug}",    'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => "approve-{$slug}", 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => "review-{$slug}",  'guard_name' => 'web']);
});

it('allows a user with view permission to access editor endpoints', function () {
    $user = User::factory()->create();
    $user->givePermissionTo("view-editor-test");
    Sanctum::actingAs($user);

    $this->getJson("/api/text-editor/{$this->record->id}/body/history")
        ->assertOk();
});

it('allows super admin to access editor endpoints', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super admin');
    Sanctum::actingAs($admin);

    $this->getJson("/api/text-editor/{$this->record->id}/body/history")
        ->assertOk();
});

it('denies a user with no module permissions', function () {
    $stranger = User::factory()->create();
    Sanctum::actingAs($stranger);

    $this->getJson("/api/text-editor/{$this->record->id}/body/history")
        ->assertForbidden();
});

it('allows a user with a matching stage approver role', function () {
    $approverRole = Role::firstOrCreate(['name' => 'stage-approver', 'guard_name' => 'web']);

    WorkflowStage::create([
        'module_id'        => $this->module->id,
        'name'             => 'Review Stage',
        'order'            => 1,
        'approver_role_id' => $approverRole->id,
    ]);

    $approver = User::factory()->create();
    $approver->assignRole('stage-approver');
    Sanctum::actingAs($approver);

    $this->getJson("/api/text-editor/{$this->record->id}/body/history")
        ->assertOk();
});