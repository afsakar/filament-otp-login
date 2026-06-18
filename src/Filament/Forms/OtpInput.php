<?php

namespace Afsakar\FilamentOtpLogin\Filament\Forms;

use Filament\Forms;
use Illuminate\Support\Facades\Config;

class OtpInput extends Forms\Components\TextInput
{
    protected int $numberLength = 6;

    public static function make(?string $name = null): static
    {
        $static = parent::make($name);

        $static->numberLength = Config::integer('filament-otp-login.otp_code.length');

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->view('filament-otp-login::forms.otp-input');
    }

    public function getNumberLength(): int
    {
        return $this->evaluate($this->numberLength);
    }
}
