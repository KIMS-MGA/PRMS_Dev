<?php

use App\Livewire\Builder\ApprovalQueue;
use App\Livewire\Builder\DynamicRecordShow;
use App\Models\Module;
use App\Models\Record;
use App\Models\RecordApproval;
use App\Models\User;
use App\Models\WorkflowStage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Creates a minimal Module + review WorkflowStage and two reviewer Users
 * assigned to $roleName.  Returns an array with keys:
 *   module, stage, reviewer_a, reviewer_b, record
 */
function setupReviewScenario(string $roleName = 'TRC Reviewer'): array
{
    $module = Module::create([
        'name' => 'Test Proposals',
        'slug' => 'test-proposals',
    ]);

    $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

    $stage = WorkflowStage::create([
        'module_id'        => $module->id,
        'name'             => 'TRC Review',
        'order'            => 1,
        'stage_type'       => 'review',
        'approver_role_id' => $role->id,
        'is_final_approval' => false,
        'default_status'   => 'Under Review',
    ]);

    $reviewerA = User::factory()->create();
    $reviewerA->assignRole($role);

    $reviewerB = User::factory()->create();
    $reviewerB->assignRole($role);

    $submitter = User::factory()->create();

    $record = Record::create([
        'module_id'        => $module->id,
        'data'             => ['title' => 'Proposal Alpha'],
        'status'           => 'Under Review',
        'current_stage_id' => $stage->id,
        'stage_entered_at' => now(),
        'created_by'       => $submitter->id,
        'updated_by'       => $submitter->id,
    ]);

    return compact('module', 'stage', 'role', 'reviewerA', 'reviewerB', 'submitter', 'record');
}

// ---------------------------------------------------------------------------
// RecordApproval model helper tests
// ---------------------------------------------------------------------------

it('finalizedReviewerCount counts only rows with reviewed_at set', function () {
    $scenario = setupReviewScenario();
    ['record' => $record, 'stage' => $stage, 'reviewerA' => $reviewerA, 'reviewerB' => $reviewerB] = $scenario;

    // No finalizations yet
    expect(RecordApproval::finalizedReviewerCount($record->id, $stage->id))->toBe(0);

    // Reviewer A finalizes
    RecordApproval::create([
        'record_id'   => $record->id,
        'stage_id'    => $stage->id,
        'user_id'     => $reviewerA->id,
        'action'      => 'returned',
        'reviewed_at' => now(),
    ]);

    expect(RecordApproval::finalizedReviewerCount($record->id, $stage->id))->toBe(1);

    // A row WITHOUT reviewed_at should not be counted
    RecordApproval::create([
        'record_id'   => $record->id,
        'stage_id'    => $stage->id,
        'user_id'     => $reviewerB->id,
        'action'      => 'approved',
        'reviewed_at' => null,
    ]);

    expect(RecordApproval::finalizedReviewerCount($record->id, $stage->id))->toBe(1);
});

it('hasUserFinalized returns true only after reviewed_at row exists', function () {
    $scenario = setupReviewScenario();
    ['record' => $record, 'stage' => $stage, 'reviewerA' => $reviewerA] = $scenario;

    expect(RecordApproval::hasUserFinalized($record->id, $stage->id, $reviewerA->id))->toBeFalse();

    RecordApproval::create([
        'record_id'   => $record->id,
        'stage_id'    => $stage->id,
        'user_id'     => $reviewerA->id,
        'action'      => 'approved',
        'reviewed_at' => now(),
    ]);

    expect(RecordApproval::hasUserFinalized($record->id, $stage->id, $reviewerA->id))->toBeTrue();
});

// ---------------------------------------------------------------------------
// returnForRevision — core bug scenario
// ---------------------------------------------------------------------------

