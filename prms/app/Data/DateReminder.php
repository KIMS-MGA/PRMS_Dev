<?php

namespace App\Data;

readonly class DateReminder
{
    /**
     * @param  NotifyRecipient[]  $recipients
     */
    public function __construct(
        public string $field_slug,
        public int    $days_before,
        public array  $recipients,
    ) {}
}