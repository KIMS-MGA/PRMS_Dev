<?php

use App\Models\Module;
use App\Models\Record;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Services\TextEditorReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new TextEditorReviewService();

    $this->module = Module::create(['name' => 'Review Test', 'slug' => 'review-test']);
    $this->user   = User::factory()->create();
    $this->record = Record::create([
        'module_id'  => $this->module->id,
        'data'       => [],
        'status'     => 'Submitted',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
});

// ── recordReview() ────────────────────────────────────────────────────────────

it('recordReview() inserts a new review row', function () {
    $this->service->recordReview($this->record->id, 'body', $this->user->id);

    $this->assertDatabaseHas('text_editor_reviews', [
        'record_id'  => $this->record->id,
        'field_slug' => 'body',
        'user_id'    => $this->user->id,
    ]);
});

it('recordReview() upserts without creating a duplicate row', function () {
    $this->service->recordReview($this->record->id, 'body', $this->user->id);
    $this->service->recordReview($this->record->id, 'body', $this->user->id);

    expect(
        DB::table('text_editor_reviews')
            ->where('record_id', $this->record->id)
            ->where('field_slug', 'body')
            ->where('user_id', $this->user->id)
            ->count()
    )->toBe(1);
});

// ── countDone() ───────────────────────────────────────────────────────────────

it('countDone() returns the number of distinct users who reviewed a field', function () {
    $user2 = User::factory()->create();

    $this->service->recordReview($this->record->id, 'body', $this->user->id);
    $this->service->recordReview($this->record->id, 'body', $user2->id);

    expect($this->service->countDone($this->record->id, 'body'))->toBe(2);
});

it('countDone() returns 0 when no reviews exist', function () {
    expect($this->service->countDone($this->record->id, 'body'))->toBe(0);
});

it('countDone() counts only the specified field slug', function () {
    $this->service->recordReview($this->record->id, 'body', $this->user->id);
    $this->service->recordReview($this->record->id, 'notes', $this->user->id);

    expect($this->service->countDone($this->record->id, 'body'))->toBe(1);
    expect($this->service->countDone($this->record->id, 'notes'))->toBe(1);
});

// ── countPermissionReviewers() ────────────────────────────────────────────────

it('countPermissionReviewers() counts users with review permission excluding super admin', function () {
    $reviewPerm    = Permission::firstOrCreate(['name' => 'review-review-test', 'guard_name' => 'web']);
    $superAdminRole = Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'web']);
    $reviewerRole   = Role::firstOrCreate(['name' => 'reviewer', 'guard_name' => 'web']);

    $superAdminRole->givePermissionTo($reviewPerm);
    $reviewerRole->givePermissionTo($reviewPerm);

    $reviewer1 = User::factory()->create();
    $reviewer1->assignRole($reviewerRole);

    $reviewer2 = User::factory()->create();
    $reviewer2->assignRole($reviewerRole);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole($superAdminRole);

    // Super admin excluded — should count 2 reviewers only
    expect($this->service->countPermissionReviewers('review-test'))->toBe(2);
});

it('countPermissionReviewers() returns 0 when the permission does not exist', function () {
    expect($this->service->countPermissionReviewers('no-such-module'))->toBe(0);
});

// ── countStageReviewers() ─────────────────────────────────────────────────────

it('countStageReviewers() returns count of users in the stage approver role', function () {
    $role  = Role::firstOrCreate(['name' => 'stage-approver', 'guard_name' => 'web']);
    $stage = WorkflowStage::create([
        'module_id'       => $this->module->id,
        'name'            => 'Stage 1',
        'order'           => 1,
        'approver_role_id' => $role->id,
    ]);

    User::factory()->count(3)->create()->each(fn ($u) => $u->assignRole($role));

    expect($this->service->countStageReviewers($stage))->toBe(3);
});

it('countStageReviewers() returns 0 when stage has no approver role', function () {
    $stage = WorkflowStage::create([
        'module_id' => $this->module->id,
        'name'      => 'Stage 1',
        'order'     => 1,
    ]);

    expect($this->service->countStageReviewers($stage))->toBe(0);
});

// ── getReviewedFields() ───────────────────────────────────────────────────────

it('getReviewedFields() returns all field slugs reviewed by a specific user', function () {
    $user2 = User::factory()->create();

    $this->service->recordReview($this->record->id, 'body', $this->user->id);
    $this->service->recordReview($this->record->id, 'notes', $this->user->id);
    $this->service->recordReview($this->record->id, 'body', $user2->id);

    $fields = $this->service->getReviewedFields($this->record->id, $this->user->id);

    expect($fields)->toContain('body')->toContain('notes')->toHaveCount(2);
});

it('getReviewedFields() returns empty array when user has no reviews', function () {
    expect($this->service->getReviewedFields($this->record->id, $this->user->id))->toBe([]);
});

// ── getReviewersByField() ─────────────────────────────────────────────────────

it('getReviewersByField() groups reviewer names by field slug', function () {
    $user2 = User::factory()->create();

    $this->service->recordReview($this->record->id, 'body', $this->user->id);
    $this->service->recordReview($this->record->id, 'body', $user2->id);
    $this->service->recordReview($this->record->id, 'notes', $this->user->id);

    $result = $this->service->getReviewersByField($this->record->id);

    expect($result->keys()->all())->toContain('body')->toContain('notes');
    expect($result->get('body'))->toHaveCount(2);
    expect($result->get('notes'))->toHaveCount(1);
});

it('getReviewersByField() returns empty collection when no reviews exist', function () {
    $result = $this->service->getReviewersByField($this->record->id);

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->toHaveCount(0);
});
