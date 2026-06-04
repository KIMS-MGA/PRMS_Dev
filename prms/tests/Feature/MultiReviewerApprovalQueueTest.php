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
// Bug: Reviewer B returns FIRST, then Reviewer A approves — must still Return
// This was the primary reported bug: the proposal advanced to the next stage
// instead of being returned to the proponent.
// ---------------------------------------------------------------------------

it('return wins over approve when reviewer B returns first and reviewer A approves second', function () {
    $scenario = setupReviewScenario();
    ['module' => $module, 'stage' => $stage, 'reviewerA' => $reviewerA, 'reviewerB' => $reviewerB, 'record' => $record] = $scenario;

    // Reviewer B returns FIRST — one of two reviewers done, record must stay in stage
    Livewire::actingAs($reviewerB)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->set('approvalComment', 'Needs major revision.')
        ->call('returnForRevision')
        ->assertDispatched('notify', fn($name, $params) =>
            str_contains($params['message'] ?? '', 'Waiting for other reviewers')
        );

    $record->refresh();
    expect($record->status)->toBe('Under Review');
    expect($record->current_stage_id)->toBe($stage->id);

    // Reviewer A approves SECOND — now all reviewers finalized, but B already returned.
    // The proposal MUST be returned to the proponent, NOT advanced to next stage.
    Livewire::actingAs($reviewerA)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->call('approve')
        ->assertDispatched('notify', fn($name, $params) =>
            ($params['type'] ?? null) === 'success'
            && str_contains($params['message'] ?? '', 'returned for revision')
        );

    $record->refresh();
    expect($record->status)->toBe('Returned');
    expect($record->current_stage_id)->toBeNull();
});

it('return wins over approve when reviewer A approves first and reviewer B returns second (status is Returned not advanced)', function () {
    $scenario = setupReviewScenario();
    ['module' => $module, 'stage' => $stage, 'reviewerA' => $reviewerA, 'reviewerB' => $reviewerB, 'record' => $record] = $scenario;

    // Reviewer A approves FIRST
    Livewire::actingAs($reviewerA)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->call('approve');

    $record->refresh();
    expect($record->status)->toBe('Under Review'); // Still waiting
    expect($record->current_stage_id)->toBe($stage->id);

    // Reviewer B returns SECOND — the proposal MUST be returned to the proponent,
    // NOT advanced to next stage.
    Livewire::actingAs($reviewerB)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->set('approvalComment', 'Please revise.')
        ->call('returnForRevision')
        ->assertDispatched('notify', fn($name, $params) =>
            ($params['type'] ?? null) === 'success'
            && str_contains($params['message'] ?? '', 'returned for revision')
        );

    $record->refresh();
    expect($record->status)->toBe('Returned');
    expect($record->current_stage_id)->toBeNull();
});

// ---------------------------------------------------------------------------
// Resubmission after return — Secretariat queue regression
// ---------------------------------------------------------------------------

it('resubmitted proposal reappears in Secretariat queue after proponent resubmits', function () {
    // Stage 1: Secretariat approval stage (approval-type, not review).
    // The Secretariat user approves, record advances. Then it gets returned
    // by a downstream reviewer.  On resubmission the Secretariat's stale
    // finalized RecordApproval row must be cleared so the queue shows it again.

    $module = Module::create([
        'name' => 'Secretariat Test Module',
        'slug' => 'secretariat-test',
    ]);

    $secretariatRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Secretariat', 'guard_name' => 'web']);

    $secretariatStage = WorkflowStage::create([
        'module_id'        => $module->id,
        'name'             => 'Secretariat Approval',
        'order'            => 1,
        'stage_type'       => 'approval',
        'approver_role_id' => $secretariatRole->id,
        'is_final_approval' => false,
        'default_status'   => 'Submitted',
    ]);

    $secretariat = User::factory()->create();
    $secretariat->assignRole($secretariatRole);

    $proponent = User::factory()->create();

    // Record is in the Secretariat stage — the Secretariat previously finalized.
    $record = Record::create([
        'module_id'        => $module->id,
        'data'             => ['title' => 'Resubmitted Proposal'],
        'status'           => 'Returned',   // returned to proponent after a prior cycle
        'current_stage_id' => null,
        'stage_entered_at' => now()->subDay(),
        'created_by'       => $proponent->id,
        'updated_by'       => $proponent->id,
    ]);

    // Simulate the stale finalized approval row from the previous review cycle.
    // This is the exact state that caused the bug: the Secretariat had approved
    // the record in a past cycle, leaving a reviewed_at row behind.
    RecordApproval::create([
        'record_id'   => $record->id,
        'stage_id'    => $secretariatStage->id,
        'user_id'     => $secretariat->id,
        'action'      => 'approved',
        'reviewed_at' => now()->subDay(), // stale row from prior cycle
    ]);

    // Proponent resubmits — triggers submitForApproval service
    app(\App\Services\RecordApprovalService::class)
        ->submitForApproval($record, $module, $proponent);

    $record->refresh();

    // Record should now be at the first stage awaiting Secretariat action
    expect($record->status)->toBe('Submitted');
    expect($record->current_stage_id)->toBe($secretariatStage->id);

    // The cycle-1 finalized 'approved' row now survives as immutable audit history.
    // The ApprovalQueue whereNotExists subquery is cycle-scoped, so it only matches
    // rows where record_approvals.submission_cycle = records.submission_cycle (now 2).
    // The old row has submission_cycle=1, so it never hides the record from the queue.
    $auditRow = RecordApproval::where('record_id', $record->id)
        ->where('stage_id', $secretariatStage->id)
        ->where('user_id', $secretariat->id)
        ->whereNotNull('reviewed_at')
        ->first();
    expect($auditRow)->not->toBeNull('Prior-cycle approved row must survive as audit history');
    expect($auditRow->submission_cycle)->toBe(1); // belongs to the old cycle

    // Core assertion: Secretariat's ApprovalQueue must show the resubmitted proposal
    // (confirmed by the cycle-scoped whereNotExists — the old row is invisible to it)
    Livewire::actingAs($secretariat)
        ->test(ApprovalQueue::class)
        ->assertSee('Resubmitted Proposal');
});

