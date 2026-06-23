<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Handles file storage and version-list management for attachment fields.
 *
 * Extracted from DynamicRecordForm::persistRecord() (lines 143–176) as part of
 * Phase 2 of the PRMS refactoring project (Task 2.3).
 *
 * This is a stateless service — no constructor injection required.
 */
class FileVersioningService
{
    /**
     * Store a file and return a single version-entry array.
     *
     * @return array{path: string, original_name: string, uploaded_at: string, uploaded_by: int|null, uploaded_by_name: string}
     */
    public function store(UploadedFile $file, string $disk = 'public', string $directory = 'attachments'): array
    {
        $path = $file->store($directory, $disk);

        return [
            'path'             => $path,
            'original_name'    => $file->getClientOriginalName(),
            'uploaded_at'      => now()->toDateTimeString(),
            'uploaded_by'      => auth()->id(),
            'uploaded_by_name' => auth()->user()?->name ?? 'Unknown',
        ];
    }

    /**
     * Prepend a newly stored file to the existing version list.
     *
     * Migrates any legacy value (bare string path or null) into the versioned
     * array format, then inserts the new entry at position 0 (newest first).
     *
     * Source: DynamicRecordForm::persistRecord() lines 151–169
     *
     * @return array<int, array{path: string, original_name: string, uploaded_at: string|null, uploaded_by: int|null, uploaded_by_name: string}>
     */
    public function prepend(UploadedFile $file, mixed $existingValue, string $disk = 'public', string $directory = 'attachments'): array
    {
        $existing = $this->migrateLegacyValue($existingValue);
        $entry    = $this->store($file, $disk, $directory);

        array_unshift($existing, $entry);

        return $existing;
    }

    /**
     * Convert any stored attachment value into a versioned array.
     *
     * Handles three legacy shapes:
     * - Non-empty string  → single-element array wrapping the bare path
     * - Array             → returned as-is
     * - null / empty / '' → empty array
     *
     * Source: DynamicRecordForm::persistRecord() lines 153–159
     *
     * @return array<int, array{path: string, original_name: string, uploaded_at: string|null, uploaded_by: int|null, uploaded_by_name: string}>
     */
    public function migrateLegacyValue(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            return [[
                'path'             => $value,
                'original_name'    => basename($value),
                'uploaded_at'      => null,
                'uploaded_by'      => null,
                'uploaded_by_name' => 'Unknown',
            ]];
        }

        if (is_array($value)) {
            return $value;
        }

        return [];
    }
}
