<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ConcoursClosed extends Notification
{
    use Queueable;

    protected $count;

    public function __construct($count)
    {
        $this->count = $count;
    }

    public function via(object $notifiable): array
    {
        // On ne garde que database pour l'instant pour éviter les erreurs de config mail
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "Mise à jour : {$this->count} concours sont désormais Inactifs.",
            'count' => $this->count,
            'type' => 'info',
            'action_url' => '/admin/concours', // Optionnel : pour cliquer sur la notif
        ];
    }

    // Utilise toArray comme fallback pour database
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}