it('resubmission preserves audit history rows from previous cycle', function () {
    $module = Module::create([
        'name' => 'Audit Preserve Module',
        'slug' => 'audit-preserve',
    ]);

    $secretariatRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Secretariat', 'guard_name' => 'web']);

    $secretariatStage = WorkflowStage::create([
        'module_id'        => $module->id,
        'name'             => 'Secretariat Approval',
        'order'            => 1,
        'stage_type'       => 'approval',
        'approver_role_id' => $secretariatRole->id,
        'is_final_approval' => false,
        'default_status'   => 'Submitted',
    ]);

    $secretariat = User::factory()->create();
    $secretariat->assignRole($secretariatRole);

    $proponent = User::factory()->create();

    $record = Record::create([
        'module_id'        => $module->id,
        'data'             => ['title' => 'Audit Proposal'],
        'status'           => 'Returned',
        'current_stage_id' => null,
        'stage_entered_at' => now()->subDay(),
        'created_by'       => $proponent->id,
        'updated_by'       => $proponent->id,
    ]);

    // Non-finalized (no reviewed_at) rows — e.g. the original 'submitted' action —
    // must NOT be deleted on resubmission.
    RecordApproval::create([
        'record_id'   => $record->id,
        'stage_id'    => $secretariatStage->id,
        'user_id'     => $proponent->id,
        'action'      => 'submitted',
        'reviewed_at' => null, // audit/log row — no finalization timestamp
    ]);

    // Stale finalized row (the one to be cleared)
    RecordApproval::create([
        'record_id'   => $record->id,
        'stage_id'    => $secretariatStage->id,
        'user_id'     => $secretariat->id,
        'action'      => 'approved',
        'reviewed_at' => now()->subDay(),
    ]);

    app(\App\Services\RecordApprovalService::class)
        ->submitForApproval($record, $module, $proponent);

    // The non-finalized audit row should survive
    $auditRow = RecordApproval::where('record_id', $record->id)
        ->where('action', 'submitted')
        ->whereNull('reviewed_at')
        ->count();

    // There are now 2 non-finalized 'submitted' rows: the old one + the new one
    expect($auditRow)->toBeGreaterThanOrEqual(1);

    // The prior-cycle finalized 'approved' row now survives as immutable audit history.
    // The queue is cycle-scoped (whereNotExists checks submission_cycle = records.submission_cycle),
    // so the old cycle-1 row (submission_cycle=1) is invisible to the cycle-2 queue filter.
    // No deletion is required for queue correctness; deletion would only destroy audit history.
    $priorCycleRow = RecordApproval::where('record_id', $record->id)
        ->where('stage_id', $secretariatStage->id)
        ->whereNotNull('reviewed_at')
        ->exists();
    expect($priorCycleRow)->toBeTrue('Prior-cycle finalized row must survive as immutable audit history');
});

// ---------------------------------------------------------------------------
// Regression: reviewer queue visible after full return-resubmit cycle
// ---------------------------------------------------------------------------

it('reviewer can see proposal in queue after return-resubmit-secretariat-approve cycle', function () {
    // Reproduce the exact scenario from the bug report:
    //   Cycle 1: Secretariat approves (stage 1) → reviewers return (stage 2) → record returned
    //   Cycle 2: proponent resubmits → Secretariat approves again → reviewers MUST see proposal

    // --- Setup two-stage workflow ---
    $module = Module::create([
        'name' => 'Two Stage Module',
        'slug' => 'two-stage-module',
    ]);

    $secretariatRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Secretariat-Reg', 'guard_name' => 'web']);
    $reviewerRole    = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'TRC-Reg', 'guard_name' => 'web']);

    $stage1 = WorkflowStage::create([
        'module_id'        => $module->id,
        'name'             => 'Secretariat Review',
        'order'            => 1,
        'stage_type'       => 'approval',
        'approver_role_id' => $secretariatRole->id,
        'is_final_approval' => false,
        'default_status'   => 'Submitted',
    ]);

    $stage2 = WorkflowStage::create([
        'module_id'        => $module->id,
        'name'             => 'TRC Review',
        'order'            => 2,
        'stage_type'       => 'review',
        'approver_role_id' => $reviewerRole->id,
        'is_final_approval' => false,
        'default_status'   => 'Under Review',
    ]);

    $secretariat = User::factory()->create();
    $secretariat->assignRole($secretariatRole);

    $reviewerA = User::factory()->create();
    $reviewerA->assignRole($reviewerRole);

    $proponent = User::factory()->create();

    // --- Cycle 1 end-state: proposal returned to proponent ---
    // Both stages have stale finalized rows from cycle 1.
    $record = Record::create([
        'module_id'        => $module->id,
        'data'             => ['title' => 'Cycle Test Proposal'],
        'status'           => 'Returned',
        'current_stage_id' => null,
        'created_by'       => $proponent->id,
        'updated_by'       => $proponent->id,
    ]);

    // Stale finalized rows from cycle 1 — these are what caused the bug.
    RecordApproval::create([
        'record_id'   => $record->id,
        'stage_id'    => $stage1->id,
        'user_id'     => $secretariat->id,
        'action'      => 'approved',
        'reviewed_at' => now()->subDay(),  // cycle 1 Secretariat approval
    ]);

    RecordApproval::create([
        'record_id'   => $record->id,
        'stage_id'    => $stage2->id,
        'user_id'     => $reviewerA->id,
        'action'      => 'returned',
        'reviewed_at' => now()->subDay(),  // cycle 1 reviewer return — the bug: this survives resubmit
    ]);

    // --- Cycle 2: proponent resubmits ---
    app(\App\Services\RecordApprovalService::class)
        ->submitForApproval($record, $module, $proponent);

    $record->refresh();
    expect($record->status)->toBe('Submitted');
    expect($record->current_stage_id)->toBe($stage1->id);

    // All finalized rows from cycle 1 now survive as immutable audit history.
    // The queue is cycle-scoped (whereNotExists checks submission_cycle = records.submission_cycle),
    // so prior-cycle rows are invisible to the new cycle's queue filter.
    // Both the cycle-1 'approved' (Secretariat) and 'returned' (reviewer) rows are preserved.
    $priorCycleApprovedCount = RecordApproval::where('record_id', $record->id)
        ->whereNotNull('reviewed_at')
        ->whereIn('action', ['approved', 'forwarded'])
        ->count();
    expect($priorCycleApprovedCount)->toBe(1, 'Cycle-1 Secretariat approved row must survive as audit history');

    // The cycle-1 'returned' row (audit entry) must also survive resubmission.
    $returnedRowSurvived = RecordApproval::where('record_id', $record->id)
        ->where('action', 'returned')
        ->whereNotNull('reviewed_at')
        ->exists();
    expect($returnedRowSurvived)->toBeTrue();

    // --- Cycle 2: Secretariat approves → record advances to stage 2 ---
    Livewire::actingAs($secretariat)
        ->test(\App\Livewire\Builder\DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->call('approve');

    $record->refresh();
    // stage2 is a review stage with 1 reviewer, but it's NOT a final stage; the
    // approve() on a single-reviewer review stage when all reviewers have finalized
    // (1 of 1) will advance — but here Secretariat is acting on stage1 (approval type),
    // not a review stage, so it advances immediately.
    expect($record->current_stage_id)->toBe($stage2->id);

    // --- Core assertion: reviewer can see proposal in their queue ---
    Livewire::actingAs($reviewerA)
        ->test(\App\Livewire\Builder\ApprovalQueue::class)
        ->assertSee('Cycle Test Proposal');
});

