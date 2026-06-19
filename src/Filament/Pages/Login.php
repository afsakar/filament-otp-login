<?php

namespace Afsakar\FilamentOtpLogin\Filament\Pages;

use Afsakar\FilamentOtpLogin\FilamentOtpLoginPlugin;
use Afsakar\FilamentOtpLogin\Models\Contracts\CanLoginDirectly;
use Afsakar\FilamentOtpLogin\Models\OtpCode;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Forms\Components\TextInput;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class Login extends BaseLogin
{
    use Notifiable;
    use WithRateLimiting;

    public ?array $data = [];

    protected int $step = 1;

    private string $otpCode = '';

    public string $email = '';

    public string $identifier = '';

    public int $countDown = 120;

    protected function plugin(): FilamentOtpLoginPlugin
    {
        return FilamentOtpLoginPlugin::get();
    }

    protected function identifier(): string
    {
        return (string) ($this->data[$this->plugin()->getIdentifierFormField()] ?? '');
    }

    public function getView(): string
    {
        return 'filament-otp-login::pages.login';
    }

    public function getStep(): int
    {
        return $this->step;
    }

    protected function isPasswordless(): bool
    {
        return $this->plugin()->isPasswordless();
    }

    public function mount(): void
    {

        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();

        $this->countDown = $this->plugin()->getOtpCodeExpiresIn();
    }

    protected function rateLimiter()
    {
        try {
            $this->rateLimit(
                $this->plugin()->getRateLimitAttempts(),
                $this->plugin()->getRateLimitDecaySeconds(),
            );
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

    protected function resendRateLimiter(): void
    {
        $this->rateLimit(
            $this->plugin()->getResendLimitAttempts(),
            $this->plugin()->getResendLimitDecaySeconds(),
            method: 'resendCode',
        );
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

        if ($this->isPasswordless()) {
            $user = $this->getUserFromFormData($data);

            Filament::auth()->login($user, $data['remember'] ?? false);

            session()->regenerate();

            return;
        }

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        $this->ensureUserCanAccessPanel($user);

        session()->regenerate();
    }

    public function verifyCode(): void
    {
        $code = OtpCode::query()
            ->where($this->plugin()->getIdentifierColumn(), $this->identifier())
            ->first();

        if ((! $code) || (! Hash::check($this->data['otp'], $code->code))) {
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
        $length = $this->plugin()->getOtpCodeLength();

        $this->otpCode = str_pad(random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);

        OtpCode::updateOrCreate([
            $this->plugin()->getIdentifierColumn() => $this->identifier(),
        ], [
            'code' => Hash::make($this->otpCode),
            'expires_at' => now()->addSeconds($this->plugin()->getOtpCodeExpiresIn()),
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
        $this->resendRateLimiter();

        $this->generateCode();

        $this->sendOtpToUser($this->otpCode);
    }

    protected function sendOtpToUser(string $otpCode): void
    {
        $this->identifier = $this->identifier();
        $this->email = $this->identifier;

        $notificationClass = $this->plugin()->getNotificationClass();

        $this->notify(new $notificationClass($otpCode, $this->plugin()->getOtpCodeExpiresIn()));

        Notification::make()
            ->title(__('filament-otp-login::translations.notifications.title'))
            ->body(__('filament-otp-login::translations.notifications.body', ['seconds' => $this->plugin()->getOtpCodeExpiresIn()]))
            ->success()
            ->send();
    }

    public function form(Schema $schema): Schema
    {
        $components = [
            $this->getEmailFormComponent(),
        ];

        if (! $this->isPasswordless()) {
            $components[] = $this->getPasswordFormComponent();
        }

        $components[] = $this->getRememberFormComponent();

        return $schema
            ->components($components);
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
        return OneTimeCodeInput::make('otp')
            ->label(__('filament-otp-login::translations.otp_code'))
            ->length($this->plugin()->getOtpCodeLength())
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
        $credentials = [
            $this->plugin()->getUserIdentifierColumn() => $data[$this->plugin()->getIdentifierFormField()],
        ];

        if ($this->isPasswordless()) {
            return $credentials;
        }

        return $credentials + ['password' => $data['password']];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function getUserFromFormData(array $data): Authenticatable
    {
        $userModel = $this->plugin()->getUserModel();

        $user = $userModel::query()
            ->where($this->plugin()->getUserIdentifierColumn(), $data[$this->plugin()->getIdentifierFormField()])
            ->first();

        if (! $user) {
            $this->throwFailureValidationException();
        }

        $this->ensureUserCanAccessPanel($user);

        return $user;
    }

    protected function getEmailFormComponent(): Component
    {
        $input = TextInput::make($this->plugin()->getIdentifierFormField())
            ->label($this->plugin()->getIdentifierLabel())
            ->required()
            ->autocomplete()
            ->autofocus();

        return match ($this->plugin()->getIdentifierType()) {
            'email' => $input->email(),
            'tel', 'phone' => $input->tel(),
            default => $input,
        };
    }

    public function routeNotificationForMail(): string
    {
        return $this->identifier;
    }

    public function routeNotificationForVonage(): string
    {
        return $this->identifier;
    }

    protected function ensureUserCanAccessPanel(?Authenticatable $user): void
    {
        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }
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
        if ($this->isPasswordless()) {
            $this->getUserFromFormData($data);

            $this->generateCode();

            $this->sendOtpToUser($this->otpCode);

            $this->step = 2;

            return;
        }

        if (! Filament::auth()->validate($this->getCredentialsFromFormData($data))) {
            $this->throwFailureValidationException();
        }

        $this->checkCanLoginDirectly($data);
    }
}
