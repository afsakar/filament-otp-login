<x-filament-panels::page.simple>
    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-panels::auth/pages/login.actions.register.before') }}

            {{ $this->registerAction }}
        </x-slot>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    @switch($this->getStep())

        @case(1)
            <form wire:submit="sendOtp" class="space-y-6">
                {{ $this->form }}

                <x-filament::button
                    type="submit"
                    label="Giriş"
                    class="flex items-center justify-center gap-2 w-full"
                    wire:loading.attr="disabled"
                >
                    <x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="sendOtp" />
                    <span wire:loading.remove wire:target="sendOtp">
                        {{ __('filament-panels::auth/pages/login.form.actions.authenticate.label') }}
                    </span>
                </x-filament::button>
            </form>
            @break
        @default

            <form wire:submit="authenticate" class="space-y-6">
                {{ $this->otpForm }}
                <x-filament::actions
                    :actions="$this->getOtpFormActions()"
                    :full-width="true"
                />

                <div wire:ignore x-data="{
                        timeLeft: $wire.countDown,
                        timerRunning: false,
                        resendCode() {
                            this.timeLeft = $wire.countDown;
                            this.$refs.resendLink.classList.add('hidden');
                            this.$refs.timerWrapper.classList.remove('hidden');
                            this.startTimer();
                            this.$dispatch('resendCode');
                        },
                        startTimer() {
                            this.timerRunning = true;
                            const interval = setInterval(() => {
                                if (this.timeLeft <= 0) {
                                    clearInterval(interval);
                                    this.timerRunning = false;
                                    this.$refs.resendLink.classList.remove('hidden');
                                    this.$refs.timerWrapper.classList.add('hidden');
                                }
                                this.timeLeft -= 1;
                                this.$refs.timeLeft.value = this.timeLeft;
                            }, 1000);
                        },
                        init() {
                            this.startTimer();
                            document.addEventListener('countDownStarted', () => {
                                this.startTimer();
                            });
                        }
                    }">
                    <div x-show="timerRunning" class="timer font-semibold resend-link text-end text-primary-600 text-sm" x-ref="timerWrapper">
                        <span x-text="timeLeft"></span> {{ __('filament-otp-login::translations.view.time_left') }}
                    </div>
                    <a x-on:click="resendCode" x-show="!timerRunning" x-ref="resendLink" class="hidden cursor-pointer font-semibold resend-link text-end text-primary-600 text-sm">
                        {{ __('filament-otp-login::translations.view.resend_code') }}
                    </a>
                    <input type="hidden" x-ref="timeLeft" name="timeLeft" />
                </div>

            </form>
    @endswitch

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
</x-filament-panels::page.simple>
