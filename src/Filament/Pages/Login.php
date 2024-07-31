<?php

namespace Afsakar\FilamentOtpLogin\Filament\Pages;

use Afsakar\FilamentOtpLogin\Filament\Forms\OtpInput;
use Afsakar\FilamentOtpLogin\Models\OtpCode;
use Afsakar\FilamentOtpLogin\Notifications\SendOtpCode;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action as ActionComponent;
use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\ToggleButtons;

class Login extends BaseLogin
{
    use InteractsWithFormActions, Notifiable, WithRateLimiting;

    protected static string $view = 'filament-otp-login::pages.login';

    public ?array $data = [];
    public int $step = 1;
    private int|string $otpCode = '';
    public string $email = '';
    public string $phone = '';
    public int $countDown = 120;

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
        $this->countDown = config('filament-otp-login.otp_code.expires');
    }

    protected function rateLimiter(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(__('filament-panels::pages/auth/login.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->danger()
                ->send();

            return;
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
        $credentials = $this->getCredentialsFromFormData($data);

        if (!Filament::auth()->attempt($credentials, $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();
        if ($user instanceof FilamentUser && !$user->canAccessPanel(Filament::getCurrentPanel())) {
            Filament::auth()->logout();
            $this->throwFailureValidationException();
        }

        session()->regenerate();
    }

    public function verifyCode(): void
    {
        $code = OtpCode::whereCode($this->data['otp'])
            ->where($this->data['login_option'] === 'email' ? 'email' : 'phone', $this->data[$this->data['login_option']])
            ->first();

        if (!$code) {
            throw ValidationException::withMessages([
                'data.otp' => __('filament-otp-login::translations.validation.invalid_code'),
            ]);
        } elseif (!$code->isValid()) {
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
        $data = $this->form->getState();
        $loginOption = $data['login_option'];
        $length = config('filament-otp-login.otp_code.length');

        do {
            $code = str_pad(rand(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
        } while (OtpCode::whereCode($code)->where($loginOption, $data[$loginOption])->exists());

        $this->otpCode = $code;
        OtpCode::updateOrCreate([
            $loginOption => $data[$loginOption],
        ], [
            'code' => $this->otpCode,
            'expires_at' => now()->addSeconds(config('filament-otp-login.otp_code.expires')),
        ]);

        $this->dispatch('countDownStarted');
    }

    public function sendOtp(): void
    {
        $this->rateLimiter();
        $data = $this->form->getState();
        $this->checkCredentials($data);
        $this->generateCode();
        $this->sendOtpToUser($this->otpCode);
        $this->step = 2;
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
        $type = $this->data['login_option'] === 'email'
            ? __('filament-otp-login::translations.email_address')
            : __('filament-otp-login::translations.phone_number');

        $this->notify(new SendOtpCode($this->data['login_option'], $this->phone, $otpCode));

        Notification::make()
            ->title(__('filament-otp-login::translations.notifications.title'))
            ->body(__('filament-otp-login::translations.notifications.body', [
                'seconds' => config('filament-otp-login.otp_code.expires'),
                'type' => $type
            ]))
            ->success()
            ->send();
    }

    public function form(Form $form): Form
    {
        return $form;
    }

    protected function getForms(): array
    {
        $loginOption = config('filament-otp-login.login_option');
        $schema = [];

        if ($loginOption === 'email') {
            $schema[] = $this->getEmailFormComponent();
        } elseif ($loginOption === 'phone') {
            $schema[] = $this->getPhoneFormComponent();
        } else {
            $schema[] = $this->getEmailAndPhoneFormComponent();
        }

        $schema[] = $this->getPasswordFormComponent();
        $schema[] = $this->getRememberFormComponent();

        return [
            'form' => $this->form($this->makeForm()->schema($schema)->statePath('data')),
            'otpForm' => $this->form($this->makeForm()->schema([$this->getOtpCodeFormComponent()])->statePath('data')),
        ];
    }

    protected function getPhoneFormComponent(): Component
    {
        return PhoneInput::make('phone')
            ->label('panel.phone_number')
            ->translateLabel()
            ->required()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getEmailAndPhoneFormComponent(): Component
    {
        return Group::make()
            ->schema([
                ToggleButtons::make('login_option')
                    ->label('')
                    ->options([
                        'email' => __('filament-otp-login::translations.email_address'),
                        'phone' => __('filament-otp-login::translations.phone_number'),
                    ])
                    ->default('email')
                    ->grouped()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, $set) => $set($state === 'phone' ? 'email' : 'phone', null))
                    ->extraAttributes(['style' => 'margin-right: auto; margin-left: auto;']),
                $this->getEmailFormComponent()->hidden(fn ($get) => $get('login_option') !== 'email'),
                $this->getPhoneFormComponent()->hidden(fn ($get) => $get('login_option') !== 'phone'),
            ]);
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

    public function getFormActions(): array
    {
        return [$this->getAuthenticateFormAction()];
    }

    public function getOtpFormActions(): array
    {
        return [$this->getSendOtpAction()];
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
            ->label(__('filament-panels::pages/auth/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            $data['login_option'] => $data[$data['login_option']],
            'password' => $data['password'],
        ];
    }

    protected function checkCredentials(array $data): void
    {
        if (!Filament::auth()->validate($this->getCredentialsFromFormData($data))) {
            $this->throwFailureValidationException();
        }
    }

    protected function throwFailureValidationException(): never
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'data.phone' => __('filament-panels::pages/auth/login.messages.failed'),
            'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}
