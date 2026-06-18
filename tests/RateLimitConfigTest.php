<?php

use Afsakar\FilamentOtpLogin\Filament\Pages\Login;

it('uses configured rate limit values', function () {
    config()->set('filament-otp-login.rate_limit.attempts', 7);
    config()->set('filament-otp-login.rate_limit.decay_seconds', 90);

    $page = new class extends Login
    {
        public array $rateLimitArguments = [];

        public function triggerRateLimiter(): void
        {
            $this->rateLimiter();
        }

        protected function rateLimit($maxAttempts, $decaySeconds = 60, $method = null, $component = null)
        {
            $this->rateLimitArguments = [$maxAttempts, $decaySeconds];
        }
    };

    $page->triggerRateLimiter();

    expect($page->rateLimitArguments)->toBe([7, 90]);
});