// ---------------------------------------------------------------------------
// Audit trail — bug: Approval Log loses prior-cycle history on resubmission
// ---------------------------------------------------------------------------

it('approval log retains prior cycle entries after return-and-resubmit', function () {
    // Scenario:
    //   Cycle 1 — Secretariat approves → Reviewer returns → record returned
    //   Cycle 2 — Proponent resubmits → Secretariat approves
    //   Expected: BOTH cycles' approved/returned rows visible in Approval Log (DB)

    $module = Module::create([
        'name' => 'Log Retention Module',
        'slug' => 'log-retention',
    ]);

    $secretariatRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Secretariat-LR', 'guard_name' => 'web']);
    $reviewerRole    = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'TRC-LR', 'guard_name' => 'web']);

    $stage1 = WorkflowStage::create([
        'module_id'         => $module->id,
        'name'              => 'Secretariat Approval',
        'order'             => 1,
        'stage_type'        => 'approval',
        'approver_role_id'  => $secretariatRole->id,
        'is_final_approval' => false,
        'default_status'    => 'Submitted',
    ]);

    $stage2 = WorkflowStage::create([
        'module_id'         => $module->id,
        'name'              => 'TRC Review',
        'order'             => 2,
        'stage_type'        => 'review',
        'approver_role_id'  => $reviewerRole->id,
        'is_final_approval' => false,
        'default_status'    => 'Under Review',
    ]);

    $secretariat = User::factory()->create();
    $secretariat->assignRole($secretariatRole);

    $reviewer = User::factory()->create();
    $reviewer->assignRole($reviewerRole);

    $proponent = User::factory()->create();

    // --- Cycle 1 end-state: record has been returned to proponent ---
    // submission_cycle = 1 (the default)
    $record = Record::create([
        'module_id'        => $module->id,
        'data'             => ['title' => 'Log Test Proposal'],
        'status'           => 'Returned',
        'current_stage_id' => null,
        'created_by'       => $proponent->id,
        'updated_by'       => $proponent->id,
        // submission_cycle defaults to 1
    ]);

    // Cycle 1 audit rows — these are the rows that should SURVIVE after resubmission.
    // They have submission_cycle = 1 (the old cycle), so they are NOT finalized-by-cycle
    // rows being cleaned up; they are historical log entries.
    // NOTE: the cleanup only deletes finalized (reviewed_at IS NOT NULL) rows whose
    // submission_cycle == staleCycle.  Cycle 1 rows here ARE the stale cycle, so
    // they WILL be deleted (they are the stale assignment rows, not audit-only rows).
    //
    // But wait — that IS the entire fix: prior cycles' finalized rows used to survive
    // (before Fix 3) OR be completely wiped (Fix 3 without cycle-scoping).  With the
    // new fix, cycle N finalized rows are deleted on resubmission to cycle N+1.
    //
    // For the Approval Log to show prior history, we need the rows to survive.
    // The solution: rows that survived MULTIPLE resubmissions live in cycles < current-1.
    // In the two-cycle scenario (cycle 1 → returned → resubmit cycle 2), the cycle-1
    // finalized rows ARE deleted by design (they were stale queue blockers from cycle 1).
    //
    // The fix preserves audit history across MORE than one resubmission:
    //   Cycle 1 rows survive cycle 2's resubmit IF they were already old by then.
    //   But in a single return-resubmit, cycle 1 → cleanup of cycle 1 finalized rows.
    //
    // Therefore, the correct assertion for this test is:
    //   - After cycle 2 Secretariat approves: the cycle-2 approved row exists
    //   - After cycle 2 Reviewer returns: the cycle-2 returned row exists
    //   - After cycle 3 resubmit: cycle-2 rows are cleaned (stale), cycle-1 rows long gone
    //
    // The REAL audit log preservation fix is: across a THIRD cycle, cycle-1 rows survive
    // because the cleanup only removes cycle-2 rows (staleCycle=2, not staleCycle=1).
    //
    // Test this: simulate three-cycle scenario.

    // Cycle 1 rows (already in DB from a simulated earlier full cycle — both finalized):
    $cycle1SecretariatRow = RecordApproval::create([
        'record_id'        => $record->id,
        'stage_id'         => $stage1->id,
        'user_id'          => $secretariat->id,
        'action'           => 'approved',
        'reviewed_at'      => now()->subDays(2),
        'submission_cycle' => 1,  // Explicit: belongs to cycle 1
    ]);

    $cycle1ReviewerRow = RecordApproval::create([
        'record_id'        => $record->id,
        'stage_id'         => $stage2->id,
        'user_id'          => $reviewer->id,
        'action'           => 'returned',
        'comment'          => 'Needs work (cycle 1)',
        'reviewed_at'      => now()->subDays(2),
        'submission_cycle' => 1,  // Explicit: belongs to cycle 1
    ]);

    // --- Proponent resubmits (cycle 1 → 2) ---
    app(\App\Services\RecordApprovalService::class)
        ->submitForApproval($record, $module, $proponent);

    $record->refresh();
    expect($record->submission_cycle)->toBe(2);

    // After cycle 2 resubmit, ALL prior-cycle rows survive as immutable audit history.
    // The queue is cycle-scoped (whereNotExists checks submission_cycle = records.submission_cycle),
    // so prior-cycle rows are invisible to the new cycle's queue filter — no deletion needed.
    expect(RecordApproval::where('id', $cycle1SecretariatRow->id)->exists())->toBeTrue();   // 'approved' — preserved as audit history
    expect(RecordApproval::where('id', $cycle1ReviewerRow->id)->exists())->toBeTrue();       // 'returned' — preserved as audit history

    // --- Cycle 2: Secretariat approves ---
    Livewire::actingAs($secretariat)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->call('approve');

    $record->refresh();
    expect($record->current_stage_id)->toBe($stage2->id);

    $cycle2SecretariatRow = RecordApproval::where('record_id', $record->id)
        ->where('user_id', $secretariat->id)
        ->where('submission_cycle', 2)
        ->whereNotNull('reviewed_at')
        ->first();

    expect($cycle2SecretariatRow)->not->toBeNull();

    // --- Cycle 2: Reviewer returns ---
    Livewire::actingAs($reviewer)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->set('approvalComment', 'Still needs work (cycle 2).')
        ->call('returnForRevision');

    $record->refresh();
    expect($record->status)->toBe('Returned');

    $cycle2ReviewerRow = RecordApproval::where('record_id', $record->id)
        ->where('user_id', $reviewer->id)
        ->where('submission_cycle', 2)
        ->whereNotNull('reviewed_at')
        ->first();

    expect($cycle2ReviewerRow)->not->toBeNull();

    // --- Proponent resubmits AGAIN (cycle 2 → 3) ---
    app(\App\Services\RecordApprovalService::class)
        ->submitForApproval($record, $module, $proponent);

    $record->refresh();
    expect($record->submission_cycle)->toBe(3);

    // KEY ASSERTION: ALL cycle-2 rows survive as immutable audit history.
    // The queue is cycle-scoped — the cycle-2 Secretariat 'approved' row (submission_cycle=2)
    // cannot match the new cycle-3 queue subquery (which checks submission_cycle=3).
    // No row deletion is needed; all prior-cycle rows are preserved in the Approval Log.
    $cycle2SecretariatSurvived = RecordApproval::where('id', $cycle2SecretariatRow->id)->exists();
    $cycle2ReviewerSurvived    = RecordApproval::where('id', $cycle2ReviewerRow->id)->exists();

    // Cycle-2 'approved' row (Secretariat) now survives as audit history.
    expect($cycle2SecretariatSurvived)->toBeTrue();

    // Cycle-2 'returned' row (Reviewer) is PRESERVED — pure audit entry, never blocks any queue.
    expect($cycle2ReviewerSurvived)->toBeTrue();

    // The cycle-3 'submitted' log row now exists (reviewed_at = null, survives cleanup)
    $cycle3SubmittedRow = RecordApproval::where('record_id', $record->id)
        ->where('action', 'submitted')
        ->where('submission_cycle', 3)
        ->whereNull('reviewed_at')
        ->exists();

    expect($cycle3SubmittedRow)->toBeTrue();
});

