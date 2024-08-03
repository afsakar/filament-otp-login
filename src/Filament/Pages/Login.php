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
    use InteractsWithFormActions;
    use Notifiable;
    use WithRateLimiting;

    protected static string $view = 'filament-otp-login::pages.login';

    public ?array $data = [];

    public int $step = 1;

    private int | string $otpCode = '';

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

    protected function rateLimiter()
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/login.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/login.notifications.throttled.body', [
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
        $loginOption = config('filament-otp-login.login_option') ?? $this->data['login_option'];
        $identifier = $loginOption === 'email' ? 'email' : 'phone';
        $identifierValue = $this->data[$identifier];
        
        $code = OtpCode::whereCode($this->data['otp'])->where($identifier, $identifierValue)->first();

        if (!$code) {
            throw ValidationException::withMessages([
                'data.otp' => __('filament-otp-login::translations.validation.invalid_code'),
            ]);
        }

        if (!$code->isValid()) {
            throw ValidationException::withMessages([
                'data.otp' => __('filament-otp-login::translations.validation.expired_code'),
            ]);
        }

        $this->dispatch('codeVerified');
        $code->delete();
    }

    public function generateCode(): void
    {
        $data = $this->form->getState();
        $loginOption = config('filament-otp-login.login_option') ?? $data['login_option'];
        $identifier = $loginOption === 'email' ? 'email' : 'phone';
        $identifierValue = $data[$identifier];
        $length = config('filament-otp-login.otp_code.length');
        
        do {
            $code = str_pad(rand(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
        } while (OtpCode::whereCode($code)->where($identifier, $identifierValue)->exists());
    
        $this->otpCode = $code;
    
        OtpCode::updateOrCreate([
            $identifier => $identifierValue,
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
        $loginOption = config('filament-otp-login.login_option') ?? $this->data['login_option'];

        $notificationText = $loginOption === 'email'
            ? __('filament-otp-login::translations.email_address')
            : __('filament-otp-login::translations.phone_number');

        $this->$loginOption = $this->data[$loginOption];

        $this->notify(new SendOtpCode($loginOption, $this->phone ?? null, $otpCode));

        Notification::make()
            ->title(__('filament-otp-login::translations.notifications.title'))
            ->body(__('filament-otp-login::translations.notifications.body', ['seconds' => config('filament-otp-login.otp_code.expires'), 'type' => $notificationText]))
            ->success()
            ->send();
    }

    public function form(Form $form): Form
    {
        return $form;
    }

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        $loginOption = config('filament-otp-login.login_option');

        $schema = [];
        
        if ($loginOption == 'email') {
            $schema[] = $this->getEmailFormComponent();
        } elseif ($loginOption == 'phone') {
            $schema[] = $this->getPhoneFormComponent();
        } else {
            $schema[] = $this->getEmailAndPhoneFormComponent();
        }
        
        $schema[] = $this->getPasswordFormComponent();
        $schema[] = $this->getRememberFormComponent();

        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema($schema)
                    ->statePath('data')
            ),
            'otpForm' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getOtpCodeFormComponent(),
                    ])
                    ->statePath('data')
            ),
        ];
    }

    protected function getPhoneFormComponent(): Component
    {
        return PhoneInput::make('phone')
            ->label('panel.phone_number')
            ->translateLabel()
            ->defaultCountry('IR')
            ->onlyCountries(['ir'])
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
                ->afterStateUpdated(function ($state, $set) {
                    if ($state === 'phone') {
                        $set('phone', null);
                    } elseif ($state === 'email') {
                        $set('email', null);
                    }
                })
                ->extraAttributes(['style' => 'margin-right: auto; margin-left: auto;']),
            $this->getEmailFormComponent()
                ->hidden(fn ($get) => $get('login_option') !== 'email'),
            PhoneInput::make('phone')
                ->label('filament-otp-login::translations.phone_number')
                ->translateLabel()
                ->defaultCountry('IR')
                ->onlyCountries(['ir'])
                ->autofocus()
                ->required()
                ->extraInputAttributes(['tabindex' => 1])
                ->hidden(fn ($get) => $get('login_option') !== 'phone')
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

    protected function goBackAction(): ActionComponent
    {
        return ActionComponent::make('go-back')
            ->label(__('filament-otp-login::translations.view.go_back'))
            ->action(fn () => $this->goBack());
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('filament-panels::pages/auth/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $loginOption = config('filament-otp-login.login_option') ?? $data['login_option'];

        return [
            $loginOption => $data[$loginOption],
            'password' => $data['password'],
        ];
    }

    protected function checkCredentials($data): void
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
