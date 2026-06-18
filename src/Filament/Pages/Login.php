<?php

namespace Afsakar\FilamentOtpLogin\Filament\Pages;

use Afsakar\FilamentOtpLogin\Filament\Forms\OtpInput;
use Afsakar\FilamentOtpLogin\Models\Contracts\CanLoginDirectly;
use Afsakar\FilamentOtpLogin\Models\OtpCode;
use Afsakar\FilamentOtpLogin\Notifications\SendOtpCode;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class Login extends BaseLogin
{
    use Notifiable;
    use WithRateLimiting;

    public ?array $data = [];

    public int $step = 1;

    private string $otpCode = '';

    public string $email = '';

    public int $countDown = 120;

    public function getView(): string
    {
        return 'filament-otp-login::pages.login';
    }

    public function mount(): void
    {

        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();

        $this->countDown = Config::integer('filament-otp-login.otp_code.expires');
    }

    protected function rateLimiter()
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::auth/pages/login.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::auth/pages/login.notifications.throttled') ?: []) ? __('filament-panels::auth/pages/login.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return null;
        }
    }

    public function authenticate(): ?LoginResponse
    {
        $this->rateLimiter();

        $this->verifyCode();

        $this->doLogin();

        return app(LoginResponse::class);
    }

    protected function doLogin(): void
    {
        $data = $this->form->getState();

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        session()->regenerate();
    }

    public function verifyCode(): void
    {
        $code = OtpCode::whereCode($this->data['otp'])->whereEmail($this->data['email'])->first();

        if (! $code) {
            throw ValidationException::withMessages([
                'data.otp' => __('filament-otp-login::translations.validation.invalid_code'),
            ]);
        } elseif (! $code->isValid()) {
            throw ValidationException::withMessages([
                'data.otp' => __('filament-otp-login::translations.validation.expired_code'),
            ]);
        } else {
            $this->dispatch('codeVerified');

            $code->delete();
        }
    }

    public function generateCode(): void
    {
        do {
            $length = Config::integer('filament-otp-login.otp_code.length');

            $code = str_pad(rand(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
        } while (OtpCode::whereCode($code)->whereEmail($this->data['email'])->exists());

        $this->otpCode = $code;

        $data = $this->form->getState();

        OtpCode::updateOrCreate([
            'email' => $data['email'],
        ], [
            'code' => $this->otpCode,
            'expires_at' => now()->addSeconds(Config::integer('filament-otp-login.otp_code.expires')),
        ]);

        $this->dispatch('countDownStarted');
    }

    public function sendOtp(): void
    {

        $this->rateLimiter();

        $data = $this->form->getState();

        $this->checkCredentials($data);
    }

    #[On('resendCode')]
    public function resendCode(): void
    {
        $this->rateLimiter();

        $this->generateCode();

        $this->sendOtpToUser($this->otpCode);
    }

    protected function sendOtpToUser(string $otpCode): void
    {
        $this->email = $this->data['email'];

        $notificationClass = config('filament-otp-login.notification_class', SendOtpCode::class);

        $this->notify(new $notificationClass($otpCode));

        Notification::make()
            ->title(__('filament-otp-login::translations.notifications.title'))
            ->body(__('filament-otp-login::translations.notifications.body', ['seconds' => Config::integer('filament-otp-login.otp_code.expires')]))
            ->success()
            ->send();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    public function otpForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getOtpCodeFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getOtpCodeFormComponent(): Component
    {
        return OtpInput::make('otp')
            ->label(__('filament-otp-login::translations.otp_code'))
            ->hint(new HtmlString('<button type="button" wire:click="goBack()" class="focus:outline-none font-bold focus:underline hover:text-primary-400 text-primary-600 text-sm">' . __('filament-otp-login::translations.view.go_back') . '</button>'))
            ->required();
    }

    public function goBack(): void
    {
        $this->step = 1;
    }

    /**
     * @return array<Action | ActionGroup>
     */
    public function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    /**
     * @return array<Action | ActionGroup>
     */
    public function getOtpFormActions(): array
    {
        return [
            $this->getSendOtpAction(),
        ];
    }

    protected function getSendOtpAction(): Action
    {
        return Action::make('send-otp')
            ->label(__('filament-otp-login::translations.view.verify'))
            ->submit('sendOtp');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('filament-panels::auth/pages/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => $data['email'],
            'password' => $data['password'],
        ];
    }

    protected function checkCanLoginDirectly($data)
    {
        $user = Filament::auth()->getProvider()->retrieveByCredentials($this->getCredentialsFromFormData($data)); // @phpstan-ignore-line

        if (
            ($user instanceof CanLoginDirectly) &&
            ($user->canLoginDirectly())
        ) {
            $this->doLogin();

            $path = Filament::getCurrentPanel()->getPath();

            return redirect()->intended($path);
        } else {
            $this->generateCode();

            $this->sendOtpToUser($this->otpCode);

            $this->step = 2;
        }
    }

    protected function checkCredentials($data): void
    {
        if (! Filament::auth()->validate($this->getCredentialsFromFormData($data))) {
            $this->throwFailureValidationException();
        }

        $this->checkCanLoginDirectly($data);
    }
}