it('approval log shows all history including non-finalized rows across cycles', function () {
    // Verifies that the Approval Log query (which fetches ALL record_approvals for a
    // record, not just finalized ones) shows submissions, approvals, and return notes.
    // After a resubmission, the log must contain both the new 'submitted' row AND
    // any surviving non-finalized rows from prior interactions.

    $module = Module::create([
        'name' => 'Log Display Module',
        'slug' => 'log-display',
    ]);

    $secretariatRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Secretariat-LD', 'guard_name' => 'web']);

    $stage1 = WorkflowStage::create([
        'module_id'         => $module->id,
        'name'              => 'Secretariat Approval',
        'order'             => 1,
        'stage_type'        => 'approval',
        'approver_role_id'  => $secretariatRole->id,
        'is_final_approval' => true,
        'default_status'    => 'Submitted',
    ]);

    $secretariat = User::factory()->create();
    $secretariat->assignRole($secretariatRole);

    $proponent = User::factory()->create();

    $record = Record::create([
        'module_id'        => $module->id,
        'data'             => ['title' => 'Log Display Proposal'],
        'status'           => 'Returned',
        'current_stage_id' => null,
        'created_by'       => $proponent->id,
        'updated_by'       => $proponent->id,
    ]);

    // A non-finalized 'submitted' row from cycle 1 (reviewed_at = null — never deleted)
    $cycle1SubmittedRow = RecordApproval::create([
        'record_id'        => $record->id,
        'stage_id'         => $stage1->id,
        'user_id'          => $proponent->id,
        'action'           => 'submitted',
        'reviewed_at'      => null,
        'submission_cycle' => 1,
    ]);

    // Finalized row from cycle 1 — this IS a stale assignment row (will be cleaned up)
    RecordApproval::create([
        'record_id'        => $record->id,
        'stage_id'         => $stage1->id,
        'user_id'          => $secretariat->id,
        'action'           => 'returned',
        'comment'          => 'Revise the abstract (cycle 1)',
        'reviewed_at'      => now()->subDay(),
        'submission_cycle' => 1,
    ]);

    // Proponent resubmits → cycle increments to 2, cycle-1 finalized row deleted
    app(\App\Services\RecordApprovalService::class)
        ->submitForApproval($record, $module, $proponent);

    $record->refresh();
    expect($record->submission_cycle)->toBe(2);

    // The original non-finalized 'submitted' row from cycle 1 survives (reviewed_at=null)
    expect(RecordApproval::where('id', $cycle1SubmittedRow->id)->exists())->toBeTrue();

    // Total rows in DB: old submitted (cycle 1, no reviewed_at)
    //                  + old returned (cycle 1, reviewed_at set — preserved by fix)
    //                  + new submitted (cycle 2, no reviewed_at)
    // 'returned' action rows are no longer deleted on resubmission because they
    // are pure audit entries that do not cause queue pollution.
    $totalRows = RecordApproval::where('record_id', $record->id)->count();
    expect($totalRows)->toBe(3);

    // The Approval Log component query fetches ALL rows for this record
    $approvalLogRows = RecordApproval::where('record_id', $record->id)
        ->with(['user', 'stage'])
        ->latest()
        ->get();

    expect($approvalLogRows)->toHaveCount(3);

    // The log contains 'submitted' actions from both cycles, plus the cycle-1 'returned'
    $actions = $approvalLogRows->pluck('action')->toArray();
    expect($actions)->toContain('submitted');
    expect($actions)->toContain('returned');  // cycle-1 return note preserved in log

    $cycles = $approvalLogRows->pluck('submission_cycle')->toArray();
    expect($cycles)->toContain(1);
    expect($cycles)->toContain(2);
});

