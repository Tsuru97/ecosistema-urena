<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class VerifyEmailAndPhone extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Genera la URL firmada para verificación de email.
     */
    protected function verificationEmailUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'users.verify.email',
            Carbon::now()->addMinutes(60 * 24),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    /**
     * Genera la URL firmada para verificación de teléfono.
     * Método público estático para ser usado desde el controlador.
     */
    public static function verificationPhoneUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'users.verify.phone',
            Carbon::now()->addMinutes(60 * 24),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->phone),
            ]
        );
    }

    /**
     * Enviar notificación por correo electrónico.
     */
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationEmailUrl($notifiable);

        return (new MailMessage)
            ->subject('Verifica tu correo electrónico - Ureña Muebles')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Por favor, haz clic en el botón de abajo para verificar tu dirección de correo electrónico.')
            ->action('Verificar Correo Electrónico', $url)
            ->line('Este enlace expirará en 24 horas.')
            ->line('Si no creaste una cuenta, no es necesario realizar ninguna acción.')
            ->salutation('Saludos, Ureña Muebles');
    }
}
