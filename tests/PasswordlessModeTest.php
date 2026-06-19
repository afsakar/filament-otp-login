<?php

it('keeps passwordless mode disabled by default', function () {
    expect(config('filament-otp-login.passwordless'))->toBeFalse();
});

it('adds a passwordless branch without password credentials', function () {
    $login = file_get_contents(__DIR__ . '/../src/Filament/Pages/Login.php');

    expect($login)
        ->toContain("Config::boolean('filament-otp-login.passwordless')")
        ->toContain('login($user')
        ->toContain("'email' => \$data['email']")
        ->not->toContain("'password' => \$data['password'],\n        ];\n    }\n\n    protected function checkCanLoginDirectly");
});