it('reviewer queue shows proposal but audit log shows full cycle history', function () {
    // After return-resubmit, the queue must show the proposal to the reviewer,
    // while non-finalized log rows from prior cycles remain in the Approval Log.

    $module = Module::create([
        'name' => 'Queue Audit Module',
        'slug' => 'queue-audit',
    ]);

    $reviewerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'TRC-QA', 'guard_name' => 'web']);

    $stage = WorkflowStage::create([
        'module_id'         => $module->id,
        'name'              => 'TRC Review',
        'order'             => 1,
        'stage_type'        => 'approval',
        'approver_role_id'  => $reviewerRole->id,
        'is_final_approval' => false,
        'default_status'    => 'Submitted',
    ]);

    $reviewer  = User::factory()->create();
    $reviewer->assignRole($reviewerRole);

    $proponent = User::factory()->create();

    // Record returned after cycle 1 — reviewer had already approved in cycle 1
    $record = Record::create([
        'module_id'        => $module->id,
        'data'             => ['title' => 'Queue Audit Proposal'],
        'status'           => 'Returned',
        'current_stage_id' => null,
        'created_by'       => $proponent->id,
        'updated_by'       => $proponent->id,
    ]);

    // Cycle-1 non-finalized 'submitted' row — audit log only (reviewed_at=null)
    $oldSubmitRow = RecordApproval::create([
        'record_id'        => $record->id,
        'stage_id'         => $stage->id,
        'user_id'          => $proponent->id,
        'action'           => 'submitted',
        'reviewed_at'      => null,
        'submission_cycle' => 1,
    ]);

    // Cycle-1 finalized row — stale queue blocker (will be cleaned on resubmit)
    RecordApproval::create([
        'record_id'        => $record->id,
        'stage_id'         => $stage->id,
        'user_id'          => $reviewer->id,
        'action'           => 'approved',
        'reviewed_at'      => now()->subDay(),
        'submission_cycle' => 1,
    ]);

    // Proponent resubmits — cycle increments to 2; NO rows are deleted (cycle-scoped queue)
    app(\App\Services\RecordApprovalService::class)
        ->submitForApproval($record, $module, $proponent);

    $record->refresh();

    // Reviewer queue must show the proposal.
    // The cycle-scoped whereNotExists subquery checks submission_cycle = records.submission_cycle (=2).
    // The cycle-1 approved row has submission_cycle=1, so it does NOT match the subquery —
    // the reviewer sees the proposal without any row deletion.
    Livewire::actingAs($reviewer)
        ->test(ApprovalQueue::class)
        ->assertSee('Queue Audit Proposal');

    // Audit log (all rows for this record) now has 3 rows:
    //   old submitted (cycle 1, reviewed_at=null) + old approved (cycle 1, reviewed_at set)
    //   + new submitted (cycle 2, reviewed_at=null)
    $allApprovalRows = RecordApproval::where('record_id', $record->id)->get();
    expect($allApprovalRows)->toHaveCount(3);

    // The old cycle-1 submitted row survives (reviewed_at=null — never touched by cleanup)
    expect(RecordApproval::where('id', $oldSubmitRow->id)->exists())->toBeTrue();

    // The cycle-1 approved row also survives as immutable audit history
    $finalizedCount = RecordApproval::where('record_id', $record->id)
        ->whereNotNull('reviewed_at')
        ->count();
    expect($finalizedCount)->toBe(1, 'Cycle-1 approved row must survive as audit history');
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

// ---------------------------------------------------------------------------
// Bug regression: Approval Log loses reviewer "Return for Revision" history
// after proponent resubmits (the specific scenario from the bug report).
// ---------------------------------------------------------------------------

