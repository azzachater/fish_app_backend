<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CustomVerifyEmailNotification extends Notification
{
    use Queueable;

    protected $verificationUrl;

    /**
     * Create a new notification instance.
     *
     * @param string $verificationUrl
     */
    public function __construct(string $verificationUrl)
    {
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
{
    return (new MailMessage)
        ->subject('Verify Your Email Address')
        ->line('Please click the button below to verify your email address.')
        ->action('Verify Email Address', $this->verificationUrl) // Laravel gère l'encodage
        ->line('If you did not create an account, no further action is required.');
}

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'verification_url' => $this->verificationUrl
        ];
    }
}
