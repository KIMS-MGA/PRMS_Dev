<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    /**
     * Mark a specific notification as read.
     */
    public function markRead(string $id): Response
    {
        auth()->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
        
        return response()->noContent();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();
        
        return back();
    }

    /**
     * Mark a notification as read and redirect to the relevant resource.
     */
    public function open(string $id): RedirectResponse
    {
        $notif = auth()->user()->notifications()->where('id', $id)->first();
        
        if ($notif) {
            $notif->markAsRead();
            $data = $notif->data;
            if (!empty($data['record_id']) && !empty($data['module_slug'])) {
                return redirect()->route('dynamic.show', [
                    'moduleSlug' => $data['module_slug'],
                    'record' => $data['record_id'],
                ]);
            }
        }
        
        return redirect()->route('builder.approval.queue');
    }
}