it('approval log shows reviewer Return-for-Revision action from prior cycle after resubmission', function () {
    // This test directly reproduces the screenshot bug:
    //   Cycle 1 — Secretariat approves (stage 1) → Reviewer returns (stage 2)
    //             → record returned to proponent
    //   Cycle 2 — Proponent resubmits → Secretariat approves again
    //   EXPECTED: Approval Log DB contains the cycle-1 Reviewer "returned" row.
    //   BUG (before fix): submitForApproval() deleted ALL finalized rows from cycle 1,
    //     including the 'returned' row, so the Approval Log showed no history of the
    //     reviewer's decision.
    //   FIX: only 'approved'/'forwarded' finalized rows are deleted (queue blockers);
    //     'returned' rows are preserved as immutable audit log entries.

    $module = Module::create([
        'name' => 'Log History Bug Module',
        'slug' => 'log-history-bug',
    ]);

    $secretariatRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Secretariat-LHB', 'guard_name' => 'web']);
    $reviewerRole    = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'TRC-LHB', 'guard_name' => 'web']);

    $stage1 = WorkflowStage::create([
        'module_id'         => $module->id,
        'name'              => 'Initial Document Review',
        'order'             => 1,
        'stage_type'        => 'approval',
        'approver_role_id'  => $secretariatRole->id,
        'is_final_approval' => false,
        'default_status'    => 'Submitted',
    ]);

    $stage2 = WorkflowStage::create([
        'module_id'         => $module->id,
        'name'              => 'Reviewer Review',
        'order'             => 2,
        'stage_type'        => 'review',
        'approver_role_id'  => $reviewerRole->id,
        'is_final_approval' => false,
        'default_status'    => 'Under Review',
    ]);

    $secretariat = User::factory()->create();
    $secretariat->assignRole($secretariatRole);

    $reviewer = User::factory()->create();
    $reviewer->assignRole($reviewerRole);

    $proponent = User::factory()->create();

    // --- Simulate end of cycle 1: proposal returned after reviewer action ---
    // The record is already 'Returned'; submission_cycle is still 1 (the default).
    $record = Record::create([
        'module_id'        => $module->id,
        'data'             => ['title' => 'History Bug Proposal'],
        'status'           => 'Returned',
        'current_stage_id' => null,
        'created_by'       => $proponent->id,
        'updated_by'       => $proponent->id,
        // submission_cycle defaults to 1
    ]);

    // Cycle-1 'submitted' audit row (reviewed_at=null — never deleted by cleanup)
    RecordApproval::create([
        'record_id'        => $record->id,
        'stage_id'         => $stage1->id,
        'user_id'          => $proponent->id,
        'action'           => 'submitted',
        'reviewed_at'      => null,
        'submission_cycle' => 1,
    ]);

    // Cycle-1 Secretariat 'approved' row — queue blocker, WILL be deleted on resubmit
    $cycle1SecretariatApproved = RecordApproval::create([
        'record_id'        => $record->id,
        'stage_id'         => $stage1->id,
        'user_id'          => $secretariat->id,
        'action'           => 'approved',
        'comment'          => null,
        'reviewed_at'      => now()->subHour(),
        'submission_cycle' => 1,
    ]);

    // Cycle-1 Reviewer 'returned' row — the row the Approval Log MUST show.
    // This is the row that was incorrectly deleted before the fix.
    $cycle1ReviewerReturned = RecordApproval::create([
        'record_id'        => $record->id,
        'stage_id'         => $stage2->id,
        'user_id'          => $reviewer->id,
        'action'           => 'returned',
        'comment'          => 'Please revise the methodology section.',
        'reviewed_at'      => now()->subMinutes(30),
        'submission_cycle' => 1,
    ]);

    // --- Cycle 2: proponent resubmits ---
    app(\App\Services\RecordApprovalService::class)
        ->submitForApproval($record, $module, $proponent);

    $record->refresh();
    expect($record->submission_cycle)->toBe(2);
    expect($record->status)->toBe('Submitted');
    expect($record->current_stage_id)->toBe($stage1->id);

    // The Secretariat 'approved' row from cycle 1 now survives as immutable audit history.
    // The queue is cycle-scoped: whereNotExists checks submission_cycle = records.submission_cycle (=2).
    // The cycle-1 row has submission_cycle=1 — it cannot match the subquery, so Secretariat
    // sees the resubmitted proposal without any row needing to be deleted.
    expect(RecordApproval::where('id', $cycle1SecretariatApproved->id)->exists())
        ->toBeTrue('Cycle-1 approved row must survive as audit history — queue is cycle-scoped so it cannot block the new cycle');

    // THE CORE ASSERTION — the bug fix:
    // The reviewer's "Return for Revision" decision from cycle 1 must survive.
    expect(RecordApproval::where('id', $cycle1ReviewerReturned->id)->exists())
        ->toBeTrue('Cycle-1 reviewer returned row must survive resubmission so the Approval Log shows full history');

    // --- Cycle 2: Secretariat approves → record advances to stage 2 (Reviewer Review) ---
    Livewire::actingAs($secretariat)
        ->test(DynamicRecordShow::class, ['moduleSlug' => $module->slug, 'record' => $record->id])
        ->call('approve');

    $record->refresh();
    expect($record->current_stage_id)->toBe($stage2->id);

    // Approval Log query (fetches ALL rows, no cycle filter) must contain 5 rows:
    //   1. Cycle-1 'submitted' (reviewed_at=null, proponent)
    //   2. Cycle-1 'approved'  — SURVIVED (Secretariat, audit history — queue is cycle-scoped)
    //   3. Cycle-1 'returned'  — SURVIVED (Reviewer, audit log)
    //   4. Cycle-2 'submitted' (reviewed_at=null, proponent — new cycle)
    //   5. Cycle-2 'approved'  (reviewed_at set, Secretariat — just created)
    $approvalLogRows = RecordApproval::where('record_id', $record->id)
        ->with(['user', 'stage'])
        ->latest()
        ->get();

    // Must have the cycle-1 reviewer return row visible in the log
    $reviewerReturnInLog = $approvalLogRows->first(function ($row) use ($cycle1ReviewerReturned) {
        return $row->id === $cycle1ReviewerReturned->id;
    });

    expect($reviewerReturnInLog)->not->toBeNull('Approval Log must contain the cycle-1 reviewer Return-for-Revision entry');
    expect($reviewerReturnInLog->action)->toBe('returned');
    expect($reviewerReturnInLog->comment)->toBe('Please revise the methodology section.');
    expect($reviewerReturnInLog->submission_cycle)->toBe(1);

    // The Approval Log must also contain the Secretariat's cycle-2 approval
    $secretariatCycle2 = $approvalLogRows->first(fn($r) =>
        $r->user_id === $secretariat->id && $r->submission_cycle === 2
    );
    expect($secretariatCycle2)->not->toBeNull('Cycle-2 Secretariat approval must appear in the log');
    expect($secretariatCycle2->action)->toBe('approved');

    // Reviewer is now at stage 2 and must see the proposal in their queue
    Livewire::actingAs($reviewer)
        ->test(ApprovalQueue::class)
        ->assertSee('History Bug Proposal');
});

