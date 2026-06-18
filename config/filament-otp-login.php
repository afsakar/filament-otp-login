<?php

use Afsakar\FilamentOtpLogin\Notifications\SendOtpCode;

return [
    'table_name' => 'otp_codes',

    'otp_code' => [
        'length' => (int) env('OTP_LOGIN_CODE_LENGTH', 6),
        'expires' => (int) env('OTP_LOGIN_CODE_EXPIRES_SECONDS', 120),
    ],

    'notification_class' => SendOtpCode::class,
];
