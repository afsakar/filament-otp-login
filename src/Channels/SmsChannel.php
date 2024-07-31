<?php

namespace Afsakar\FilamentOtpLogin\Channels;

use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct()
    {
        
    }

    public function send($notifiable, Notification $notification)
    {
        $data = $notification->toSms($notifiable);

        $phone_number = $data['phone'];
        $code = $data['code'];

        // send code with sms
        
    }
}
