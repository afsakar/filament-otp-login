<?php

it('stores and verifies otp codes by hash', function () {
    $login = file_get_contents(__DIR__ . '/../src/Filament/Pages/Login.php');

    expect($login)
        ->toContain('Hash::make($this->otpCode)')
        ->toContain("Hash::check(\$this->data['otp'], \$code->code)")
        ->not->toContain('whereCode($code)')
        ->not->toContain("whereCode(\$this->data['otp'])");
});
