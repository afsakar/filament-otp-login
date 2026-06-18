<?php

use Afsakar\FilamentOtpLogin\Notifications\SendOtpCode;

return [
    'table_name' => 'otp_codes',

    'otp_code' => [
        'length' => (int) env('OTP_LOGIN_CODE_LENGTH', 6),
        'expires' => (int) env('OTP_LOGIN_CODE_EXPIRES_SECONDS', 120),
    ],

    'rate_limit' => [
        'attempts' => (int) env('OTP_LOGIN_RATE_LIMIT_ATTEMPTS', 5),
        'decay_seconds' => (int) env('OTP_LOGIN_RATE_LIMIT_DECAY_SECONDS', 60),
    ],

    'notification_class' => SendOtpCode::class,
];
