<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PolishResetPasswordNotification extends Notification
{
    public function __construct(
        protected string $token,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Reset hasla - Dziennik hodowlany MAKSSNAKE')
            ->greeting('Czesc!')
            ->line('Otrzymalismy prosbe o zresetowanie hasla do Twojego konta.')
            ->action('Ustaw nowe haslo', $url)
            ->line('Link do resetu wygasnie za '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minut.')
            ->line('Jesli to nie Ty prosiles o reset hasla, zignoruj te wiadomosc.');
    }
}
