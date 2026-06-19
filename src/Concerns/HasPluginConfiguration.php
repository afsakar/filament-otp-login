<?php

namespace Afsakar\FilamentOtpLogin\Concerns;

use Afsakar\FilamentOtpLogin\Notifications\SendOtpCode;

trait HasPluginConfiguration
{
    protected string $tableName = 'otp_codes';

    protected string $identifierColumn = 'identifier';

    protected string $identifierFormField = 'email';

    protected string $identifierLabel = 'Email';

    protected string $identifierType = 'email';

    protected string $userIdentifierColumn = 'email';

    protected string $userModel = '';

    protected int $otpCodeLength = 6;

    protected int $otpCodeExpiresIn = 120;

    protected int $rateLimitAttempts = 5;

    protected int $rateLimitDecaySeconds = 60;

    protected int $resendLimitAttempts = 3;

    protected int $resendLimitDecaySeconds = 300;

    protected bool $passwordless = false;

    protected string $notification = SendOtpCode::class;

    public function tableName(string $tableName): static
    {
        $this->tableName = $tableName;

        return $this;
    }

    public function identifierColumn(string $column): static
    {
        $this->identifierColumn = $column;

        return $this;
    }

    public function identifierFormField(string $name, string $label = 'Email', string $type = 'email'): static
    {
        $this->identifierFormField = $name;
        $this->identifierLabel = $label;
        $this->identifierType = $type;

        return $this;
    }

    public function userIdentifierColumn(string $column): static
    {
        $this->userIdentifierColumn = $column;

        return $this;
    }

    public function userModel(string $model): static
    {
        $this->userModel = $model;

        return $this;
    }

    public function otpCode(int $length = 6, int $expiresIn = 120): static
    {
        $this->otpCodeLength = $length;
        $this->otpCodeExpiresIn = $expiresIn;

        return $this;
    }

    public function rateLimit(int $attempts = 5, int $decaySeconds = 60): static
    {
        $this->rateLimitAttempts = $attempts;
        $this->rateLimitDecaySeconds = $decaySeconds;

        return $this;
    }

    public function resendLimit(int $attempts = 3, int $decaySeconds = 300): static
    {
        $this->resendLimitAttempts = $attempts;
        $this->resendLimitDecaySeconds = $decaySeconds;

        return $this;
    }

    public function passwordless(bool $condition = true): static
    {
        $this->passwordless = $condition;

        return $this;
    }

    public function notification(string $notification): static
    {
        $this->notification = $notification;

        return $this;
    }

    public function getUserModel(): string
    {
        return $this->userModel ?: config('auth.providers.users.model', 'App\\Models\\User');
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getIdentifierColumn(): string
    {
        return $this->identifierColumn;
    }

    public function getIdentifierFormField(): string
    {
        return $this->identifierFormField;
    }

    public function getIdentifierLabel(): string
    {
        return $this->identifierLabel;
    }

    public function getIdentifierType(): string
    {
        return $this->identifierType;
    }

    public function getUserIdentifierColumn(): string
    {
        return $this->userIdentifierColumn;
    }

    public function getOtpCodeLength(): int
    {
        return $this->otpCodeLength;
    }

    public function getOtpCodeExpiresIn(): int
    {
        return $this->otpCodeExpiresIn;
    }

    public function getRateLimitAttempts(): int
    {
        return $this->rateLimitAttempts;
    }

    public function getRateLimitDecaySeconds(): int
    {
        return $this->rateLimitDecaySeconds;
    }

    public function getResendLimitAttempts(): int
    {
        return $this->resendLimitAttempts;
    }

    public function getResendLimitDecaySeconds(): int
    {
        return $this->resendLimitDecaySeconds;
    }

    public function isPasswordless(): bool
    {
        return $this->passwordless;
    }

    public function getNotificationClass(): string
    {
        return $this->notification;
    }
}
