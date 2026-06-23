<?php

namespace App\Data;

readonly class WorkflowConditions
{
    /**
     * @param  WorkflowCondition[]  $conditions
     */
    public function __construct(
        public string $logic,
        public array  $conditions,
    ) {}
}