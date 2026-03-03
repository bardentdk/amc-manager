<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemAlert extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $url;

    public function __construct($title, $message, $url = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
    }

    public function via(object $notifiable): array
    {
        return ['database']; // On stocke uniquement en base de données pour la cloche
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}