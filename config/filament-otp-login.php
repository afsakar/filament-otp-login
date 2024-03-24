<?php

return [
    'table_name' => 'otp_codes',

    'user_model' => env('OTP_LOGIN_USER_MODEL', 'App\\Models\\User'),

    'otp_code' => [
        'length' => env('OTP_LOGIN_CODE_LENGTH', 6),
        'expires' => env('OTP_LOGIN_CODE_EXPIRES_SECONDS', 120),
    ],
];
