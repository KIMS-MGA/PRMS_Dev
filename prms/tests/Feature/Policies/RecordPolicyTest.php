<?php

use App\Models\Module;
use App\Models\Record;
use App\Models\User;
use App\Policies\RecordPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new RecordPolicy();

    // Ensure roles exist (seeder may not run in tests)
    Role::firstOrCreate(['name' => 'super admin',     'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'proponent',       'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'trc secretariat', 'guard_name' => 'web']);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super admin');

    $this->module = Module::create(['name' => 'Policy Test', 'slug' => 'policy-test']);

    $this->record = Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Draft',
        'created_by' => $this->superAdmin->id,
        'updated_by' => $this->superAdmin->id,
    ]);

    $slug = $this->module->slug;
    $viewPerm    = Permission::firstOrCreate(['name' => "view-{$slug}",    'guard_name' => 'web']);
    $createPerm  = Permission::firstOrCreate(['name' => "create-{$slug}",  'guard_name' => 'web']);
    $editPerm    = Permission::firstOrCreate(['name' => "edit-{$slug}",    'guard_name' => 'web']);
    $reviewPerm  = Permission::firstOrCreate(['name' => "review-{$slug}",  'guard_name' => 'web']);
    $approvePerm = Permission::firstOrCreate(['name' => "approve-{$slug}", 'guard_name' => 'web']);

    $this->proponent = User::factory()->create();
    $this->proponent->assignRole('proponent');
    $this->proponent->givePermissionTo($viewPerm);
    $this->proponent->givePermissionTo($createPerm);
    $this->proponent->givePermissionTo($editPerm);

    $this->reviewer = User::factory()->create();
    $this->reviewer->assignRole('trc secretariat');
    $this->reviewer->givePermissionTo($reviewPerm);

    $this->approver = User::factory()->create();
    $this->approver->givePermissionTo($approvePerm);

    $this->stranger = User::factory()->create();
});

// ── view ─────────────────────────────────────────────────────────────────────

it('allows super admin to view any record', function () {
    expect($this->policy->view($this->superAdmin, $this->record))->toBeTrue();
});

it('allows a user with view permission to view a record', function () {
    expect($this->policy->view($this->proponent, $this->record))->toBeTrue();
});

it('denies a user without view permission', function () {
    expect($this->policy->view($this->stranger, $this->record))->toBeFalse();
});

// ── create ───────────────────────────────────────────────────────────────────

it('allows super admin to create records', function () {
    expect($this->policy->create($this->superAdmin, $this->module->slug))->toBeTrue();
});

it('allows a user with create permission to create records', function () {
    expect($this->policy->create($this->proponent, $this->module->slug))->toBeTrue();
});

it('denies a user without create permission', function () {
    expect($this->policy->create($this->stranger, $this->module->slug))->toBeFalse();
});

// ── update ───────────────────────────────────────────────────────────────────

it('allows super admin to update any record', function () {
    expect($this->policy->update($this->superAdmin, $this->record))->toBeTrue();
});

it('allows a user with edit permission to update a record', function () {
    expect($this->policy->update($this->proponent, $this->record))->toBeTrue();
});

it('denies a user without edit permission', function () {
    expect($this->policy->update($this->stranger, $this->record))->toBeFalse();
});

// ── approve ──────────────────────────────────────────────────────────────────

it('allows super admin to approve', function () {
    expect($this->policy->approve($this->superAdmin, $this->record))->toBeTrue();
});

it('allows a user with approve permission to approve', function () {
    expect($this->policy->approve($this->approver, $this->record))->toBeTrue();
});

it('denies a user without approve permission', function () {
    expect($this->policy->approve($this->stranger, $this->record))->toBeFalse();
});

// ── review ───────────────────────────────────────────────────────────────────
// NOTE: super admin is intentionally BLOCKED from reviewing (tested directly on policy,
// bypassing Gate::before — see known conflict note in the plan).

it('blocks super admin from reviewing (policy direct call)', function () {
    expect($this->policy->review($this->superAdmin, $this->record))->toBeFalse();
});

it('allows a user with review permission to review', function () {
    expect($this->policy->review($this->reviewer, $this->record))->toBeTrue();
});

it('denies a user without review permission', function () {
    expect($this->policy->review($this->stranger, $this->record))->toBeFalse();
});

// ── viewEditor ───────────────────────────────────────────────────────────────

it('allows super admin to access the editor', function () {
    expect($this->policy->viewEditor($this->superAdmin, $this->record))->toBeTrue();
});

it('allows a user with view permission to access the editor', function () {
    expect($this->policy->viewEditor($this->proponent, $this->record))->toBeTrue();
});

it('denies a user with no module permissions from accessing the editor', function () {
    expect($this->policy->viewEditor($this->stranger, $this->record))->toBeFalse();
});
