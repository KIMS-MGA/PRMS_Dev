<?php

namespace App\Livewire\Builder;

use Livewire\Component;
use App\Models\Record;
use App\Models\RecordSchedule;
use App\Models\User;
use App\Notifications\DynamicNotification;
use Illuminate\Support\Facades\Log;

class RecordScheduler extends Component
{
    public int $recordId;
    public string $moduleSlug;

    public string $scheduledDate = '';
    public string $scheduledTime = '';
    public string $scheduleNotes = '';

    public bool $isEditing = false;

    // Stages that support scheduling
    private const SCHEDULABLE_STAGES = ['TRC Deliberation', 'Ad Referendum Review'];

    public function mount(int $recordId, string $moduleSlug): void
    {
        $this->recordId  = $recordId;
        $this->moduleSlug = $moduleSlug;
    }

    public function canSchedule(): bool
    {
        $user = auth()->user();
        return $user->hasRole('super admin') || $user->hasRole('TRC Secretariat');
    }

    public function isSchedulableStage(): bool
    {
        $record = Record::with('currentStage')->find($this->recordId);
        if (! $record || ! $record->currentStage) return false;
        return in_array($record->currentStage->name, self::SCHEDULABLE_STAGES, true);
    }

    public function startEditing(): void
    {
        if (! $this->canSchedule()) abort(403);

        $latest = RecordSchedule::where('record_id', $this->recordId)
            ->where('action', '!=', 'cancelled')
            ->latest()
            ->first();

        if ($latest) {
            $this->scheduledDate = $latest->scheduled_at->format('Y-m-d');
            $this->scheduledTime = $latest->scheduled_at->format('H:i');
            $this->scheduleNotes = $latest->notes ?? '';
        }

        $this->isEditing = true;
    }

    public function cancelEditing(): void
    {
        $this->isEditing      = false;
        $this->scheduledDate  = '';
        $this->scheduledTime  = '';
        $this->scheduleNotes  = '';
    }

    public function saveSchedule(): void
    {
        if (! $this->canSchedule()) abort(403);

        $this->validate([
            'scheduledDate' => 'required|date|after_or_equal:today',
            'scheduledTime' => 'required|date_format:H:i',
            'scheduleNotes' => 'nullable|string|max:1000',
        ]);

        $record = Record::with('currentStage')->findOrFail($this->recordId);

        $hasPrevious = RecordSchedule::where('record_id', $this->recordId)
            ->where('action', '!=', 'cancelled')
            ->exists();

        $scheduledAt = \Carbon\Carbon::parse("{$this->scheduledDate} {$this->scheduledTime}");

        $entry = RecordSchedule::create([
            'record_id'    => $this->recordId,
            'stage_id'     => $record->current_stage_id,
            'scheduled_by' => auth()->id(),
            'scheduled_at' => $scheduledAt,
            'notes'        => $this->scheduleNotes ?: null,
            'action'       => $hasPrevious ? 'rescheduled' : 'scheduled',
        ]);

        $this->notifyAllUsers($record, $entry);

        $this->isEditing     = false;
        $this->scheduledDate = '';
        $this->scheduledTime = '';
        $this->scheduleNotes = '';

        $label = $hasPrevious ? 'Schedule updated.' : 'Schedule saved.';
        $this->dispatch('notify', type: 'success', message: $label);
    }

    public function cancelSchedule(): void
    {
        if (! $this->canSchedule()) abort(403);

        $latest = RecordSchedule::where('record_id', $this->recordId)
            ->where('action', '!=', 'cancelled')
            ->latest()
            ->first();

        if (! $latest) return;

        $record = Record::with('currentStage')->findOrFail($this->recordId);

        RecordSchedule::create([
            'record_id'    => $this->recordId,
            'stage_id'     => $record->current_stage_id,
            'scheduled_by' => auth()->id(),
            'scheduled_at' => $latest->scheduled_at,
            'notes'        => 'Cancelled.',
            'action'       => 'cancelled',
        ]);

        $this->dispatch('notify', type: 'info', message: 'Schedule cancelled.');
    }

    private function notifyAllUsers(Record $record, RecordSchedule $entry): void
    {
        $action      = $entry->action === 'rescheduled' ? 'rescheduled' : 'scheduled';
        $stageName   = $record->currentStage?->name ?? 'the current stage';
        $dateLabel   = $entry->scheduled_at->format('F j, Y \a\t g:i A');
        $moduleName  = $record->module?->name ?? 'a record';
        $message     = "A meeting for {$moduleName} ({$stageName}) has been {$action} on {$dateLabel}.";

        foreach (User::where('is_active', true)->get() as $user) {
            try {
                $user->notify(new DynamicNotification($message, $this->recordId, $this->moduleSlug, 'Meeting Scheduled', true));
            } catch (\Throwable $e) {
                Log::warning('[RecordScheduler] Notification failed for user ' . $user->id . ': ' . $e->getMessage());
            }
        }
    }

    public function render()
    {
        $record = Record::with('currentStage')->find($this->recordId);
        $isSchedulableStage = $record && $record->currentStage
            && in_array($record->currentStage->name, self::SCHEDULABLE_STAGES, true);

        $latestSchedule = RecordSchedule::where('record_id', $this->recordId)
            ->where('action', '!=', 'cancelled')
            ->latest()
            ->first();

        $history = RecordSchedule::where('record_id', $this->recordId)
            ->with('scheduler', 'stage')
            ->latest()
            ->get();

        $canSchedule = $this->canSchedule();

        return view('livewire.builder.record-scheduler', compact(
            'record', 'isSchedulableStage', 'latestSchedule', 'history', 'canSchedule'
        ));
    }
}
