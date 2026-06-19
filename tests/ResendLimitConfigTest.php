<?php

use Afsakar\FilamentOtpLogin\Filament\Pages\Login;

it('uses configured resend limit values', function () {
    config()->set('filament-otp-login.resend_limit.attempts', 2);
    config()->set('filament-otp-login.resend_limit.decay_seconds', 180);

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
    };

    $page->triggerResendRateLimiter();

    expect($page->rateLimitArguments)->toBe([2, 180, 'resendCode']);
});
