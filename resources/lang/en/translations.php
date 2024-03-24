<?php

return [
    'otp_code' => 'OTP Code',

    'mail' => [
        'subject' => 'OTP Code',
        'greeting' => 'Hello!',
        'line1' => 'Your OTP code is: :code',
        'line2' => 'This code will be valid for :seconds seconds.',
        'line3' => 'If you did not request a code, please ignore this email.',
        'salutation' => 'Best Regards, :app_name',
    ],

    'view' => [
        'time_left' => 'seconds left',
        'resend_code' => 'Resend Code',
        'verify' => 'Verify',
        'go_back' => 'Go Back',
    ],

    'notifications' => [
        'title' => 'OTP Code Sent',
        'body' => 'The verification code has been sent to your e-mail address. It will be valid in :seconds seconds.',
    ],

    'validation' => [
        'invalid_code' => 'The code you entered is invalid.',
        'expired_code' => 'The code you entered has expired.',
    ],
];
