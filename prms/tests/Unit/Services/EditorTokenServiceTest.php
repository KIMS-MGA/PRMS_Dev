<?php

use App\Models\User;
use App\Services\EditorTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

// Bind to a real application container so Sanctum token operations work.
// RefreshDatabase is required — Sanctum token operations need the personal_access_tokens table.
uses(TestCase::class, RefreshDatabase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Create a persisted User for token tests.
 */
function makeEditorUser(): User
{
    return User::factory()->create();
}

// ── mint() tests ──────────────────────────────────────────────────────────────

it('mint() revokes existing prefix tokens before minting new ones', function () {
    $user    = makeEditorUser();
    $service = new EditorTokenService();
    $prefix  = 'editor-42-';
    $slugs   = ['body'];

    // Mint once — creates token
    $service->mint($user, $prefix, $slugs);
    expect($user->tokens()->where('name', 'like', $prefix . '%')->count())->toBe(1);

    // Mint again — should revoke previous and mint fresh (still 1 token, not 2)
    $service->mint($user, $prefix, $slugs);
    expect($user->tokens()->where('name', 'like', $prefix . '%')->count())->toBe(1);
});

it('mint() creates one token per field slug, named correctly', function () {
    $user    = makeEditorUser();
    $service = new EditorTokenService();
    $prefix  = 'editor-99-';
    $slugs   = ['notes', 'description', 'summary'];

    $service->mint($user, $prefix, $slugs);

    foreach ($slugs as $slug) {
        expect(
            $user->tokens()->where('name', $prefix . $slug)->exists()
        )->toBeTrue("Token for slug '{$slug}' was not created");
    }
});

it('mint() returns array keyed by slug with non-empty token strings', function () {
    $user    = makeEditorUser();
    $service = new EditorTokenService();
    $prefix  = 'editor-new-';
    $slugs   = ['content', 'notes'];

    $result = $service->mint($user, $prefix, $slugs);

    expect($result)->toBeArray();
    expect(array_keys($result))->toBe($slugs);

    foreach ($result as $slug => $token) {
        expect($token)->toBeString()->not->toBeEmpty();
    }
});

it('mint() token abilities include editor:read and editor:write', function () {
    $user    = makeEditorUser();
    $service = new EditorTokenService();
    $prefix  = 'editor-5-';
    $slugs   = ['body'];

    $result = $service->mint($user, $prefix, $slugs);

    $rawToken = $result['body'];
    $pat      = PersonalAccessToken::findToken($rawToken);

    expect($pat)->not->toBeNull();
    expect($pat->abilities)->toContain('editor:read');
    expect($pat->abilities)->toContain('editor:write');
});

it('mint() token expires in approximately 8 hours', function () {
    $user    = makeEditorUser();
    $service = new EditorTokenService();
    $prefix  = 'editor-7-';
    $slugs   = ['notes'];

    $before = now()->addHours(8)->subMinutes(1);
    $after  = now()->addHours(8)->addMinutes(1);

    $result = $service->mint($user, $prefix, $slugs);

    $rawToken = $result['notes'];
    $pat      = PersonalAccessToken::findToken($rawToken);

    expect($pat)->not->toBeNull();
    expect($pat->expires_at)->not->toBeNull();
    expect($pat->expires_at->between($before, $after))->toBeTrue();
});

it('mint() with empty fieldSlugs returns empty array and creates no tokens', function () {
    $user    = makeEditorUser();
    $service = new EditorTokenService();
    $prefix  = 'editor-new-';

    $result = $service->mint($user, $prefix, []);

    expect($result)->toBeArray()->toBeEmpty();
    expect($user->tokens()->where('name', 'like', $prefix . '%')->count())->toBe(0);
});

it('mint() does NOT delete new-format tokens (editor-{id}-{slug}) older than 8 hours', function () {
    $user    = makeEditorUser();
    $service = new EditorTokenService();

    // Manually create a new-format token that is "old" (created 9 hours ago)
    // to simulate a long-running session for a different record.
    $oldNewFormatToken = $user->createToken(
        'editor-42-notes',
        ['editor:read', 'editor:write'],
        now()->addHours(8)
    );

    // Back-date created_at so it looks old enough for the legacy cleanup to target it
    $user->tokens()
        ->where('name', 'editor-42-notes')
        ->update(['created_at' => now()->subHours(9)]);

    // Mint tokens for a different prefix — should NOT delete editor-42-notes
    $service->mint($user, 'editor-77-', ['body']);

    expect(
        $user->tokens()->where('name', 'editor-42-notes')->exists()
    )->toBeTrue('New-format token editor-42-notes was incorrectly deleted by legacy cleanup');
});

// ── revoke() tests ────────────────────────────────────────────────────────────

it('revoke() deletes tokens matching the prefix', function () {
    $user    = makeEditorUser();
    $service = new EditorTokenService();
    $prefix  = 'editor-10-';
    $slugs   = ['notes', 'body'];

    $service->mint($user, $prefix, $slugs);

    expect($user->tokens()->where('name', 'like', $prefix . '%')->count())->toBe(2);

    $service->revoke($user, $prefix);

    expect($user->tokens()->where('name', 'like', $prefix . '%')->count())->toBe(0);
});

// ── isValid() tests ───────────────────────────────────────────────────────────

it('isValid() returns true for a valid unexpired token', function () {
    $user    = makeEditorUser();
    $service = new EditorTokenService();
    $prefix  = 'editor-20-';

    $result = $service->mint($user, $prefix, ['content']);

    expect($service->isValid($result['content']))->toBeTrue();
});

it('isValid() returns false for an expired token', function () {
    $user    = makeEditorUser();
    $service = new EditorTokenService();

    // Create a token that expires in the past
    $newToken = $user->createToken('editor-expired-test', ['editor:read', 'editor:write'], now()->subHour());
    $rawToken = $newToken->plainTextToken;

    expect($service->isValid($rawToken))->toBeFalse();
});

it('isValid() returns false for an unknown/invalid token string', function () {
    $service = new EditorTokenService();

    expect($service->isValid('totally-invalid-token-string-that-does-not-exist'))->toBeFalse();
});
