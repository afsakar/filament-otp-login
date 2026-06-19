<?php

use Afsakar\FilamentOtpLogin\Filament\Pages\Login;
use Afsakar\FilamentOtpLogin\FilamentOtpLoginPlugin;

it('uses plugin rate limit values', function () {
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

        protected function plugin(): FilamentOtpLoginPlugin
        {
            return FilamentOtpLoginPlugin::make()->rateLimit(7, 90);
        }
    };

    $page->triggerRateLimiter();

    expect($page->rateLimitArguments)->toBe([7, 90]);
});
