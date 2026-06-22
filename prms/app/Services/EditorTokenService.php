<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class EditorTokenService
{
    /**
     * Mint one Sanctum token per field slug, scoped to the given prefix.
     *
     * Revokes any existing tokens for this prefix before creating new ones to
     * prevent accumulation. Also cleans up legacy single-part editor tokens
     * that are older than 8 hours.
     *
     * @param  User    $user        The authenticated user who owns the tokens.
     * @param  string  $prefix      Token name prefix, e.g. 'editor-42-' or 'editor-new-'.
     * @param  string[] $fieldSlugs  Slugs of text_editor fields to mint tokens for.
     * @return array<string, string> Map of field slug → plain text token.
     */
    public function mint(User $user, string $prefix, array $fieldSlugs): array
    {
        // Revoke all previous tokens for this prefix to prevent accumulation.
        $user->tokens()->where('name', 'like', $prefix . '%')->delete();

        // Clean up legacy single-part editor tokens (old 'editor-{slug}' format) older than 8 hours.
        $user->tokens()
            ->where('name', 'like', 'editor-%')
            ->whereNotLike('name', 'editor-%-%-%')
            ->where('created_at', '<', now()->subHours(8))
            ->delete();

        $tokens = [];

        foreach ($fieldSlugs as $slug) {
            $newToken       = $user->createToken(
                $prefix . $slug,
                ['editor:read', 'editor:write'],
                now()->addHours(8)
            );
            $tokens[$slug]  = $newToken->plainTextToken;
        }

        return $tokens;
    }

    /**
     * Revoke all tokens for this user whose name starts with $prefix.
     *
     * @param  User    $user    The authenticated user who owns the tokens.
     * @param  string  $prefix  Token name prefix, e.g. 'editor-42-'.
     */
    public function revoke(User $user, string $prefix): void
    {
        $user->tokens()->where('name', 'like', $prefix . '%')->delete();
    }

    /**
     * Check whether a plain-text Sanctum token is still valid (exists and not expired).
     *
     * @param  string  $token  The raw plain-text token string.
     * @return bool
     */
    public function isValid(string $token): bool
    {
        $pat = PersonalAccessToken::findToken($token);

        return $pat !== null && ($pat->expires_at === null || $pat->expires_at->isFuture());
    }
}
