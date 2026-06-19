<?php

it('uses Filament native one time code input', function () {
    $login = file_get_contents(__DIR__ . '/../src/Filament/Pages/Login.php');

    expect($login)
        ->toContain('Filament\Forms\Components\OneTimeCodeInput')
        ->toContain('OneTimeCodeInput::make')
        ->not->toContain('Afsakar\FilamentOtpLogin\Filament\Forms\OtpInput');
});
