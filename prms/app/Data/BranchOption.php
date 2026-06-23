<?php

namespace App\Data;

readonly class BranchOption
{
    public function __construct(
        public string $label,
        public int    $stage_id,
    ) {}
}