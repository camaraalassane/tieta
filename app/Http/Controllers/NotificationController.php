<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request)
    {
        // Marque toutes les notifications non lues de l'utilisateur comme "lues"
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }
}