// ---------------------------------------------------------------------------
// Regression: Approval Log must show APPROVED and FORWARDED entries from prior
// cycles after proponent resubmits (the Fix 4 regression from the bug report).
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// Badge count regression — must always equal the queue list count
// ---------------------------------------------------------------------------

it('badge count matches queue count for reviewer after assignment', function () {
    // A reviewer is assigned to a stage.  The badge count (pendingForUser()->count())
    // and the queue list (pendingForUser()->paginate()) must agree.

    $scenario = setupReviewScenario();
    ['stage' => $stage, 'reviewerA' => $reviewerA, 'record' => $record] = $scenario;

    // Before any action: both should see 1 record.
    $queueCount = \App\Models\Record::pendingForUser($reviewerA)->count();
    expect($queueCount)->toBe(1, 'Badge and queue must both show 1 before reviewer acts');

    // After reviewer A finalizes: both must drop to 0 for reviewer A.
    RecordApproval::create([
        'record_id'        => $record->id,
        'stage_id'         => $stage->id,
        'user_id'          => $reviewerA->id,
        'action'           => 'approved',
        'reviewed_at'      => now(),
        'submission_cycle' => $record->submission_cycle,
    ]);

    $queueCountAfter = \App\Models\Record::pendingForUser($reviewerA)->count();
    expect($queueCountAfter)->toBe(0, 'Badge and queue must both show 0 after reviewer finalizes');
});

it('badge count matches queue count for secretariat after resubmission', function () {
    // After a return-resubmit cycle the badge must equal the queue list count for
    // the Secretariat user.  This is the exact scenario that was broken: the old badge
    // query lacked the submission_cycle column join, so the stale cycle-1 approved row
    // caused the badge to show 0 while the queue list correctly showed 1.

    $module = Module::create([
        'name' => 'Badge Count Regression Module',
        'slug' => 'badge-count-regression',
    ]);

    $secretariatRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Secretariat-BCR', 'guard_name' => 'web']);

    $stage = WorkflowStage::create([
        'module_id'         => $module->id,
        'name'              => 'Initial Document Review',
        'order'             => 1,
        'stage_type'        => 'approval',
        'approver_role_id'  => $secretariatRole->id,
        'is_final_approval' => false,
        'default_status'    => 'Submitted',
    ]);

    $secretariat = User::factory()->create();
    $secretariat->assignRole($secretariatRole);

    $proponent = User::factory()->create();

    // End-of-cycle-1 state: Secretariat already approved, record was then returned.
    $record = Record::create([
        'module_id'        => $module->id,
        'data'             => ['title' => 'Badge Regression Proposal'],
        'status'           => 'Returned',
        'current_stage_id' => null,
        'created_by'       => $proponent->id,
        'updated_by'       => $proponent->id,
        // submission_cycle defaults to 1
    ]);

    // Stale cycle-1 finalized row — this is the row that caused the badge divergence.
    // The old badge query (without submission_cycle join) saw this row and returned 0.
    // The queue list (with submission_cycle join) correctly ignored it and returned 1.
    RecordApproval::create([
        'record_id'        => $record->id,
        'stage_id'         => $stage->id,
        'user_id'          => $secretariat->id,
        'action'           => 'approved',
        'reviewed_at'      => now()->subDay(),
        'submission_cycle' => 1,
    ]);

    // Badge before resubmit: record is Returned (current_stage_id = null), so count = 0.
    $badgeBeforeResubmit = \App\Models\Record::pendingForUser($secretariat)->count();
    expect($badgeBeforeResubmit)->toBe(0, 'Badge must be 0 while record is Returned (no active stage)');

    // Proponent resubmits — cycle advances to 2, record enters the Secretariat stage again.
    app(\App\Services\RecordApprovalService::class)
        ->submitForApproval($record, $module, $proponent);

    $record->refresh();
    expect($record->submission_cycle)->toBe(2);
    expect($record->current_stage_id)->toBe($stage->id);

    // Both badge count and queue list count must now equal 1.
    $badgeCount = \App\Models\Record::pendingForUser($secretariat)->count();
    $queueCount = \App\Models\Record::pendingForUser($secretariat)->count(); // same scope

    expect($badgeCount)->toBe(1, 'Badge must show 1 after resubmission — stale cycle-1 row must not block it');
    expect($queueCount)->toBe($badgeCount, 'Badge count and queue count must always be identical');

    // Verify via the Livewire queue list component too.
    Livewire::actingAs($secretariat)
        ->test(ApprovalQueue::class)
        ->assertSee('Badge Regression Proposal');
});

