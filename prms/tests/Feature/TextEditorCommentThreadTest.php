<?php

use App\Models\Module;
use App\Models\Record;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $role = Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'web']);
    $this->user = User::factory()->create();
    $this->user->assignRole($role);

    $this->module = Module::create(['name' => 'Docs', 'slug' => 'docs']);
    $this->record = Record::create([
        'module_id' => $this->module->id,
        'data' => [],
        'status' => 'Draft',
        'created_by' => $this->user->id,
    ]);

    Sanctum::actingAs($this->user);
});

function createRootComment($test): string
{
    $commentId = (string) Str::uuid();
    $test->postJson("/api/text-editor/{$test->record->id}/body/comments", [
        'comment_id' => $commentId,
        'quoted_text' => 'some text',
        'body' => 'a root comment',
    ])->assertCreated();

    return $commentId;
}

it('nests a reply under its root comment', function () {
    $commentId = createRootComment($this);

    $this->postJson("/api/text-editor/{$this->record->id}/body/comments/{$commentId}/reply", [
        'body' => 'a reply',
    ])->assertCreated();

    $comments = $this->getJson("/api/text-editor/{$this->record->id}/body/comments")
        ->assertOk()
        ->json();

    expect($comments)->toHaveCount(1);
    expect($comments[0]['comment_id'])->toBe($commentId);
    expect($comments[0]['replies'])->toHaveCount(1);
    expect($comments[0]['replies'][0]['body'])->toBe('a reply');
});

it('hides the whole thread once the root is resolved', function () {
    $commentId = createRootComment($this);
    $this->postJson("/api/text-editor/{$this->record->id}/body/comments/{$commentId}/reply", [
        'body' => 'a reply',
    ])->assertCreated();

    $this->deleteJson("/api/text-editor/{$this->record->id}/body/comments/{$commentId}")
        ->assertOk();

    $comments = $this->getJson("/api/text-editor/{$this->record->id}/body/comments")
        ->assertOk()
        ->json();

    expect($comments)->toHaveCount(0);
});

it('rejects a reply to a non-existent comment', function () {
    $this->postJson("/api/text-editor/{$this->record->id}/body/comments/" . Str::uuid() . '/reply', [
        'body' => 'orphan',
    ])->assertNotFound();
});

it('validates that a reply body is required', function () {
    $commentId = createRootComment($this);
    $this->postJson("/api/text-editor/{$this->record->id}/body/comments/{$commentId}/reply", [])
        ->assertStatus(422);
});
