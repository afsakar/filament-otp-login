<?php

it('uses a cryptographically secure random source for otp codes', function () {
    $login = file_get_contents(__DIR__ . '/../src/Filament/Pages/Login.php');

    expect($login)
        ->toContain('random_int(')
        ->not->toContain('rand(');
});
