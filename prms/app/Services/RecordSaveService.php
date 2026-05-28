<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Record;
use App\Models\RecordHistory;
use App\Events\RecordSaved;
use App\Models\User;

class RecordSaveService
{
    /**
     * Persist record changes, handles file uploads and versioning.
     *
     * @param Module $module
     * @param Record|null $record
     * @param array $data
     * @param string $status
     * @param User $user
     * @return Record
     */
    public function save(Module $module, ?Record $record, array $data, string $status, User $user): Record
    {
        if ($record && $record->status === 'Completed') {
            abort(403, 'Completed records cannot be edited.');
        }

        foreach ($module->fields as $field) {
            if ($field->type === 'attachment' && isset($data[$field->slug])) {
                $file = $data[$field->slug];
                if (is_object($file) && method_exists($file, 'store')) {
                    // Storing the file on public disk
                    $path = $file->store('attachments', 'public');

                    if ($field->versioning) {
                        $existing = [];
                        if ($record) {
                            $existingVal = $record->data[$field->slug] ?? [];
                            if (is_string($existingVal) && !empty($existingVal)) {
                                // Migrate old single-file value into versioned array
                                $existing = [[
                                    'path'             => $existingVal,
                                    'original_name'    => basename($existingVal),
                                    'uploaded_at'      => null,
                                    'uploaded_by'      => null,
                                    'uploaded_by_name' => 'Unknown'
                                ]];
                            } elseif (is_array($existingVal)) {
                                $existing = $existingVal;
                            }
                        }
                        array_unshift($existing, [
                            'path'             => $path,
                            'original_name'    => $file->getClientOriginalName(),
                            'uploaded_at'      => now()->toDateTimeString(),
                            'uploaded_by'      => $user->id,
                            'uploaded_by_name' => $user->name,
                        ]);
                        $data[$field->slug] = $existing;
                    } else {
                        $data[$field->slug] = $path;
                    }
                } elseif ($field->versioning) {
                    // No new file uploaded — restore existing versions so they aren't wiped
                    $data[$field->slug] = $record ? ($record->data[$field->slug] ?? []) : [];
                }
            }
        }

        $isNew = !$record;
        $beforeData = $isNew ? null : ['data' => $record->data, 'status' => $record->status];

        $targetModuleId = $module->source_module_id ?? $module->id;

        $savedRecord = Record::updateOrCreate(
            ['id' => $record?->id],
            [
                'module_id'  => $targetModuleId,
                'data'       => $data,
                'status'     => $status,
                'created_by' => $record ? $record->created_by : $user->id,
                'updated_by' => $user->id,
            ]
        );

        RecordHistory::create([
            'record_id'    => $savedRecord->id,
            'user_id'      => $user->id,
            'action'       => $isNew ? 'created' : 'updated',
            'changes_json' => $isNew ? null : [
                'before' => $beforeData,
                'after'  => ['data' => $data, 'status' => $status],
            ],
        ]);

        RecordSaved::dispatch($savedRecord, $isNew ? 'created' : 'updated');

        return $savedRecord;
    }
}
