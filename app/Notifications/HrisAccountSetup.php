<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Password;

class HrisAccountSetup extends ResetPassword
{
    public static function sendTo(string $email): string
    {
        return Password::sendResetLink(['email' => $email], function (User $user, string $token): void {
            $user->notify(new self($token));
        });
    }

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Set up your Clarin HRIS account')
            ->line('You have been invited to Clarin HRIS. Use this link to set your password.')
            ->action('Set your password', $url)
            ->line('After signing in, verify your email and set up two-factor authentication.')
            ->line('If the link expires, request a new one from the HRIS sign-in page.');
    }
}
