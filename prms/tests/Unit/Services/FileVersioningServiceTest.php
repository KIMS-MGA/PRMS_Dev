<?php

use App\Services\FileVersioningService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Bind to Laravel TestCase so facades (Storage, auth) are backed by a running
// application container. RefreshDatabase is NOT used — no database access occurs.
uses(TestCase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Create a fake UploadedFile for testing without hitting the filesystem.
 */
function makeFakeFile(string $name = 'document.pdf', string $mimeType = 'application/pdf'): UploadedFile
{
    return UploadedFile::fake()->create($name, 100, $mimeType);
}

// ── store() tests ─────────────────────────────────────────────────────────────

it('store() saves file to correct disk and returns array with correct shape', function () {
    Storage::fake('public');

    $service = new FileVersioningService();
    $file    = makeFakeFile('report.pdf');

    $result = $service->store($file, 'public', 'attachments');

    expect($result)->toBeArray()
        ->toHaveKeys(['path', 'original_name', 'uploaded_at', 'uploaded_by', 'uploaded_by_name']);

    // Verify the file was actually stored
    Storage::disk('public')->assertExists($result['path']);
});

it('store() returns entry with correct original_name from getClientOriginalName()', function () {
    Storage::fake('public');

    $service = new FileVersioningService();
    $file    = makeFakeFile('my-document.pdf');

    $result = $service->store($file, 'public', 'attachments');

    expect($result['original_name'])->toBe('my-document.pdf');
});

it('store() returns entry with uploaded_by matching auth()->id()', function () {
    Storage::fake('public');

    // Act as a user so auth()->id() is non-null
    $user = \App\Models\User::factory()->make(['id' => 42]);
    $this->actingAs($user);

    $service = new FileVersioningService();
    $file    = makeFakeFile('signed-form.pdf');

    $result = $service->store($file, 'public', 'attachments');

    expect($result['uploaded_by'])->toBe(auth()->id());
});

// ── prepend() tests ───────────────────────────────────────────────────────────

it('prepend() with existing array value: new version is first element, old versions preserved', function () {
    Storage::fake('public');

    $service = new FileVersioningService();
    $file    = makeFakeFile('new-file.pdf');

    $existingVersions = [
        ['path' => 'attachments/old.pdf', 'original_name' => 'old.pdf', 'uploaded_at' => '2024-01-01 00:00:00', 'uploaded_by' => 1, 'uploaded_by_name' => 'Alice'],
    ];

    $result = $service->prepend($file, $existingVersions, 'public', 'attachments');

    expect($result)->toHaveCount(2);
    expect($result[0]['original_name'])->toBe('new-file.pdf');
    expect($result[1])->toBe($existingVersions[0]);
});

it('prepend() with legacy string value: string is migrated to version entry, new file prepended', function () {
    Storage::fake('public');

    $service = new FileVersioningService();
    $file    = makeFakeFile('updated.pdf');

    $legacyPath = 'attachments/legacy-file.pdf';

    $result = $service->prepend($file, $legacyPath, 'public', 'attachments');

    expect($result)->toHaveCount(2);
    expect($result[0]['original_name'])->toBe('updated.pdf');
    expect($result[1]['path'])->toBe($legacyPath);
    expect($result[1]['original_name'])->toBe('legacy-file.pdf');
    expect($result[1]['uploaded_by'])->toBeNull();
    expect($result[1]['uploaded_by_name'])->toBe('Unknown');
});

it('prepend() with null existing value: returns array with just the new version', function () {
    Storage::fake('public');

    $service = new FileVersioningService();
    $file    = makeFakeFile('first-upload.pdf');

    $result = $service->prepend($file, null, 'public', 'attachments');

    expect($result)->toHaveCount(1);
    expect($result[0]['original_name'])->toBe('first-upload.pdf');
});

it('prepend() with empty string existing value: returns array with just the new version', function () {
    Storage::fake('public');

    $service = new FileVersioningService();
    $file    = makeFakeFile('first.pdf');

    $result = $service->prepend($file, '', 'public', 'attachments');

    expect($result)->toHaveCount(1);
    expect($result[0]['original_name'])->toBe('first.pdf');
});

// ── migrateLegacyValue() tests ────────────────────────────────────────────────

it('migrateLegacyValue() non-empty string: returns single-element array with correct shape', function () {
    $service = new FileVersioningService();

    $result = $service->migrateLegacyValue('attachments/my-file.pdf');

    expect($result)->toBeArray()->toHaveCount(1);
    expect($result[0])->toMatchArray([
        'path'             => 'attachments/my-file.pdf',
        'original_name'    => 'my-file.pdf',
        'uploaded_at'      => null,
        'uploaded_by'      => null,
        'uploaded_by_name' => 'Unknown',
    ]);
});

it('migrateLegacyValue() already an array: returns unchanged', function () {
    $service = new FileVersioningService();

    $existing = [
        ['path' => 'attachments/v1.pdf', 'original_name' => 'v1.pdf', 'uploaded_at' => '2024-06-01 12:00:00', 'uploaded_by' => 5, 'uploaded_by_name' => 'Bob'],
        ['path' => 'attachments/v2.pdf', 'original_name' => 'v2.pdf', 'uploaded_at' => '2024-07-01 12:00:00', 'uploaded_by' => 5, 'uploaded_by_name' => 'Bob'],
    ];

    $result = $service->migrateLegacyValue($existing);

    expect($result)->toBe($existing);
});

it('migrateLegacyValue() null: returns empty array', function () {
    $service = new FileVersioningService();

    $result = $service->migrateLegacyValue(null);

    expect($result)->toBe([]);
});

it('migrateLegacyValue() empty string: returns empty array', function () {
    $service = new FileVersioningService();

    $result = $service->migrateLegacyValue('');

    expect($result)->toBe([]);
});
