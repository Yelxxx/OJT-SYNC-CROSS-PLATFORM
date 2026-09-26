<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function read(DatabaseNotification $notification): RedirectResponse
    {
        abort_if($notification->notifiable_id !== Auth::id(), 403, 'This notification does not belong to you.');

        $notification->markAsRead();

        $link = $notification->data['link'] ?? null;

        return redirect($link && is_string($link) ? $link : route('dashboard'));
    }

    public function markAllRead(): RedirectResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('status', 'All notifications marked as read.');
    }
}
