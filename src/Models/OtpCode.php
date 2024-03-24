<?php

namespace Afsakar\FilamentOtpLogin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $code
 * @property string $email
 * @property \Carbon\Carbon $expires_at
 */
class OtpCode extends Model
{
    use MassPrunable;

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('filament-otp-login.table_name'));
    }

    public function prunable(): Builder
    {
        return static::where('expires_at', '<=', now()->subDay()->startOfDay());
    }

    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }
}
