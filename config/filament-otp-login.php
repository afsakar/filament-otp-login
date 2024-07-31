<?php

return [
    'table_name' => 'otp_codes',

    'otp_code' => [
        'length' => env('OTP_LOGIN_CODE_LENGTH', 6),
        'expires' => env('OTP_LOGIN_CODE_EXPIRES_SECONDS', 120),
    ],

    /**
    * Determines which login fields to display:
    * 
    * - 'email': Show only the email field.
    * - 'phone': Show only the phone field.
    * - null: Show both email and phone fields.
    */
    'login_option' => null
];
