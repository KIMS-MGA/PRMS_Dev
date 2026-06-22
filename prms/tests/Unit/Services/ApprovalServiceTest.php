<?php

use App\Enums\ApprovalAction;
use App\Enums\RecordStatus;
use App\Models\Record;
use App\Models\RecordApproval;
use App\Models\RecordHistory;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Notifications\DynamicNotification;
use App\Services\ApprovalService;
use App\Services\NotificationService;
use Tests\TestCase;

// Bind to a real application container so facades work but no DB is used.
uses(TestCase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Build a minimal mock Module.
 */
function makeModule(int $id = 1, string $name = 'Test Module', string $slug = 'test-module'): object
{
    return (object) ['id' => $id, 'name' => $name, 'slug' => $slug];
}

/**
 * Build a lightweight mock Record with the minimum attributes the service needs.
 */
function makeApprovalRecord(
    int $id = 1,
    int $createdBy = 10,
    string $status = 'Draft',
    int $moduleId = 1,
    ?WorkflowStage $currentStage = null,
    ?int $currentStageId = null
): Record {
    $module = makeModule($moduleId);

    /** @var Record&Mockery\MockInterface $record */
    $record = Mockery::mock(Record::class)->makePartial();
    $record->id               = $id;
    $record->created_by       = $createdBy;
    $record->status           = $status;
    $record->module_id        = $moduleId;
    $record->current_stage_id = $currentStageId ?? ($currentStage?->id);

    $record->shouldReceive('getAttribute')->with('id')->andReturn($id);
    $record->shouldReceive('getAttribute')->with('created_by')->andReturn($createdBy);
    $record->shouldReceive('getAttribute')->with('status')->andReturn($status)->byDefault();
    $record->shouldReceive('getAttribute')->with('module_id')->andReturn($moduleId);
    $record->shouldReceive('getAttribute')->with('module')->andReturn($module);
    $record->shouldReceive('getAttribute')->with('currentStage')->andReturn($currentStage);
    $record->shouldReceive('getAttribute')->with('current_stage_id')->andReturn($currentStageId ?? ($currentStage?->id));
    $record->shouldReceive('update')->andReturn(true);

    return $record;
}

/**
 * Build a mock User that accepts notify() calls without hitting the DB.
 */
function makeApprovalUser(int $id = 1, string $email = 'user@example.com'): User
{
    /** @var User&Mockery\MockInterface $user */
    $user = Mockery::mock(User::class)->makePartial();
    $user->id    = $id;
    $user->email = $email;
    $user->shouldReceive('notify')->andReturn(null);

    return $user;
}

/**
 * Build a mock WorkflowStage.
 */
function makeStage(
    int $id = 1,
    int $moduleId = 1,
    int $order = 1,
    bool $isFinal = false,
    ?string $defaultStatus = null,
    ?int $approverRoleId = null,
    array $notifyOnEnter = [],
    array $branches = []
): WorkflowStage {
    /** @var WorkflowStage&Mockery\MockInterface $stage */
    $stage = Mockery::mock(WorkflowStage::class)->makePartial();
    $stage->id                  = $id;
    $stage->module_id           = $moduleId;
    $stage->order               = $order;
    $stage->is_final_approval   = $isFinal;
    $stage->default_status      = $defaultStatus;
    $stage->approver_role_id    = $approverRoleId;
    $stage->notify_on_enter_json = $notifyOnEnter;
    $stage->branches_json        = $branches;
    $stage->name                 = 'Stage ' . $id;

    $stage->shouldReceive('getAttribute')->with('id')->andReturn($id);
    $stage->shouldReceive('getAttribute')->with('module_id')->andReturn($moduleId);
    $stage->shouldReceive('getAttribute')->with('order')->andReturn($order);
    $stage->shouldReceive('getAttribute')->with('is_final_approval')->andReturn($isFinal);
    $stage->shouldReceive('getAttribute')->with('default_status')->andReturn($defaultStatus);
    $stage->shouldReceive('getAttribute')->with('approver_role_id')->andReturn($approverRoleId);
    $stage->shouldReceive('getAttribute')->with('notify_on_enter_json')->andReturn($notifyOnEnter);
    $stage->shouldReceive('getAttribute')->with('branches_json')->andReturn($branches);
    $stage->shouldReceive('getAttribute')->with('name')->andReturn('Stage ' . $id);

    return $stage;
}

/**
 * Build a testable ApprovalService that stubs Eloquent static calls.
 */
function makeService(
    ?NotificationService $notifications = null,
    array $stageQueryResults = [],
    ?User $creatorUser = null
): TestableApprovalService {
    $notifications ??= Mockery::mock(NotificationService::class);
    $notifications->shouldReceive('notifyRecipients')->andReturn(null)->byDefault();

    return new TestableApprovalService($notifications, $stageQueryResults, $creatorUser);
}

// ── Testable subclass ─────────────────────────────────────────────────────────

/**
 * Subclass that stubs DB-touching static calls on Eloquent models.
 * Overrides protected hook methods that the service calls for DB operations.
 */
class TestableApprovalService extends ApprovalService
{
    public array $createdApprovals = [];
    public array $createdHistories = [];

    /**
     * @param  array<int, WorkflowStage|null>  $stageQueryResults  Indexed queue of results for findFirstStage() and findNextStage()
     */
    public function __construct(
        NotificationService $notifications,
        private array $stageQueryResults = [],
        private ?User $creatorUser = null,
    ) {
        parent::__construct($notifications);
    }

    protected function findFirstStage(int $moduleId): ?WorkflowStage
    {
        return array_shift($this->stageQueryResults);
    }

    protected function findNextStage(int $moduleId, int $afterOrder): ?WorkflowStage
    {
        return array_shift($this->stageQueryResults);
    }

    protected function findStageById(int $id): ?WorkflowStage
    {
        return array_shift($this->stageQueryResults);
    }

    protected function createApproval(array $data): void
    {
        $this->createdApprovals[] = $data;
    }

    protected function createHistory(array $data): void
    {
        $this->createdHistories[] = $data;
    }

    protected function findCreator(mixed $id): ?User
    {
        return $this->creatorUser;
    }

    protected function getUsersByRole(string $roleName): \Illuminate\Support\Collection
    {
        return collect();
    }
}

// ── Tests: submit() ───────────────────────────────────────────────────────────

it('submit() happy path: Draft record is submitted, approval+history created, notifyRecipients called', function () {
    $stage = makeStage(id: 1, moduleId: 1, order: 1, notifyOnEnter: [['type' => 'role', 'value' => 'approver']]);
    $record = makeApprovalRecord(status: 'Draft', moduleId: 1);
    $user   = makeApprovalUser(id: 5);

    $notifications = Mockery::mock(NotificationService::class);
    $notifications->shouldReceive('notifyRecipients')
        ->once()
        ->with([['type' => 'role', 'value' => 'approver']], $record, Mockery::type('string'));

    $service = new TestableApprovalService($notifications, [$stage]);
    $service->submit($record, $user);

    expect($service->createdApprovals)->toHaveCount(1);
    expect($service->createdApprovals[0]['action'])->toBe(ApprovalAction::Submitted->value);
    expect($service->createdApprovals[0]['user_id'])->toBe($user->id);
    expect($service->createdHistories)->toHaveCount(1);
    expect($service->createdHistories[0]['action'])->toBe(ApprovalAction::Submitted->value);

    $record->shouldHaveReceived('update')->once();
});

it('submit() throws InvalidArgumentException when record status is not Draft or Returned', function () {
    $record = makeApprovalRecord(status: 'Submitted');
    $user   = makeApprovalUser();

    $service = makeService();

    expect(fn () => $service->submit($record, $user))
        ->toThrow(\InvalidArgumentException::class);
});

it('submit() throws RuntimeException when no WorkflowStage exists for module', function () {
    $record = makeApprovalRecord(status: 'Draft', moduleId: 1);
    $user   = makeApprovalUser();

    $service = makeService(stageQueryResults: [null]);

    expect(fn () => $service->submit($record, $user))
        ->toThrow(\RuntimeException::class);
});

it('submit() works when record status is Returned', function () {
    $stage = makeStage(id: 1, moduleId: 1, order: 1);
    $record = makeApprovalRecord(status: 'Returned', moduleId: 1);
    $user   = makeApprovalUser(id: 5);

    $service = new TestableApprovalService(
        Mockery::mock(NotificationService::class)
            ->shouldReceive('notifyRecipients')->andReturn(null)->getMock(),
        [$stage]
    );

    $service->submit($record, $user);

    expect($service->createdApprovals[0]['action'])->toBe(ApprovalAction::Submitted->value);
});

// ── Tests: approve() ─────────────────────────────────────────────────────────

it('approve() non-final: advances to next stage, approval+history created, notifyRecipients called', function () {
    $currentStage = makeStage(id: 1, moduleId: 1, order: 1, isFinal: false);
    $nextStage    = makeStage(id: 2, moduleId: 1, order: 2, defaultStatus: 'Under Review', notifyOnEnter: [['type' => 'role', 'value' => 'approver']]);

    $record = makeApprovalRecord(status: 'Submitted', moduleId: 1, currentStage: $currentStage, currentStageId: 1);
    $user   = makeApprovalUser(id: 5);

    $notifications = Mockery::mock(NotificationService::class);
    $notifications->shouldReceive('notifyRecipients')->once();

    $service = new TestableApprovalService($notifications, [$nextStage]);
    $service->approve($record, $user);

    expect($service->createdApprovals)->toHaveCount(1);
    expect($service->createdApprovals[0]['action'])->toBe(ApprovalAction::Approved->value);
    expect($service->createdHistories)->toHaveCount(1);
    expect($service->createdHistories[0]['action'])->toBe(ApprovalAction::Approved->value);

    $record->shouldHaveReceived('update')->once();
});

it('approve() final stage: record marked Completed, creator notified via DynamicNotification', function () {
    $finalStage = makeStage(id: 1, moduleId: 1, order: 1, isFinal: true);
    $creator    = makeApprovalUser(id: 10);
    $record     = makeApprovalRecord(status: 'Submitted', moduleId: 1, currentStage: $finalStage, currentStageId: 1, createdBy: 10);
    $user       = makeApprovalUser(id: 5);

    $notifications = Mockery::mock(NotificationService::class);
    // notifyRecipients should NOT be called for creator — it uses direct notify()
    $notifications->shouldNotReceive('notifyRecipients');

    $service = new TestableApprovalService($notifications, [], $creator);
    $service->approve($record, $user);

    expect($service->createdApprovals[0]['action'])->toBe(ApprovalAction::Approved->value);
    $creator->shouldHaveReceived('notify')->once();

    $record->shouldHaveReceived('update')->once();
});

it('approve() with comment stores comment in approval and history', function () {
    $finalStage = makeStage(id: 1, moduleId: 1, order: 1, isFinal: true);
    $creator    = makeApprovalUser(id: 10);
    $record     = makeApprovalRecord(status: 'Submitted', moduleId: 1, currentStage: $finalStage, currentStageId: 1, createdBy: 10);
    $user       = makeApprovalUser(id: 5);

    $notifications = Mockery::mock(NotificationService::class);
    $notifications->shouldNotReceive('notifyRecipients');

    $service = new TestableApprovalService($notifications, [], $creator);
    $service->approve($record, $user, 'Looks good');

    expect($service->createdApprovals[0]['comment'])->toBe('Looks good');
    expect($service->createdHistories[0]['changes_json'])->toBe(['comment' => 'Looks good']);
});

// ── Tests: returnForRevision() ────────────────────────────────────────────────

it('returnForRevision() happy path: record set to Returned, approval+history created, creator notified', function () {
    $creator = makeApprovalUser(id: 10);
    $record  = makeApprovalRecord(status: 'Submitted', createdBy: 10);
    $user    = makeApprovalUser(id: 5);

    $notifications = Mockery::mock(NotificationService::class);
    $notifications->shouldNotReceive('notifyRecipients');

    $service = new TestableApprovalService($notifications, [], $creator);
    $service->returnForRevision($record, $user, 'Needs more detail');

    expect($service->createdApprovals)->toHaveCount(1);
    expect($service->createdApprovals[0]['action'])->toBe(ApprovalAction::Returned->value);
    expect($service->createdApprovals[0]['comment'])->toBe('Needs more detail');
    expect($service->createdHistories[0]['changes_json'])->toBe(['comment' => 'Needs more detail']);
    $creator->shouldHaveReceived('notify')->once();

    $record->shouldHaveReceived('update')->with(Mockery::on(fn($d) => ($d['status'] ?? null) === 'Returned'))->once();
});

it('returnForRevision() throws InvalidArgumentException when comment is empty string', function () {
    $record = makeApprovalRecord();
    $user   = makeApprovalUser();

    $service = makeService();

    expect(fn () => $service->returnForRevision($record, $user, ''))
        ->toThrow(\InvalidArgumentException::class);
});

// ── Tests: forwardToBranch() ──────────────────────────────────────────────────

it('forwardToBranch() valid index: record updated, approval+history created, stage notified', function () {
    $targetStage = makeStage(id: 3, moduleId: 1, defaultStatus: 'Under Review', notifyOnEnter: [['type' => 'role', 'value' => 'approver']]);

    $branches    = [
        0 => ['stage_id' => 3, 'label' => 'Path A'],
    ];
    $currentStage = makeStage(id: 1, moduleId: 1, order: 1, branches: $branches);

    $record = makeApprovalRecord(status: 'Submitted', moduleId: 1, currentStage: $currentStage, currentStageId: 1);
    $user   = makeApprovalUser(id: 5);

    $notifications = Mockery::mock(NotificationService::class);
    $notifications->shouldReceive('notifyRecipients')->once();

    $service = new TestableApprovalService($notifications, [$targetStage]);
    $service->forwardToBranch($record, $user, 0);

    expect($service->createdApprovals)->toHaveCount(1);
    expect($service->createdApprovals[0]['action'])->toBe(ApprovalAction::Forwarded->value);
    expect($service->createdHistories[0]['changes_json']['path'])->toBe('Path A');

    $record->shouldHaveReceived('update')->once();
});

it('forwardToBranch() throws InvalidArgumentException when branch index is invalid', function () {
    $currentStage = makeStage(id: 1, branches: []);
    $record       = makeApprovalRecord(status: 'Submitted', currentStage: $currentStage, currentStageId: 1);
    $user         = makeApprovalUser();

    $service = makeService();

    expect(fn () => $service->forwardToBranch($record, $user, 99))
        ->toThrow(\InvalidArgumentException::class);
});

it('forwardToBranch() throws InvalidArgumentException when branch stage_id is empty', function () {
    $branches     = [0 => ['stage_id' => '', 'label' => 'Bad']];
    $currentStage = makeStage(id: 1, branches: $branches);
    $record       = makeApprovalRecord(status: 'Submitted', currentStage: $currentStage, currentStageId: 1);
    $user         = makeApprovalUser();

    $service = makeService();

    expect(fn () => $service->forwardToBranch($record, $user, 0))
        ->toThrow(\InvalidArgumentException::class);
});

// ── Tests: autoAdvance() ─────────────────────────────────────────────────────

it('autoAdvance() non-final: advances to next stage, RecordApproval user_id null, action auto_advanced', function () {
    $currentStage = makeStage(id: 1, moduleId: 1, order: 1, isFinal: false);
    $nextStage    = makeStage(id: 2, moduleId: 1, order: 2);

    $record = makeApprovalRecord(status: 'Submitted', moduleId: 1, currentStage: $currentStage, currentStageId: 1);

    $notifications = Mockery::mock(NotificationService::class);
    // Legacy fallback path: notifyRecipients not used, getUsersByRole returns empty
    $notifications->shouldNotReceive('notifyRecipients');

    $service = new TestableApprovalService($notifications, [$nextStage]);
    $service->autoAdvance($record);

    expect($service->createdApprovals)->toHaveCount(1);
    expect($service->createdApprovals[0]['action'])->toBe(ApprovalAction::AutoAdvanced->value);
    expect($service->createdApprovals[0]['user_id'])->toBeNull();
    expect($service->createdHistories)->toHaveCount(1);
    expect($service->createdHistories[0]['user_id'])->toBeNull();

    $record->shouldHaveReceived('update')->once();
});

it('autoAdvance() final stage: record marked Completed, creator notified', function () {
    $finalStage = makeStage(id: 1, moduleId: 1, order: 1, isFinal: true);
    $creator    = makeApprovalUser(id: 10);
    $record     = makeApprovalRecord(status: 'Submitted', moduleId: 1, currentStage: $finalStage, currentStageId: 1, createdBy: 10);

    $notifications = Mockery::mock(NotificationService::class);
    $notifications->shouldNotReceive('notifyRecipients');

    $service = new TestableApprovalService($notifications, [], $creator);
    $service->autoAdvance($record);

    expect($service->createdApprovals[0]['action'])->toBe(ApprovalAction::AutoApproved->value);
    expect($service->createdApprovals[0]['user_id'])->toBeNull();
    $creator->shouldHaveReceived('notify')->once();

    $record->shouldHaveReceived('update')->once();
});
