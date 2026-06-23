<?php

namespace App\Data;

readonly class NotifyRecipient
{
    public function __construct(
        public string $type,
        public string $value,
    ) {}
}