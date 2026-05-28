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
        $tokenPrefix = 'editor-' . ($recordId ?? 'new') . '-';

        // Revoke ALL previous tokens for this prefix immediately to prevent accumulation
        $user->tokens()->where('name', 'like', $tokenPrefix . '%')->delete();
        
        // Revoke legacy tokens older than 8 hours
        $user->tokens()
            ->where('name', 'like', 'editor-%')
            ->whereNotLike('name', 'editor-%-%-%')
            ->where('created_at', '<', now()->subHours(8))
            ->delete();

        foreach ($module->fields as $field) {
            if ($field->type === 'text_editor') {
                $editorTokens[$field->slug] = $user
                    ->createToken($tokenPrefix . $field->slug, ['editor:read', 'editor:write'], now()->addHours(8))
                    ->plainTextToken;
            }
        }

        return $editorTokens;
    }
}
