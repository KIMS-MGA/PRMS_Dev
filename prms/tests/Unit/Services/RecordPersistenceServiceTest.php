<?php

use App\Events\RecordSaved;
use App\Models\Module;
use App\Models\Record;
use App\Models\RecordHistory;
use App\Services\FileVersioningService;
use App\Services\RecordPersistenceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

// Bind to a real application container so facades work but no DB is used.
uses(TestCase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Build a plain field object with the properties RecordPersistenceService reads.
 */
function makeField(string $slug, string $type = 'text', bool $versioning = false): object
{
    return (object) ['slug' => $slug, 'type' => $type, 'versioning' => $versioning];
}

/**
 * Build a mock Module with the minimum attributes the service needs.
 *
 * @param  array<object>  $fields
 */
function makeModuleForPersistence(
    int $id = 1,
    ?int $sourceModuleId = null,
    array $fields = []
): Module {
    /** @var Module&Mockery\MockInterface $module */
    $module = Mockery::mock(Module::class)->makePartial();
    $module->id               = $id;
    $module->source_module_id = $sourceModuleId;
    $module->shouldReceive('getAttribute')->with('id')->andReturn($id);
    $module->shouldReceive('getAttribute')->with('source_module_id')->andReturn($sourceModuleId);
    $module->shouldReceive('getAttribute')->with('fields')->andReturn(collect($fields));

    return $module;
}

/**
 * Build a mock Record with the minimum attributes the service reads.
 */
function makeExistingRecord(
    int $id = 10,
    int $createdBy = 5,
    string $status = 'Draft',
    array $data = []
): Record {
    /** @var Record&Mockery\MockInterface $record */
    $record = Mockery::mock(Record::class)->makePartial();
    $record->id         = $id;
    $record->created_by = $createdBy;
    $record->status     = $status;
    $record->data       = $data;

    $record->shouldReceive('getAttribute')->with('id')->andReturn($id);
    $record->shouldReceive('getAttribute')->with('created_by')->andReturn($createdBy);
    $record->shouldReceive('getAttribute')->with('status')->andReturn($status)->byDefault();
    $record->shouldReceive('getAttribute')->with('data')->andReturn($data);

    return $record;
}

/**
 * Build a testable RecordPersistenceService that stubs Eloquent static calls.
 */
function makeRecordPersistenceService(
    ?FileVersioningService $fileVersioning = null,
    ?Record $savedRecord = null,
): TestableRecordPersistenceService {
    $fileVersioning ??= Mockery::mock(FileVersioningService::class);

    return new TestableRecordPersistenceService($fileVersioning, $savedRecord);
}

// ── Testable subclass ─────────────────────────────────────────────────────────

/**
 * Subclass that stubs DB-touching static calls (Record::updateOrCreate, RecordHistory::create).
 */
class TestableRecordPersistenceService extends RecordPersistenceService
{
    public array $createdHistories = [];
    public array $upsertCalls      = [];
    public bool  $eventDispatched  = false;
    public string $dispatchedTrigger = '';

    public function __construct(
        FileVersioningService $fileVersioning,
        private ?Record $recordToReturn = null,
    ) {
        parent::__construct($fileVersioning);
    }

    protected function upsertRecord(array $attributes, array $values): Record
    {
        $this->upsertCalls[] = ['attributes' => $attributes, 'values' => $values];

        if ($this->recordToReturn !== null) {
            return $this->recordToReturn;
        }

        // Build a minimal in-memory Record so the rest of save() can proceed
        /** @var Record&Mockery\MockInterface $record */
        $record = Mockery::mock(Record::class)->makePartial();
        $record->id     = $attributes['id'] ?? 999;
        $record->status = $values['status'] ?? 'Draft';
        $record->shouldReceive('getAttribute')->with('id')->andReturn($record->id);
        $record->shouldReceive('getAttribute')->with('status')->andReturn($record->status);

        return $record;
    }

    protected function createHistory(array $data): void
    {
        $this->createdHistories[] = $data;
    }

    protected function dispatchEvent(Record $record, string $trigger): void
    {
        $this->eventDispatched     = true;
        $this->dispatchedTrigger   = $trigger;
    }
}

// ── Tests: new record ─────────────────────────────────────────────────────────

it('save() creates new record when $existing is null: upsertRecord called with id null', function () {
    $module  = makeModuleForPersistence(id: 1, fields: []);
    $service = makeRecordPersistenceService();

    $service->save($module, ['name' => 'Test'], null);

    expect($service->upsertCalls)->toHaveCount(1);
    expect($service->upsertCalls[0]['attributes']['id'])->toBeNull();
});

it('save() updates existing record: upsertRecord called with correct id', function () {
    $existing = makeExistingRecord(id: 42);
    $module   = makeModuleForPersistence(id: 1, fields: []);
    $service  = makeRecordPersistenceService(savedRecord: $existing);

    $service->save($module, ['name' => 'Updated'], $existing);

    expect($service->upsertCalls)->toHaveCount(1);
    expect($service->upsertCalls[0]['attributes']['id'])->toBe(42);
});

// ── Tests: RecordHistory ──────────────────────────────────────────────────────

it('save() creates history with action=created and changes_json=null for new records', function () {
    $module  = makeModuleForPersistence(id: 1, fields: []);
    $service = makeRecordPersistenceService();

    $service->save($module, ['title' => 'New'], null);

    expect($service->createdHistories)->toHaveCount(1);
    expect($service->createdHistories[0]['action'])->toBe('created');
    expect($service->createdHistories[0]['changes_json'])->toBeNull();
});

it('save() creates history with action=updated and before/after changes_json for existing records', function () {
    $existing = makeExistingRecord(id: 10, status: 'Draft', data: ['title' => 'Old']);
    $module   = makeModuleForPersistence(id: 1, fields: []);
    $service  = makeRecordPersistenceService(savedRecord: $existing);

    $service->save($module, ['title' => 'New'], $existing);

    expect($service->createdHistories)->toHaveCount(1);
    expect($service->createdHistories[0]['action'])->toBe('updated');

    $changesJson = $service->createdHistories[0]['changes_json'];
    expect($changesJson)->toHaveKey('before');
    expect($changesJson)->toHaveKey('after');
    expect($changesJson['before']['data'])->toBe(['title' => 'Old']);
    expect($changesJson['before']['status'])->toBe('Draft');
    expect($changesJson['after']['data'])->toBe(['title' => 'New']);
});

// ── Tests: RecordSaved event ──────────────────────────────────────────────────

it('save() dispatches RecordSaved event with trigger=created for new records', function () {
    $module  = makeModuleForPersistence(id: 1, fields: []);
    $service = makeRecordPersistenceService();

    $service->save($module, ['title' => 'New'], null);

    expect($service->eventDispatched)->toBeTrue();
    expect($service->dispatchedTrigger)->toBe('created');
});

it('save() dispatches RecordSaved event with trigger=updated for existing records', function () {
    $existing = makeExistingRecord(id: 10);
    $module   = makeModuleForPersistence(id: 1, fields: []);
    $service  = makeRecordPersistenceService(savedRecord: $existing);

    $service->save($module, ['title' => 'Updated'], $existing);

    expect($service->eventDispatched)->toBeTrue();
    expect($service->dispatchedTrigger)->toBe('updated');
});

// ── Tests: file field processing ─────────────────────────────────────────────

it('save() versioned attachment: calls FileVersioningService::prepend() and stores result in formData', function () {
    $attachmentField = makeField('doc', 'attachment', versioning: true);
    $module          = makeModuleForPersistence(id: 1, fields: [$attachmentField]);

    $file         = UploadedFile::fake()->create('report.pdf', 100);
    $existingData = ['doc' => [['path' => 'attachments/old.pdf', 'original_name' => 'old.pdf', 'uploaded_at' => null, 'uploaded_by' => null, 'uploaded_by_name' => 'Unknown']]];
    $existing     = makeExistingRecord(id: 10, data: $existingData);

    $versionedResult = [
        ['path' => 'attachments/new.pdf', 'original_name' => 'report.pdf', 'uploaded_at' => '2025-01-01 00:00:00', 'uploaded_by' => 1, 'uploaded_by_name' => 'Alice'],
        ['path' => 'attachments/old.pdf', 'original_name' => 'old.pdf', 'uploaded_at' => null, 'uploaded_by' => null, 'uploaded_by_name' => 'Unknown'],
    ];

    $fileVersioning = Mockery::mock(FileVersioningService::class);
    $fileVersioning->shouldReceive('prepend')
        ->once()
        ->with($file, $existingData['doc'])
        ->andReturn($versionedResult);

    $service = makeRecordPersistenceService(fileVersioning: $fileVersioning, savedRecord: $existing);

    $formData = ['doc' => $file];
    $service->save($module, $formData, $existing);

    // The upserted formData should contain the versioned array, not the raw UploadedFile
    $upsertedData = $service->upsertCalls[0]['values']['data'];
    expect($upsertedData['doc'])->toBe($versionedResult);
});

it('save() non-versioned attachment: calls $file->store() directly and stores path string', function () {
    Storage::fake('public');

    $attachmentField = makeField('doc', 'attachment', versioning: false);
    $module          = makeModuleForPersistence(id: 1, fields: [$attachmentField]);
    $file            = UploadedFile::fake()->create('contract.pdf', 100);

    $fileVersioning = Mockery::mock(FileVersioningService::class);
    $fileVersioning->shouldNotReceive('prepend');

    $service  = makeRecordPersistenceService(fileVersioning: $fileVersioning);
    $formData = ['doc' => $file];

    $service->save($module, $formData, null);

    $upsertedData = $service->upsertCalls[0]['values']['data'];
    expect($upsertedData['doc'])->toBeString();
    expect($upsertedData['doc'])->not->toBeEmpty();
});

it('save() no new file for versioned field: restores existing versions from record', function () {
    $attachmentField = makeField('doc', 'attachment', versioning: true);
    $module          = makeModuleForPersistence(id: 1, fields: [$attachmentField]);

    $existingVersions = [['path' => 'attachments/v1.pdf', 'original_name' => 'v1.pdf', 'uploaded_at' => null, 'uploaded_by' => 1, 'uploaded_by_name' => 'Alice']];
    $existing         = makeExistingRecord(id: 10, data: ['doc' => $existingVersions]);

    $fileVersioning = Mockery::mock(FileVersioningService::class);
    $fileVersioning->shouldNotReceive('prepend');

    $service  = makeRecordPersistenceService(fileVersioning: $fileVersioning, savedRecord: $existing);
    $formData = ['doc' => null]; // No new file uploaded

    $service->save($module, $formData, $existing);

    $upsertedData = $service->upsertCalls[0]['values']['data'];
    expect($upsertedData['doc'])->toBe($existingVersions);
});

it('save() non-attachment field: left unchanged in formData', function () {
    $textField = makeField('title', 'text', versioning: false);
    $module    = makeModuleForPersistence(id: 1, fields: [$textField]);

    $fileVersioning = Mockery::mock(FileVersioningService::class);
    $fileVersioning->shouldNotReceive('prepend');

    $service  = makeRecordPersistenceService(fileVersioning: $fileVersioning);
    $formData = ['title' => 'My Title'];

    $service->save($module, $formData, null);

    $upsertedData = $service->upsertCalls[0]['values']['data'];
    expect($upsertedData['title'])->toBe('My Title');
});

// ── Tests: target module ID resolution ───────────────────────────────────────

it('save() uses source_module_id as target when set', function () {
    $module  = makeModuleForPersistence(id: 1, sourceModuleId: 99, fields: []);
    $service = makeRecordPersistenceService();

    $service->save($module, [], null);

    expect($service->upsertCalls[0]['values']['module_id'])->toBe(99);
});

it('save() uses own id as target when source_module_id is null', function () {
    $module  = makeModuleForPersistence(id: 7, sourceModuleId: null, fields: []);
    $service = makeRecordPersistenceService();

    $service->save($module, [], null);

    expect($service->upsertCalls[0]['values']['module_id'])->toBe(7);
});

// ── Tests: return value ───────────────────────────────────────────────────────

it('save() returns the Record instance returned by upsertRecord()', function () {
    $savedRecord = makeExistingRecord(id: 55);
    $module      = makeModuleForPersistence(id: 1, fields: []);
    $service     = makeRecordPersistenceService(savedRecord: $savedRecord);

    $result = $service->save($module, [], null);

    expect($result)->toBe($savedRecord);
});
