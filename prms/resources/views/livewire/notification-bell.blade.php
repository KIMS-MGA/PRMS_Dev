<div class="relative" x-data="{ bellOpen: false }" @click.outside="bellOpen = false" wire:poll.8s>
    <button @click="bellOpen = !bellOpen"
        class="relative p-2 text-gray-500 hover:text-gray-800 hover:bg-gray-100 rounded-full transition"
        aria-label="Notifications" title="Notifications">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-2.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if($unreadCount > 0)
            <span
                class="absolute top-1 right-1 bg-red-500 text-white text-[8px] rounded-full w-3.5 h-3.5 flex items-center justify-center font-bold leading-none">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="bellOpen" x-transition x-cloak
        class="absolute top-11 right-0 w-72 bg-white rounded-lg shadow-xl border border-gray-200 z-50 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-2.5 border-b bg-gray-50">
            <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Notifications</span>
            @if($unreadCount > 0)
                <button wire:click="markAllRead" class="text-[10px] text-indigo-600 hover:underline font-medium">
                    Mark all read
                </button>
            @endif
        </div>
        <div class="max-h-72 overflow-y-auto divide-y divide-gray-100">
            @forelse($unread as $notif)
                @php $ndata = $notif->data; @endphp
                <a href="{{ route('notifications.open', $notif->id) }}"
                    class="flex items-start gap-3 px-4 py-3 hover:bg-indigo-50 transition-colors">
                    <span class="mt-1 w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0"></span>
                    <span class="text-xs text-gray-700 leading-snug">{{ $ndata['message'] ?? '' }}</span>
                </a>
            @empty
                <div class="px-4 py-6 text-center text-xs text-gray-400 italic">No new notifications</div>
            @endforelse
        </div>
        <div class="border-t px-4 py-2.5 bg-gray-50">
            <a href="{{ route('builder.notifications') }}"
                class="text-xs text-indigo-600 hover:underline font-medium">
                View all notifications →
            </a>
        </div>
    </div>
</div>