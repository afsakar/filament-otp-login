<?php

namespace Afsakar\FilamentOtpLogin\Filament\Pages;

use Afsakar\FilamentOtpLogin\Filament\Forms\OtpInput;
use Afsakar\FilamentOtpLogin\Models\OtpCode;
use Afsakar\FilamentOtpLogin\Notifications\SendOtpCode;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Events\Auth\Registered;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Notifications\Auth\VerifyEmail;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Filament\Pages\Auth\Register as BaseRegister;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Illuminate\Notifications\Notifiable;



class Register extends BaseRegister
{
    use CanUseDatabaseTransactions;
    use InteractsWithFormActions;
    use WithRateLimiting;
    use Notifiable;

    protected static string $view = 'filament-otp-login::pages.register';

    public ?array $data = [];

    protected string $userModel;

    public int $step = 1;

    private int | string $otpCode = '';

    public string $phone = '';

    public int $countDown = 120;

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->callHook('beforeFill');

        $this->form->fill();

        $this->callHook('afterFill');

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

    public function authenticate(): ?RegistrationResponse
    {
        $this->rateLimiter();

        $this->verifyCode();
        
        $this->doRegister();

        return app(RegistrationResponse::class);
    }

    protected function doRegister(): void
    {
        $user = $this->wrapInDatabaseTransaction(function () {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

            $this->callHook('beforeRegister');

            $user = $this->handleRegistration($data);

            $this->form->model($user)->saveRelationships();

            $this->callHook('afterRegister');

            return $user;
        });

        event(new Registered($user));

        $this->sendEmailVerificationNotification($user);

        Filament::auth()->login($user);

        session()->regenerate();
    }

    public function verifyCode(): void
    {
        $identifier = config('filament-otp-login.register_option');
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
        $registerOption = config('filament-otp-login.register_option');
        $identifier = $registerOption === 'email' ? 'email' : 'phone';
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
        $registerOption = config('filament-otp-login.register_option');

        $notificationText = $registerOption === 'email'
            ? __('filament-otp-login::translations.email_address')
            : __('filament-otp-login::translations.phone_number');

        $this->$registerOption = $this->data[$registerOption];

        $this->notify(new SendOtpCode($registerOption, $this->phone ?? null, $otpCode));

        Notification::make()
            ->title(__('filament-otp-login::translations.notifications.title'))
            ->body(__('filament-otp-login::translations.notifications.body', ['seconds' => config('filament-otp-login.otp_code.expires'), 'type' => $notificationText]))
            ->success()
            ->send();
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('filament-panels::pages/auth/register.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(array_key_exists('body', __('filament-panels::pages/auth/register.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/register.notifications.throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]) : null)
            ->danger();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        return $this->getUserModel()::create($data);
    }

    protected function sendEmailVerificationNotification(Model $user): void
    {
        if (! $user instanceof MustVerifyEmail) {
            return;
        }

        if ($user->hasVerifiedEmail()) {
            return;
        }

        if (! method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
        }

        $notification = app(VerifyEmail::class);
        $notification->url = Filament::getVerifyEmailUrl($user);

        $user->notify($notification);
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
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPhoneFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->statePath('data'),
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

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label(__('filament-panels::pages/auth/register.form.name.label'))
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('filament-otp-login::translations.email_address')
            ->translateLabel()
            ->email()
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel());
    }

    protected function getPhoneFormComponent(): Component
    {
        return PhoneInput::make('phone')
            ->label('panel.phone_number')
            ->translateLabel()
            ->defaultCountry('IR')
            ->onlyCountries(['ir'])
            ->required()
            ->unique($this->getUserModel());
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/register.form.password.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule(Password::default())
            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
            ->same('passwordConfirmation')
            ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute'));
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label(__('filament-panels::pages/auth/register.form.password_confirmation.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->dehydrated(false);
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label(__('filament-panels::pages/auth/register.actions.login.label'))
            ->url(filament()->getLoginUrl());
    }

    protected function getUserModel(): string
    {
        if (isset($this->userModel)) {
            return $this->userModel;
        }

        /** @var SessionGuard $authGuard */
        $authGuard = Filament::auth();

        /** @var EloquentUserProvider $provider */
        $provider = $authGuard->getProvider();

        return $this->userModel = $provider->getModel();
    }

    public function getTitle(): string | Htmlable
    {
        return __('filament-panels::pages/auth/register.title');
    }

    public function getHeading(): string | Htmlable
    {
        return __('filament-panels::pages/auth/register.heading');
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getRegisterFormAction(),
        ];
    }

    public function getRegisterFormAction(): Action
    {
        return Action::make('register')
            ->label(__('filament-panels::pages/auth/register.form.actions.register.label'))
            ->submit('register');
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

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        return $data;
    }
}
