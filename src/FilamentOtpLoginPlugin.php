<?php

namespace Afsakar\FilamentOtpLogin;

use Afsakar\FilamentOtpLogin\Filament\Pages\Login;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentOtpLoginPlugin implements Plugin
{
    public string $login = Login::class;

    public function getId(): string
    {
        return 'filament-otp-login';
    }

    public function register(Panel $panel): void
    {
        $panel->login($this->login);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function loginPage(string $login): static
    {
        $this->login = $login;

        return $this;
    }
}