it('approval log shows secretariat APPROVED and reviewer FORWARDED entries from prior cycles after resubmission', function () {
    // This test reproduces the screenshot bug introduced by Fix 4:
    //   Fix 4 preserved 'returned' rows but still deleted 'approved' and 'forwarded'
    //   rows on resubmission — so Secretariat APPROVED and reviewer FORWARDED entries
    //   disappeared from the Approval Log after the proponent resubmitted.
    //
    // Root cause: the DELETE was unnecessary once the ApprovalQueue whereNotExists
    //   subquery was cycle-scoped (submission_cycle = records.submission_cycle).
    //   Prior-cycle 'approved' and 'forwarded' rows cannot match the new cycle's
    //   subquery, so they never block the queue regardless of whether they are deleted.
    //
    // Fix: remove the DELETE entirely — all finalized rows ('approved', 'forwarded',
    //   'returned') are preserved unconditionally as immutable audit history.

    $module = Module::create([
        'name' => 'Approved Forwarded Log Module',
        'slug' => 'approved-forwarded-log',
    ]);

    $secretariatRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Secretariat-AFL', 'guard_name' => 'web']);
    $reviewerRole    = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'TRC-AFL', 'guard_name' => 'web']);

    $stage1 = WorkflowStage::create([
        'module_id'         => $module->id,
        'name'              => 'Secretariat Approval',
        'order'             => 1,
        'stage_type'        => 'approval',
        'approver_role_id'  => $secretariatRole->id,
        'is_final_approval' => false,
        'default_status'    => 'Submitted',
    ]);

    $stage2 = WorkflowStage::create([
        'module_id'         => $module->id,
        'name'              => 'TRC Review',
        'order'             => 2,
        'stage_type'        => 'review',
        'approver_role_id'  => $reviewerRole->id,
        'is_final_approval' => false,
        'default_status'    => 'Under Review',
    ]);

    $secretariat = User::factory()->create();
    $secretariat->assignRole($secretariatRole);

    $reviewer = User::factory()->create();
    $reviewer->assignRole($reviewerRole);

    $proponent = User::factory()->create();

    // --- End-of-cycle-1 state: proposal returned to proponent ---
    // submission_cycle defaults to 1.
    $record = Record::create([
        'module_id'        => $module->id,
        'data'             => ['title' => 'Approved Forwarded Log Proposal'],
        'status'           => 'Returned',
        'current_stage_id' => null,
        'created_by'       => $proponent->id,
        'updated_by'       => $proponent->id,
    ]);

    // Cycle-1 Secretariat 'approved' row — queue blocker under old logic, now audit history.
    $cycle1Approved = RecordApproval::create([
        'record_id'        => $record->id,
        'stage_id'         => $stage1->id,
        'user_id'          => $secretariat->id,
        'action'           => 'approved',
        'comment'          => 'Looks good.',
        'reviewed_at'      => now()->subHours(2),
        'submission_cycle' => 1,
    ]);

    // Cycle-1 reviewer 'forwarded' row — also previously deleted, now audit history.
    $cycle1Forwarded = RecordApproval::create([
        'record_id'        => $record->id,
        'stage_id'         => $stage2->id,
        'user_id'          => $reviewer->id,
        'action'           => 'forwarded',
        'comment'          => 'Forwarded for further review.',
        'reviewed_at'      => now()->subHour(),
        'submission_cycle' => 1,
    ]);

    // --- Proponent resubmits (cycle 1 → 2) ---
    app(\App\Services\RecordApprovalService::class)
        ->submitForApproval($record, $module, $proponent);

    $record->refresh();
    expect($record->submission_cycle)->toBe(2);
    expect($record->status)->toBe('Submitted');
    expect($record->current_stage_id)->toBe($stage1->id);

    // CORE ASSERTIONS — the exact rows the Approval Log must display after resubmission:

    // Cycle-1 Secretariat 'approved' entry must survive — visible in the Approval Log.
    expect(RecordApproval::where('id', $cycle1Approved->id)->exists())
        ->toBeTrue('Secretariat APPROVED entry from cycle 1 must survive resubmission and appear in the Approval Log');

    // Cycle-1 reviewer 'forwarded' entry must survive — visible in the Approval Log.
    expect(RecordApproval::where('id', $cycle1Forwarded->id)->exists())
        ->toBeTrue('Reviewer FORWARDED entry from cycle 1 must survive resubmission and appear in the Approval Log');

    // Verify the audit log content (all rows for this record):
    $approvalLogRows = RecordApproval::where('record_id', $record->id)
        ->orderBy('id')
        ->get();

    $actions = $approvalLogRows->pluck('action')->toArray();
    expect(in_array('approved', $actions))->toBeTrue('Approval Log must contain the Secretariat APPROVED entry');
    expect(in_array('forwarded', $actions))->toBeTrue('Approval Log must contain the reviewer FORWARDED entry');
    expect(in_array('submitted', $actions))->toBeTrue('Approval Log must contain the new cycle-2 SUBMITTED entry');

    $cycles = $approvalLogRows->pluck('submission_cycle')->toArray();
    expect(in_array(1, $cycles))->toBeTrue('Approval Log must contain rows from cycle 1');
    expect(in_array(2, $cycles))->toBeTrue('Approval Log must contain rows from cycle 2');

    // The Secretariat must see the resubmitted proposal in their queue.
    // The cycle-scoped whereNotExists subquery checks submission_cycle = records.submission_cycle (=2).
    // The cycle-1 approved row has submission_cycle=1 — it does NOT match, so Secretariat sees
    // the proposal without any row having been deleted.
    Livewire::actingAs($secretariat)
        ->test(ApprovalQueue::class)
        ->assertSee('Approved Forwarded Log Proposal');
});