it('returnForRevision does NOT change global record status when other reviewers have not yet finalized', function () {
    $scenario = setupReviewScenario();
    ['module' => $module, 'stage' => $stage, 'reviewerA' => $reviewerA, 'record' => $record] = $scenario;

    // Reviewer A returns the proposal
    Livewire::actingAs($reviewerA)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->set('approvalComment', 'Needs more detail on the methodology.')
        ->call('returnForRevision')
        ->assertDispatched('notify', fn($name, $params) =>
            ($params['type'] ?? null) === 'success'
            && str_contains($params['message'] ?? '', 'Waiting for other reviewers')
        );

    // Global record status MUST remain 'Under Review', not 'Returned'
    $record->refresh();
    expect($record->status)->toBe('Under Review');
    expect($record->current_stage_id)->toBe($stage->id);
});

it('returnForRevision saves a per-reviewer approval row with reviewed_at set', function () {
    $scenario = setupReviewScenario();
    ['module' => $module, 'reviewerA' => $reviewerA, 'record' => $record, 'stage' => $stage] = $scenario;

    Livewire::actingAs($reviewerA)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->set('approvalComment', 'Return this please.')
        ->call('returnForRevision');

    $approval = RecordApproval::where('record_id', $record->id)
        ->where('user_id', $reviewerA->id)
        ->where('action', 'returned')
        ->first();

    expect($approval)->not->toBeNull();
    expect($approval->reviewed_at)->not->toBeNull();
    expect($approval->stage_id)->toBe($stage->id);
    expect($approval->comment)->toBe('Return this please.');
});

it('proposal stays in Reviewer B queue after Reviewer A calls returnForRevision', function () {
    $scenario = setupReviewScenario();
    ['module' => $module, 'reviewerA' => $reviewerA, 'reviewerB' => $reviewerB, 'record' => $record, 'stage' => $stage] = $scenario;

    // Reviewer A returns
    Livewire::actingAs($reviewerA)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->set('approvalComment', 'Insufficient data.')
        ->call('returnForRevision');

    // Reviewer B's Approval Queue must still show the proposal
    Livewire::actingAs($reviewerB)
        ->test(ApprovalQueue::class)
        ->assertSee('Proposal Alpha');
});

it('Reviewer A queue no longer shows the proposal after they finalize', function () {
    $scenario = setupReviewScenario();
    ['module' => $module, 'reviewerA' => $reviewerA, 'record' => $record] = $scenario;

    Livewire::actingAs($reviewerA)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->set('approvalComment', 'See notes.')
        ->call('returnForRevision');

    // Reviewer A's queue should NOT show this record anymore
    Livewire::actingAs($reviewerA)
        ->test(ApprovalQueue::class)
        ->assertDontSee('Proposal Alpha');
});

it('record exits all queues and status becomes Returned only after all reviewers finalize', function () {
    $scenario = setupReviewScenario();
    ['module' => $module, 'stage' => $stage, 'reviewerA' => $reviewerA, 'reviewerB' => $reviewerB, 'record' => $record] = $scenario;

    // Reviewer A returns
    Livewire::actingAs($reviewerA)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->set('approvalComment', 'Please revise section 2.')
        ->call('returnForRevision');

    // Still in workflow — status unchanged
    $record->refresh();
    expect($record->status)->toBe('Under Review');

    // Reviewer B also returns
    Livewire::actingAs($reviewerB)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->set('approvalComment', 'Agree, revisions needed.')
        ->call('returnForRevision')
        ->assertDispatched('notify', fn($name, $params) =>
            ($params['type'] ?? null) === 'success'
            && str_contains($params['message'] ?? '', 'returned for revision')
        );

    // Now the global status should be 'Returned' and stage cleared
    $record->refresh();
    expect($record->status)->toBe('Returned');
    expect($record->current_stage_id)->toBeNull();

    // Neither reviewer should see it in the queue
    Livewire::actingAs($reviewerA)
        ->test(ApprovalQueue::class)
        ->assertDontSee('Proposal Alpha');

    Livewire::actingAs($reviewerB)
        ->test(ApprovalQueue::class)
        ->assertDontSee('Proposal Alpha');
});

