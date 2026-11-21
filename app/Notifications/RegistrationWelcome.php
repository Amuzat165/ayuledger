<?php

namespace App\Notifications;

use App\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationWelcome extends Notification
{
    use Queueable;

    private $tenant;
    private $businessName;

    public function __construct(Tenant $tenant, $businessName)
    {
        $this->tenant = $tenant;
        $this->businessName = $businessName;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Welcome to AYUGLOBAL - Registration received')
            ->greeting('Welcome, ' . $notifiable->name)
            ->line($this->businessName . ' has been registered successfully.')
            ->line('Your account is awaiting payment verification and administrator approval.')
            ->action('Submit payment details', url('/business/payment'))
            ->line('Keep this account information secure and do not share your password.');
    }
}
