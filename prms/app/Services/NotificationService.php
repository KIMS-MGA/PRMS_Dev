<?php

namespace App\Services;

use App\Mail\StageNotificationMail;
use App\Models\Record;
use App\Models\User;
use App\Notifications\DynamicNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Dispatch notifications to all configured recipients.
     *
     * Each element of $recipients has shape: ['type' => string, 'value' => string]
     *
     * Supported types:
     *   - 'submitter'      — DB + email notification to the record creator
     *   - 'role'           — DB + email notification to every user holding the named role
     *   - 'specific_user'  — DB + email notification to the user with the given ID
     *   - 'specific_email' — raw mail only (StageNotificationMail), no DB notification
     *
     * Failures per recipient are caught, logged as warnings, and do not abort
     * the remaining recipients.
     */
    public function notifyRecipients(
        array $recipients,
        Record $record,
        string $message,
        ?string $subject = null
    ): void {
        $recordUrl = $this->buildRecordUrl($record);

        foreach ($recipients as $recipient) {
            $type  = $recipient['type']  ?? '';
            $value = $recipient['value'] ?? '';

            try {
                match ($type) {
                    'submitter'      => $this->notifyUser(
                        $this->findUser($record->created_by),
                        $message,
                        $record,
                        $subject
                    ),
                    'role'           => $this->notifyRole($value, $message, $record, $subject),
                    'specific_user'  => $this->notifyUser(
                        $this->findUser($value),
                        $message,
                        $record,
                        $subject
                    ),
                    'specific_email' => $this->notifyRawEmail($value, $message, $subject, $recordUrl),
                    default          => null,
                };
            } catch (\Throwable $e) {
                Log::warning('[NotificationService] Failed type=' . $type . ': ' . $e->getMessage());
            }
        }
    }

    // ── Protected helpers (overridable in tests) ──────────────────────────────

    /**
     * Resolve a User by primary key. Returns null when not found.
     *
     * @param  int|string  $id
     */
    protected function findUser(mixed $id): ?User
    {
        return User::find($id);
    }

    /**
     * Return all users belonging to $roleName.
     */
    protected function getUsersByRole(string $roleName): Collection
    {
        return User::role($roleName)->get();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Send a DB + email notification to a single user.
     * Silently skips null users (bad IDs).
     */
    private function notifyUser(?User $user, string $message, Record $record, ?string $subject): void
    {
        if (! $user) {
            return;
        }

        $user->notify(new DynamicNotification(
            message:    $message,
            recordId:   $record->id,
            moduleSlug: $record->module?->slug,
            subject:    $subject,
            sendEmail:  true,
        ));
    }

    /**
     * Send a DB + email notification to every user holding $roleName.
     */
    private function notifyRole(string $roleName, string $message, Record $record, ?string $subject): void
    {
        foreach ($this->getUsersByRole($roleName) as $user) {
            $this->notifyUser($user, $message, $record, $subject);
        }
    }

    /**
     * Send a raw email (no DB notification) to a specific email address.
     */
    private function notifyRawEmail(
        string $email,
        string $message,
        ?string $subject,
        ?string $recordUrl
    ): void {
        Mail::to($email)->send(new StageNotificationMail(
            body:        $message,
            mailSubject: $subject ?? 'PRMS Notification',
            recordUrl:   $recordUrl,
        ));
    }

    /**
     * Build the record URL, guarding against a null module relationship.
     */
    private function buildRecordUrl(Record $record): ?string
    {
        $slug = $record->module?->slug;

        if (! $slug || ! $record->id) {
            return null;
        }

        return url("/app/{$slug}/{$record->id}");
    }
}
