<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Bienvenido a Ureña Muebles!')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Tu cuenta ha sido creada exitosamente en el sistema de gestión de Ureña Muebles.')
            ->line('Tu nombre de usuario es: **' . $notifiable->username . '**')
            ->line('Tu correo electrónico registrado es: **' . $notifiable->email . '**')
            ->action('Iniciar Sesión', url('/login'))
            ->line('Si tienes alguna duda, no dudes en contactarnos.')
            ->salutation('Saludos, Ureña Muebles');
    }
}
