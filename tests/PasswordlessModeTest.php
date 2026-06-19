<?php

use Afsakar\FilamentOtpLogin\FilamentOtpLoginPlugin;

it('keeps passwordless mode disabled by default', function () {
    expect(FilamentOtpLoginPlugin::make()->isPasswordless())->toBeFalse();
});

it('adds a passwordless branch without password credentials', function () {
    $login = file_get_contents(__DIR__ . '/../src/Filament/Pages/Login.php');

    expect($login)
        ->toContain('$this->plugin()->isPasswordless()')
        ->toContain('login($user')
        ->toContain('$this->plugin()->getUserIdentifierColumn()')
        ->not->toContain("'password' => \$data['password'],\n        ];\n    }\n\n    protected function checkCanLoginDirectly");
});
