<?php

namespace App\Services;

use App\Events\RecordSaved;
use App\Models\Module;
use App\Models\Record;
use App\Models\RecordHistory;
use Illuminate\Http\UploadedFile;

/**
 * Handles persisting a record: file-field processing, upsert, history creation,
 * and event dispatch.
 *
 * Extracted from DynamicRecordForm::persistRecord() (lines 120–209) as part of
 * Phase 2 of the PRMS refactoring project (Task 2.4).
 */
class RecordPersistenceService
{
    public function __construct(
        private readonly FileVersioningService $fileVersioning,
    ) {}

    /**
     * Persist a record for the given module.
     *
     * @param  Module       $module    The module the record belongs to.
     * @param  array        $formData  Field values keyed by slug; may contain UploadedFile instances.
     * @param  Record|null  $existing  Pass an existing Record to update it, or null to create a new one.
     *
     * @return Record  The created or updated Record instance.
     */
    public function save(Module $module, array $formData, ?Record $existing = null): Record
    {
        // 1. Resolve target module ID
        $targetModuleId = $module->source_module_id ?? $module->id;

        // 2. Process file fields
        foreach ($module->fields as $field) {
            if ($field->type !== 'attachment') {
                continue;
            }

            $value = $formData[$field->slug] ?? null;
            $isUploadedFile = $value instanceof \Illuminate\Http\UploadedFile;

            if ($isUploadedFile && $field->versioning) {
                // Versioned upload: prepend new version to the existing list
                $existingValue = $existing?->data[$field->slug] ?? [];
                $formData[$field->slug] = $this->fileVersioning->prepend($value, $existingValue);

            } elseif ($isUploadedFile && ! $field->versioning) {
                // Non-versioned upload: store and keep the path string
                $formData[$field->slug] = $value->store('attachments', 'public');

            } elseif (! $isUploadedFile && $field->versioning) {
                // No new file but field is versioned: restore existing versions so
                // they are not lost during the upsert
                $formData[$field->slug] = $existing?->data[$field->slug] ?? [];
            }
            // Non-versioned, no new file: leave formData value unchanged
        }

        // 3. Determine if new or update
        $isNew      = $existing === null;
        $beforeData = $isNew ? null : ['data' => $existing->data, 'status' => $existing->status];

        // 4. Upsert record
        $record = $this->upsertRecord(
            ['id' => $existing?->id],
            [
                'module_id'  => $targetModuleId,
                'data'       => $formData,
                'status'     => $formData['status'] ?? ($existing?->status ?? 'Draft'),
                'created_by' => $existing?->created_by ?? auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        // 5. Create RecordHistory
        $this->createHistory([
            'record_id'    => $record->id,
            'user_id'      => auth()->id(),
            'action'       => $isNew ? 'created' : 'updated',
            'changes_json' => $isNew ? null : [
                'before' => $beforeData,
                'after'  => ['data' => $formData, 'status' => $record->status],
            ],
        ]);

        // 6. Dispatch event
        $this->dispatchEvent($record, $isNew ? 'created' : 'updated');

        // 7. Return
        return $record;
    }

    // ── Protected hooks (overrideable in tests / subclasses) ─────────────────

    /**
     * Perform the actual database upsert.
     * Isolated as a protected method so tests can stub it without hitting the DB.
     */
    protected function upsertRecord(array $attributes, array $values): Record
    {
        return Record::updateOrCreate($attributes, $values);
    }

    /**
     * Persist a RecordHistory entry.
     * Isolated as a protected method so tests can stub it without hitting the DB.
     */
    protected function createHistory(array $data): void
    {
        RecordHistory::create($data);
    }

    /**
     * Dispatch the RecordSaved domain event.
     * Isolated as a protected method so tests can stub it without triggering listeners.
     */
    protected function dispatchEvent(Record $record, string $trigger): void
    {
        RecordSaved::dispatch($record, $trigger);
    }
}
