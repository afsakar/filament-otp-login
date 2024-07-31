<?php

namespace Afsakar\FilamentOtpLogin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Afsakar\FilamentOtpLogin\Channels\SmsChannel;

class SendOtpCode extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(public string $type, public string $phone, public string $code)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        if ($this->type == 'email') {
            return ['mail'];
        } else {
            return [SmsChannel::class];
        }
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(__('filament-otp-login::translations.mail.subject'))
            ->greeting(__('filament-otp-login::translations.mail.greeting'))
            ->line(__('filament-otp-login::translations.mail.line1', ['code' => $this->code]))
            ->line(__('filament-otp-login::translations.mail.line2', ['seconds' => config('filament-otp-login.otp_code.expires')]))
            ->line(__('filament-otp-login::translations.mail.line3'))
            ->salutation(__('filament-otp-login::translations.mail.salutation', ['app_name' => config('app.name')]));
    }

    /**
     * Get the sms representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Afsakar\FilamentOtpLogin\Channels\SmsChannel
     */
    public function toSms($notifiable)
    {
        return [
            'phone' => $this->phone,
            'code' => $this->code
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
