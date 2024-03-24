<?php

namespace Afsakar\FilamentOtpLogin\Commands;

use Afsakar\FilamentOtpLogin\Models\OtpCode;
use Illuminate\Console\Command;

class PruneOtpCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp-codes:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune expired OTP codes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Pruning expired OTP codes...');

        $pruned = OtpCode::where('expires_at', '<', now())->delete();

        $this->info("Pruned {$pruned} expired OTP codes.");
    }
}
