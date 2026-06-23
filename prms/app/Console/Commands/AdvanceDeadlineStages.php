<?php

namespace App\Console\Commands;

use App\Models\Record;
use App\Models\WorkflowStage;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AdvanceDeadlineStages extends Command
{
    protected $signature   = 'prms:advance-deadline-stages';
    protected $description = 'Auto-advance records that have exceeded their stage deadline (in working days).';

    public function __construct(private readonly ApprovalService $approvalService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $stages = WorkflowStage::whereNotNull('auto_advance_days')->get();

        if ($stages->isEmpty()) {
            $this->info('No stages with deadlines configured.');
            return;
        }

        $advanced = 0;

        foreach ($stages as $stage) {
            $records = Record::where('current_stage_id', $stage->id)
                ->whereNotNull('stage_entered_at')
                ->whereIn('status', ['Submitted', 'Under Review'])
                ->get();

            foreach ($records as $record) {
                if ($this->countWorkingDays($record->stage_entered_at, now()) < $stage->auto_advance_days) {
                    continue;
                }

                $prevStageId = $record->current_stage_id;
                $this->approvalService->autoAdvance($record);
                $record->refresh();

                if ($record->status === 'Completed') {
                    $this->line("  ✓ Record #{$record->id} auto-approved (final stage deadline exceeded).");
                } else {
                    $this->line("  ↑ Record #{$record->id} advanced from stage #{$prevStageId}.");
                }

                $advanced++;
            }
        }

        $this->info("Auto-advanced {$advanced} record(s).");
    }

    private function countWorkingDays(Carbon $from, Carbon $to): int
    {
        $count   = 0;
        $current = $from->copy()->startOfDay();
        $end     = $to->copy()->startOfDay();

        while ($current->lt($end)) {
            if ($current->isWeekday()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }
}
