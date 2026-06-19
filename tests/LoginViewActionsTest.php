<?php

it('renders otp form actions with the support actions component', function () {
    $view = file_get_contents(__DIR__ . '/../resources/views/pages/login.blade.php');

    expect($view)
        ->toContain('x-filament::actions')
        ->not->toContain('x-filament-schemas::actions');
});
