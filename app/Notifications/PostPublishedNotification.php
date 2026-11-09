<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Models\Post;

class PostPublishedNotification extends Notification
{
    // Sin ShouldQueue: QUEUE_CONNECTION=sync de igual funciona en background,
    // pero con ShouldQueue y un queue worker caído las notificaciones nunca se envían.
    // Usamos notificación síncrona para garantizar entrega inmediata.

    public $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $priorityLabel = match($this->post->priority) {
            'urgente'    => '🔴 URGENTE',
            'importante' => '🟡 IMPORTANTE',
            default      => '🔵 Nuevo comunicado',
        };

        return [
            'post_id'     => $this->post->id,
            'title'       => $this->post->title,
            'type'        => $this->post->type,
            'priority'    => $this->post->priority,
            'store_id'    => $this->post->store_id,
            'author_name' => optional($this->post->author)->name,
            'message'     => "{$priorityLabel}: {$this->post->title}",
            'url'         => route('feed.show', $this->post->id),
        ];
    }
}
