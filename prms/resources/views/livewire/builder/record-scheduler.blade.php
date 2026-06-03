<div @if(!$isSchedulableStage && $history->isEmpty()) style="display:none" @endif
    class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

    {{-- Header --}}
    <h3 class="text-base font-bold text-gray-800 border-b pb-2 mb-4 flex items-center gap-2">
        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        Meeting Schedule
        @if($record->currentStage)
            <span class="text-xs font-normal text-gray-400">— {{ $record->currentStage->name }}</span>
        @endif
    </h3>

    {{-- Current scheduled date (when not editing) --}}
    @if(!$isEditing)
        @if($latestSchedule)
            <div class="mb-4 p-3 rounded-lg bg-purple-50 border border-purple-100 flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-semibold text-purple-800">
                        {{ $latestSchedule->scheduled_at->format('F j, Y') }}
                    </span>
                    <span class="text-sm text-purple-600">
                        {{ $latestSchedule->scheduled_at->format('g:i A') }}
                    </span>
                </div>
                @if($latestSchedule->notes)
                    <p class="text-xs text-gray-500 italic pl-6">{{ $latestSchedule->notes }}</p>
                @endif
                <p class="text-xs text-gray-400 pl-6">
                    Scheduled by {{ $latestSchedule->scheduler?->name ?? 'System' }}
                    · {{ $latestSchedule->created_at->diffForHumans() }}
                </p>
            </div>
        @elseif($isSchedulableStage)
            <p class="text-sm text-gray-400 italic mb-4">No meeting scheduled yet.</p>
        @endif

        @if($canSchedule && $isSchedulableStage)
            <div class="flex gap-2">
                <button wire:click="startEditing"
                    class="inline-flex items-center gap-1.5 bg-purple-600 text-white px-3 py-1.5 rounded shadow-sm hover:bg-purple-700 font-bold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ $latestSchedule ? 'Reschedule' : 'Set Schedule' }}
                </button>
                @if($latestSchedule)
                    <button wire:click="cancelSchedule"
                        wire:confirm="Cancel this schedule? All users will see the removal."
                        class="inline-flex items-center gap-1.5 bg-red-100 text-red-700 px-3 py-1.5 rounded shadow-sm hover:bg-red-200 font-bold text-sm">
                        Cancel Schedule
                    </button>
                @endif
            </div>
        @endif
    @endif

    {{-- Schedule form --}}
    @if($isEditing && $canSchedule)
        <div class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Date <span class="text-red-400">*</span></label>
                    <input type="date" wire:model="scheduledDate"
                        min="{{ date('Y-m-d') }}"
                        class="block w-full rounded border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                    @error('scheduledDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Time <span class="text-red-400">*</span></label>
                    <input type="time" wire:model="scheduledTime"
                        class="block w-full rounded border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                    @error('scheduledTime') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Notes (optional)</label>
                <textarea wire:model="scheduleNotes" rows="2"
                    placeholder="e.g. Conference Room A, virtual link..."
                    class="block w-full rounded border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm"></textarea>
                @error('scheduleNotes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="flex gap-2 pt-1">
                <button wire:click="saveSchedule" wire:loading.attr="disabled"
                    class="bg-purple-600 text-white px-4 py-1.5 rounded shadow-sm hover:bg-purple-700 font-bold text-sm disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveSchedule">Save Schedule</span>
                    <span wire:loading wire:target="saveSchedule">Saving…</span>
                </button>
                <button wire:click="cancelEditing" wire:loading.attr="disabled"
                    class="bg-gray-100 text-gray-700 px-4 py-1.5 rounded shadow-sm hover:bg-gray-200 font-bold text-sm">
                    Cancel
                </button>
            </div>
        </div>
    @endif

    {{-- Schedule History --}}
    @if($history->isNotEmpty())
        <div class="mt-4 border-t pt-4">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Schedule History</p>
            <div class="space-y-2">
                @foreach($history as $entry)
                    <div class="flex items-start gap-2 text-xs">
                        <span class="flex-shrink-0 font-bold uppercase w-20 text-right
                            {{ $entry->action === 'scheduled'   ? 'text-purple-600' : '' }}
                            {{ $entry->action === 'rescheduled' ? 'text-blue-600'   : '' }}
                            {{ $entry->action === 'cancelled'   ? 'text-red-500'    : '' }}
                        ">{{ ucfirst($entry->action) }}</span>
                        <div class="flex-1">
                            <span class="font-medium text-gray-700">{{ $entry->scheduler?->name ?? 'System' }}</span>
                            <span class="text-gray-400"> — {{ $entry->scheduled_at->format('M j, Y g:i A') }}</span>
                            @if($entry->notes && $entry->action !== 'cancelled')
                                <p class="text-gray-400 italic">{{ $entry->notes }}</p>
                            @endif
                        </div>
                        <span class="text-gray-300 flex-shrink-0">{{ $entry->created_at->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
