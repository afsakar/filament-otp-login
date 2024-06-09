<?php

return [
    'table_name' => 'otp_codes',

    'otp_code' => [
        'length' => env('OTP_LOGIN_CODE_LENGTH', 6),
        'expires' => env('OTP_LOGIN_CODE_EXPIRES_SECONDS', 120),
    ],
];
