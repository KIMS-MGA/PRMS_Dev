<?php

use App\Models\Module;
use App\Models\Record;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Notifications\DynamicNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    $this->module = Module::create(['name' => 'Reminder Test', 'slug' => 'reminder-test']);
    $this->user   = User::factory()->create();
});

it('sends reminder notifications to all configured recipient types', function () {
    $roleUser  = User::factory()->create();
    $namedUser = User::factory()->create();

    $role = Role::firstOrCreate(['name' => 'reminder-role', 'guard_name' => 'web']);
    $roleUser->assignRole($role);

    $targetDate = Carbon::today()->addDays(3)->format('Y-m-d');

    $stage = WorkflowStage::create([
        'module_id'          => $this->module->id,
        'name'               => 'Stage 1',
        'order'              => 1,
        'date_reminders_json' => [[
            'field_slug'  => 'scheduled_date',
            'days_before' => 3,
            'recipients'  => [
                ['type' => 'submitter',     'value' => ''],
                ['type' => 'role',          'value' => 'reminder-role'],
                ['type' => 'specific_user', 'value' => (string) $namedUser->id],
            ],
        ]],
    ]);

    $record = Record::create([
        'module_id'  => $this->module->id,
        'data'       => ['scheduled_date' => $targetDate],
        'status'     => 'Submitted',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $this->artisan('prms:send-date-field-reminders')
        ->assertExitCode(0);

    // submitter, role user, and named user each get a notification
    Notification::assertSentTo($this->user, DynamicNotification::class);
    Notification::assertSentTo($roleUser, DynamicNotification::class);
    Notification::assertSentTo($namedUser, DynamicNotification::class);
});

it('does not send reminders when no records match the target date', function () {
    $targetDate = Carbon::today()->addDays(3)->format('Y-m-d');
    $otherDate  = Carbon::today()->addDays(10)->format('Y-m-d'); // does not match

    WorkflowStage::create([
        'module_id'          => $this->module->id,
        'name'               => 'Stage 1',
        'order'              => 1,
        'date_reminders_json' => [[
            'field_slug'  => 'scheduled_date',
            'days_before' => 3,
            'recipients'  => [['type' => 'submitter', 'value' => '']],
        ]],
    ]);

    Record::create([
        'module_id'  => $this->module->id,
        'data'       => ['scheduled_date' => $otherDate],
        'status'     => 'Submitted',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $this->artisan('prms:send-date-field-reminders')
        ->assertExitCode(0);

    Notification::assertNothingSent();
});

it('reports when no stages have date reminders configured', function () {
    $this->artisan('prms:send-date-field-reminders')
        ->expectsOutput('No stages with date field reminders configured.')
        ->assertExitCode(0);
});
