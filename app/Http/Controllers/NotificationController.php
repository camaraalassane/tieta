<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function markAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    public function getUnread(Request $request)
    {
        $notifications = $request->user()->unreadNotifications()->latest()->take(50)->get();

        $formatted = $notifications->map(function ($notif) {
            return [
                'id' => $notif->id,
                'data' => $notif->data,
                'created_at' => $notif->created_at,
                'read_at' => $notif->read_at
            ];
        });

        return response()->json($formatted);
    }

    public function markOneAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }
        return response()->json(['success' => true]);
    }
}
