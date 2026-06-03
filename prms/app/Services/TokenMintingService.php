<?php

namespace App\Services;

use App\Models\Module;
use App\Models\User;

class TokenMintingService
{
    /**
     * Mint one editor token per text_editor field, scoped to recordId (or 'new').
     * Revokes previous tokens for this prefix and expired tokens.
     *
     * @param User $user
     * @param Module $module
     * @param int|null $recordId
     * @return array
     */
    public function mintEditorTokens(User $user, Module $module, ?int $recordId): array
    {
        $editorTokens = [];
        $tokenPrefix  = 'editor-' . ($recordId ?? 'new') . '-';

        // Purge only expired tokens (older than 8 h) for this prefix so we do NOT
        // revoke tokens that are currently being used by another browser window
        // belonging to the same user (concurrent collaborative sessions).
        $user->tokens()
            ->where('name', 'like', $tokenPrefix . '%')
            ->where('created_at', '<', now()->subHours(8))
            ->delete();

        // Index remaining valid tokens by name so we can detect which ones exist.
        $existingTokenNames = $user->tokens()
            ->where('name', 'like', $tokenPrefix . '%')
            ->pluck('name')
            ->flip()
            ->all();

        foreach ($module->fields as $field) {
            if ($field->type === 'text_editor') {
                $tokenName = $tokenPrefix . $field->slug;

                // Always delete-then-recreate so we get a fresh plain-text token
                // to embed in the Blade view (Sanctum only stores the hash, so we
                // cannot reconstruct the raw token for an existing row).
                // This is safe: the Hocuspocus server validates on connect; the old
                // token is invalidated only when the new one is issued here.
                if (isset($existingTokenNames[$tokenName])) {
                    $user->tokens()->where('name', $tokenName)->delete();
                }

                $editorTokens[$field->slug] = $user
                    ->createToken($tokenName, ['editor:read', 'editor:write'], now()->addHours(8))
                    ->plainTextToken;
            }
        }

        return $editorTokens;
    }
}
