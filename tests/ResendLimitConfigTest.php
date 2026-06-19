<?php

use Afsakar\FilamentOtpLogin\Filament\Pages\Login;
use Afsakar\FilamentOtpLogin\FilamentOtpLoginPlugin;

it('uses plugin resend limit values', function () {
    $page = new class extends Login
    {
        public array $rateLimitArguments = [];

        public function triggerResendRateLimiter(): void
        {
            $this->resendRateLimiter();
        }

        protected function rateLimit($maxAttempts, $decaySeconds = 60, $method = null, $component = null)
        {
            $this->rateLimitArguments = [$maxAttempts, $decaySeconds, $method];
        }

        protected function plugin(): FilamentOtpLoginPlugin
        {
            return FilamentOtpLoginPlugin::make()->resendLimit(2, 180);
        }
    };

    $page->triggerResendRateLimiter();

    expect($page->rateLimitArguments)->toBe([2, 180, 'resendCode']);
});
