<?php

namespace Afsakar\FilamentOtpLogin\Commands;

use Illuminate\Console\Command;

class FilamentOtpLoginCommand extends Command
{
    public $signature = 'filament-otp-login';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
