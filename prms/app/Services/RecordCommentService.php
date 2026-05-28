<?php

namespace App\Services;

use App\Models\RecordComment;
use App\Models\User;

class RecordCommentService
{
    /**
     * Add a comment to a record.
     *
     * @param int $recordId
     * @param User $user
     * @param string $body
     * @return RecordComment
     */
    public function addComment(int $recordId, User $user, string $body): RecordComment
    {
        return RecordComment::create([
            'record_id' => $recordId,
            'user_id'   => $user->id,
            'body'      => $body,
        ]);
    }

    /**
     * Delete a comment from a record.
     *
     * @param int $commentId
     * @param int $recordId
     * @param User $user
     * @return void
     */
    public function deleteComment(int $commentId, int $recordId, User $user): void
    {
        if (!$user->can('delete-comments')) {
            abort(403);
        }

        RecordComment::where('id', $commentId)
            ->where('record_id', $recordId)
            ->delete();
    }
}