// ---------------------------------------------------------------------------
// approve() — existing review-stage wait logic should still work correctly
// ---------------------------------------------------------------------------

it('approve does NOT advance record when first reviewer approves but second has not yet', function () {
    $scenario = setupReviewScenario();
    ['module' => $module, 'stage' => $stage, 'reviewerA' => $reviewerA, 'record' => $record] = $scenario;

    Livewire::actingAs($reviewerA)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->call('approve')
        ->assertDispatched('notify', fn($name, $params) =>
            ($params['type'] ?? null) === 'success'
            && str_contains($params['message'] ?? '', 'Waiting for other reviewers')
        );

    $record->refresh();
    expect($record->current_stage_id)->toBe($stage->id);
});

it('approve saves a finalized RecordApproval row with reviewed_at set', function () {
    $scenario = setupReviewScenario();
    ['module' => $module, 'reviewerA' => $reviewerA, 'record' => $record, 'stage' => $stage] = $scenario;

    Livewire::actingAs($reviewerA)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->call('approve');

    $approval = RecordApproval::where('record_id', $record->id)
        ->where('user_id', $reviewerA->id)
        ->where('action', 'approved')
        ->first();

    expect($approval)->not->toBeNull();
    expect($approval->reviewed_at)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// Mixed actions: one reviewer approves, other returns
// ---------------------------------------------------------------------------

it('record exits queue when one reviewer approves and the other returns (all finalized)', function () {
    $scenario = setupReviewScenario();
    ['module' => $module, 'stage' => $stage, 'reviewerA' => $reviewerA, 'reviewerB' => $reviewerB, 'record' => $record] = $scenario;

    // Reviewer A approves — record should NOT advance yet
    Livewire::actingAs($reviewerA)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->call('approve');

    $record->refresh();
    expect($record->current_stage_id)->toBe($stage->id);

    // Reviewer B returns — all reviewers are now done
    Livewire::actingAs($reviewerB)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->set('approvalComment', 'Not ready.')
        ->call('returnForRevision');

    $record->refresh();
    // When actions differ across reviewers the "returned" path wins because the
    // last finalization triggers global state update with 'Returned' status.
    expect($record->current_stage_id)->toBeNull();
});

// ---------------------------------------------------------------------------
// Audit trail
// ---------------------------------------------------------------------------

it('each reviewer action is independently persisted with a distinct timestamp', function () {
    $scenario = setupReviewScenario();
    ['module' => $module, 'reviewerA' => $reviewerA, 'reviewerB' => $reviewerB, 'record' => $record, 'stage' => $stage] = $scenario;

    $beforeA = now()->subSecond();

    Livewire::actingAs($reviewerA)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->set('approvalComment', 'Notes from A.')
        ->call('returnForRevision');

    $approvalA = RecordApproval::where('record_id', $record->id)
        ->where('user_id', $reviewerA->id)
        ->whereNotNull('reviewed_at')
        ->first();

    expect($approvalA)->not->toBeNull();
    expect($approvalA->reviewed_at->gte($beforeA))->toBeTrue();

    $beforeB = now()->subSecond();

    Livewire::actingAs($reviewerB)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->set('approvalComment', 'Notes from B.')
        ->call('returnForRevision');

    $approvalB = RecordApproval::where('record_id', $record->id)
        ->where('user_id', $reviewerB->id)
        ->whereNotNull('reviewed_at')
        ->first();

    expect($approvalB)->not->toBeNull();
    expect($approvalB->reviewed_at->gte($beforeB))->toBeTrue();

    // Two separate rows — one per reviewer
    $totalRows = RecordApproval::where('record_id', $record->id)
        ->whereNotNull('reviewed_at')
        ->count();

    expect($totalRows)->toBe(2);
});
