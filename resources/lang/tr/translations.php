<?php

return [
    'otp_code' => 'Tek Kullanımlık Şifre',

    'mail' => [
        'subject' => 'Tek Kullanımlık Şifre',
        'greeting' => 'Merhaba!',
        'line1' => 'Tek Kullanımlık Şifreniz: :code',
        'line2' => 'Bu şifre :seconds saniye boyunca geçerlidir.',
        'line3' => 'Eğer bir şifre istemediyseniz, lütfen bu e-postayı görmezden gelin.',
        'salutation' => 'Saygılarımla, :app_name',
    ],

    'view' => [
        'time_left' => 'saniye kaldı',
        'resend_code' => 'Şifreyi Yeniden Gönder',
        'verify' => 'Doğrula',
        'go_back' => 'Geri Git',
    ],

    'notifications' => [
        'title' => 'Tek Kullanımlık Şifre Gönderildi',
        'body' => 'Doğrulama kodu e-posta adresinize gönderildi. :seconds saniye içinde geçerli olacaktır.',
    ],

    'validation' => [
        'invalid_code' => 'Girdiğiniz şifre geçersiz.',
        'expired_code' => 'Girdiğiniz şifrenin süresi dolmuş. Lütfen yeni bir şifre isteyin.',
    ],
];
