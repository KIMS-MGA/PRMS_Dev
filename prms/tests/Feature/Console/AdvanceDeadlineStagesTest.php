<?php

use App\Console\Commands\AdvanceDeadlineStages;
use App\Models\Module;
use App\Models\Record;
use App\Models\RecordApproval;
use App\Models\User;
use App\Models\WorkflowStage;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->module = Module::create(['name' => 'Deadline Test', 'slug' => 'deadline-test']);
    $this->user   = User::factory()->create();
});

// ── Working-day count ──────────────────────────────────────────────────────────

it('countWorkingDays preserves Mon–Fri count excluding weekends', function () {
    $command = app(AdvanceDeadlineStages::class);

    $reflection = new ReflectionMethod($command, 'countWorkingDays');
    $reflection->setAccessible(true);

    // Monday to Friday of the same week = 4 working days (Mon, Tue, Wed, Thu)
    $from = Carbon::parse('2026-06-15'); // Monday
    $to   = Carbon::parse('2026-06-19'); // Friday
    expect($reflection->invoke($command, $from, $to))->toBe(4);

    // Monday to Monday of next week = 5 working days
    $from = Carbon::parse('2026-06-15'); // Monday
    $to   = Carbon::parse('2026-06-22'); // Monday
    expect($reflection->invoke($command, $from, $to))->toBe(5);

    // Saturday to Monday = 0 working days (weekend skipped)
    $from = Carbon::parse('2026-06-13'); // Saturday
    $to   = Carbon::parse('2026-06-15'); // Monday
    expect($reflection->invoke($command, $from, $to))->toBe(0);
});

// ── Command skips records within deadline ─────────────────────────────────────

it('does not advance records that have not exceeded their deadline', function () {
    $stage = WorkflowStage::create([
        'module_id'       => $this->module->id,
        'name'            => 'Stage 1',
        'order'           => 1,
        'auto_advance_days' => 5,
        'is_final_approval' => false,
    ]);

    // Record entered stage yesterday (1 working day — within 5-day deadline)
    $record = Record::create([
        'module_id'        => $this->module->id,
        'data'             => [],
        'status'           => 'Submitted',
        'current_stage_id' => $stage->id,
        'stage_entered_at' => Carbon::now()->subDay(),
        'created_by'       => $this->user->id,
        'updated_by'       => $this->user->id,
    ]);

    $this->artisan('prms:advance-deadline-stages')
        ->assertExitCode(0);

    $record->refresh();
    expect($record->status)->toBe('Submitted')
        ->and($record->current_stage_id)->toBe($stage->id);
});

// ── Command advances records past deadline ────────────────────────────────────

it('advances a record to the next stage when deadline is exceeded', function () {
    $stage1 = WorkflowStage::create([
        'module_id'         => $this->module->id,
        'name'              => 'Stage 1',
        'order'             => 1,
        'auto_advance_days' => 1,
        'is_final_approval' => false,
    ]);
    $stage2 = WorkflowStage::create([
        'module_id'         => $this->module->id,
        'name'              => 'Stage 2',
        'order'             => 2,
        'is_final_approval' => true,
    ]);

    // Record entered stage 3 working days ago (exceeds 1-day deadline)
    $record = Record::create([
        'module_id'        => $this->module->id,
        'data'             => [],
        'status'           => 'Submitted',
        'current_stage_id' => $stage1->id,
        'stage_entered_at' => Carbon::parse('last Monday')->subWeek(), // well past deadline
        'created_by'       => $this->user->id,
        'updated_by'       => $this->user->id,
    ]);

    $this->artisan('prms:advance-deadline-stages')
        ->assertExitCode(0);

    $record->refresh();
    expect($record->current_stage_id)->toBe($stage2->id);
    $this->assertDatabaseHas('record_approvals', [
        'record_id' => $record->id,
        'stage_id'  => $stage1->id,
        'action'    => 'auto_advanced',
    ]);
});

// ── Command auto-approves at final stage ──────────────────────────────────────

it('auto-approves a record and marks it Completed when deadline exceeded at final stage', function () {
    $stage = WorkflowStage::create([
        'module_id'         => $this->module->id,
        'name'              => 'Final Stage',
        'order'             => 1,
        'auto_advance_days' => 1,
        'is_final_approval' => true,
    ]);

    $record = Record::create([
        'module_id'        => $this->module->id,
        'data'             => [],
        'status'           => 'Submitted',
        'current_stage_id' => $stage->id,
        'stage_entered_at' => Carbon::parse('last Monday')->subWeek(),
        'created_by'       => $this->user->id,
        'updated_by'       => $this->user->id,
    ]);

    $this->artisan('prms:advance-deadline-stages')
        ->assertExitCode(0);

    $record->refresh();
    expect($record->status)->toBe('Completed');
    $this->assertDatabaseHas('record_approvals', [
        'record_id' => $record->id,
        'action'    => 'auto_approved',
    ]);
});

// ── Command reports when no stages configured ─────────────────────────────────

it('reports no stages when none are configured with deadlines', function () {
    $this->artisan('prms:advance-deadline-stages')
        ->expectsOutput('No stages with deadlines configured.')
        ->assertExitCode(0);
});
