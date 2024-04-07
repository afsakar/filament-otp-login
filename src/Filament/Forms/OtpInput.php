<?php

namespace Afsakar\FilamentOtpLogin\Filament\Forms;

use Filament\Forms;

class OtpInput extends Forms\Components\TextInput
{
    protected string $view = 'filament-otp-login::forms.otp-input';

    protected int $numberLength = 6;

    public static function make(string $name): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure();

        $static->numberLength = config('filament-otp-login.otp_code.length');

        return $static;
    }

    public function getNumberLength(): int
    {
        return $this->evaluate($this->numberLength);
    }
}
