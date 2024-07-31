<?php

return [
    'otp_code' => 'کد تایید',

    'mail' => [
        'subject' => 'کد تایید',
        'greeting' => 'سلام!',
        'line1' => 'کد تایید شما: :code',
        'line2' => 'این کد به مدت :seconds ثانیه معتبر خواهد بود.',
        'line3' => 'اگر شما درخواست کدی نکرده‌اید، لطفاً این ایمیل را نادیده بگیرید.',
        'salutation' => 'با احترام، :app_name',
    ],

    'view' => [
        'time_left' => 'ثانیه باقی‌مانده',
        'resend_code' => 'دوباره ارسال کد',
        'verify' => 'تایید',
        'go_back' => 'بازگشت',
    ],

    'notifications' => [
        'title' => 'کد تایید ارسال شد',
        'body' => 'کد تأیید به :type شما ارسال شده است. این کد به مدت :seconds ثانیه معتبر خواهد بود.',
    ],

    'validation' => [
        'invalid_code' => 'کدی که وارد کرده‌اید نامعتبر است.',
        'expired_code' => 'کدی که وارد کرده‌اید منقضی شده است.',
    ],

    'email' => 'ایمیل',
    'phone' => 'موبایل',

    'email_address' => 'آدرس ایمیل',
    'phone_number' => 'شماره موبایل',
];
