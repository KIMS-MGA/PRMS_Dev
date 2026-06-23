<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationBell extends Component
{
    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $unread      = auth()->user()?->unreadNotifications()->latest()->take(10)->get() ?? collect();
        $unreadCount = $unread->count();

        return view('livewire.notification-bell', compact('unread', 'unreadCount'));
    }
}