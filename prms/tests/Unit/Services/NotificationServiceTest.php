<?php

use App\Mail\StageNotificationMail;
use App\Models\Record;
use App\Models\User;
use App\Notifications\DynamicNotification;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

// Bind this test file to the Laravel test case so facades (Mail, Notification,
// Log) are backed by a running application container.
// RefreshDatabase is NOT used — no database access occurs.
uses(TestCase::class);

// ── Test-only subclass ────────────────────────────────────────────────────────
//
// Overrides the two protected helpers that wrap static Eloquent calls so that
// tests remain fast (no DB) and fully deterministic.

class TestableNotificationService extends NotificationService
{
    /** @var array<int|string, User|null> */
    public array $userMap  = [];

    /** @var array<string, Collection> */
    public array $roleMap  = [];

    protected function findUser(mixed $id): ?User
    {
        return $this->userMap[$id] ?? null;
    }

    protected function getUsersByRole(string $roleName): Collection
    {
        return $this->roleMap[$roleName] ?? collect();
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Build a lightweight mock Record with the minimum attributes the service needs.
 */
function makeRecord(int $id = 1, int $createdBy = 10, string $moduleSlug = 'test-module'): Record
{
    $module = (object) ['slug' => $moduleSlug];

    /** @var Record $record */
    $record = Mockery::mock(Record::class)->makePartial();
    $record->id         = $id;
    $record->created_by = $createdBy;
    $record->shouldReceive('getAttribute')->with('id')->andReturn($id);
    $record->shouldReceive('getAttribute')->with('created_by')->andReturn($createdBy);
    $record->shouldReceive('getAttribute')->with('module')->andReturn($module);

    return $record;
}

/**
 * Build a mock User that accepts notify() calls without hitting the DB.
 */
function makeUser(int $id = 1, string $email = 'user@example.com'): User
{
    /** @var User $user */
    $user = Mockery::mock(User::class)->makePartial();
    $user->id    = $id;
    $user->email = $email;
    $user->shouldReceive('notify')->andReturn(null);

    return $user;
}

// ── Test cases ────────────────────────────────────────────────────────────────

it('notifies the record submitter via DynamicNotification', function () {
    Notification::fake();
    Mail::fake();

    $submitter = makeUser(10);
    $record    = makeRecord(createdBy: 10);

    $service = new TestableNotificationService();
    $service->userMap[10] = $submitter;

    $service->notifyRecipients(
        [['type' => 'submitter', 'value' => '']],
        $record,
        'Test message'
    );

    $submitter->shouldHaveReceived('notify')->once();
    Mail::assertNothingSent();
});

it('notifies all users with a given role via DynamicNotification', function () {
    Notification::fake();
    Mail::fake();

    $user1 = makeUser(1);
    $user2 = makeUser(2);

    $service = new TestableNotificationService();
    $service->roleMap['approver'] = collect([$user1, $user2]);

    $record = makeRecord();
    $service->notifyRecipients(
        [['type' => 'role', 'value' => 'approver']],
        $record,
        'Role notification'
    );

    $user1->shouldHaveReceived('notify')->once();
    $user2->shouldHaveReceived('notify')->once();
    Mail::assertNothingSent();
});

it('notifies a specific user by ID via DynamicNotification', function () {
    Notification::fake();
    Mail::fake();

    $targetUser = makeUser(42);

    $service = new TestableNotificationService();
    $service->userMap['42'] = $targetUser;

    $record = makeRecord();
    $service->notifyRecipients(
        [['type' => 'specific_user', 'value' => '42']],
        $record,
        'Specific user message'
    );

    $targetUser->shouldHaveReceived('notify')->once();
    Mail::assertNothingSent();
});

it('sends StageNotificationMail for specific_email recipients', function () {
    Notification::fake();
    Mail::fake();

    $record  = makeRecord();
    $service = new TestableNotificationService();
    $service->notifyRecipients(
        [['type' => 'specific_email', 'value' => 'external@example.com']],
        $record,
        'Email only message'
    );

    Mail::assertSent(StageNotificationMail::class, function ($mail) {
        return $mail->hasTo('external@example.com');
    });
    Notification::assertNothingSent();
});

it('handles mixed recipient types in a single call', function () {
    Notification::fake();
    Mail::fake();

    $submitter    = makeUser(10);
    $roleUser     = makeUser(20);
    $specificUser = makeUser(30);

    $service = new TestableNotificationService();
    $service->userMap[10]   = $submitter;
    $service->userMap['30'] = $specificUser;
    $service->roleMap['editor'] = collect([$roleUser]);

    $record = makeRecord(createdBy: 10);
    $service->notifyRecipients(
        [
            ['type' => 'submitter',      'value' => ''],
            ['type' => 'role',           'value' => 'editor'],
            ['type' => 'specific_user',  'value' => '30'],
            ['type' => 'specific_email', 'value' => 'ext@example.com'],
        ],
        $record,
        'Mixed message'
    );

    $submitter->shouldHaveReceived('notify')->once();
    $roleUser->shouldHaveReceived('notify')->once();
    $specificUser->shouldHaveReceived('notify')->once();
    Mail::assertSent(StageNotificationMail::class, fn ($m) => $m->hasTo('ext@example.com'));
});

it('completes without error when recipients array is empty', function () {
    Notification::fake();
    Mail::fake();

    $record  = makeRecord();
    $service = new TestableNotificationService();

    expect(fn () => $service->notifyRecipients([], $record, 'No recipients'))
        ->not->toThrow(\Throwable::class);

    Notification::assertNothingSent();
    Mail::assertNothingSent();
});

it('does not throw when a user ID resolves to null (bad ID)', function () {
    Notification::fake();
    Mail::fake();

    $service = new TestableNotificationService();
    // userMap has no entry for '999', so findUser() returns null

    $record = makeRecord();

    expect(fn () => $service->notifyRecipients(
        [['type' => 'specific_user', 'value' => '999']],
        $record,
        'Bad ID'
    ))->not->toThrow(\Throwable::class);

    Notification::assertNothingSent();
    Mail::assertNothingSent();
});

it('catches a thrown exception, logs a warning, and continues to process remaining recipients', function () {
    Notification::fake();
    Mail::fake();

    $goodUser = makeUser(5);

    // Build the bad user manually so the notify() stub throws, rather than
    // stacking a second shouldReceive() on top of the one from makeUser().
    $badUser = Mockery::mock(User::class)->makePartial();
    $badUser->id    = 6;
    $badUser->email = 'bad@example.com';
    $badUser->shouldReceive('notify')->andThrow(new \RuntimeException('SMTP failure'));

    $service = new TestableNotificationService();
    $service->userMap['6'] = $badUser;
    $service->userMap['5'] = $goodUser;

    $record = makeRecord();

    $logSpy = Log::spy();

    $service->notifyRecipients(
        [
            ['type' => 'specific_user', 'value' => '6'],
            ['type' => 'specific_user', 'value' => '5'],
        ],
        $record,
        'Batch message'
    );

    $logSpy->shouldHaveReceived('warning')->once();
    $goodUser->shouldHaveReceived('notify')->once();
});
