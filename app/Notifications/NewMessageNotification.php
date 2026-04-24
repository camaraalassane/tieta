<?php
// app/Notifications/NewMessageNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NewMessageNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $message;
    protected $sender;

    public function __construct($message, $sender)
    {
        $this->message = $message;
        $this->sender = $sender;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'id' => $this->message->id,
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'subject' => $this->message->objet ?? 'Nouveau message',
            'message' => $this->message->texte,
            'type' => 'new_message',
            'icon' => 'pi-envelope',
            'color' => 'info',
            'date' => now()->toDateTimeString(),
            // ⚠️ Supprimé 'action_url' car la route n'existe pas
        ];
    }

    public function toBroadcast($notifiable)
    {
        return [
            'data' => [
                'id' => $this->message->id,
                'sender_name' => $this->sender->name,
                'subject' => $this->message->objet ?? 'Nouveau message',
                'message' => $this->message->texte,
                'type' => 'new_message',
                'date' => now()->toDateTimeString()
            ]
        ];
    }
}
