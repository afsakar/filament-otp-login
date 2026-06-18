<?php

it('targets only Filament 4 and 5', function () {
    $composer = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['require']['php'])->toBe('^8.2')
        ->and($composer['require']['filament/filament'])->toBe('^4.0 || ^5.0');
});

it('uses Filament 4 and 5 auth and schema APIs', function () {
    $login = file_get_contents(__DIR__ . '/../src/Filament/Pages/Login.php');
    $view = file_get_contents(__DIR__ . '/../resources/views/pages/login.blade.php');

    expect($login)
        ->toContain('Filament\Auth\Pages\Login')
        ->toContain('Filament\Schemas\Schema')
        ->not->toContain('Filament\Pages\Auth\Login')
        ->not->toContain('Filament\Forms\Form')
        ->and($view)
        ->toContain('filament-panels::auth/pages/login')
        ->not->toContain('filament-panels::pages/auth/login');
});
