<?php

namespace App\Livewire\Builder;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Record;
use Livewire\Attributes\Layout;

class ApprovalQueue extends Component
{
    use WithPagination;

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();

        // Record::pendingForUser() is the single source of truth for the queue
        // filter — the sidebar badge count uses the same scope so the two counts
        // can never diverge.
        $pendingRecords = Record::pendingForUser($user)
            ->with(['module', 'currentStage', 'creator'])
            ->latest()
            ->paginate(20);

        return view('livewire.builder.approval-queue', compact('pendingRecords'));
    }
}
