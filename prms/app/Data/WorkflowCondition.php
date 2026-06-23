<?php

namespace App\Data;

readonly class WorkflowCondition
{
    public function __construct(
        public string $field,
        public string $operator,
        public string $value,
    ) {}
}