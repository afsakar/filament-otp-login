<?php

namespace Afsakar\FilamentOtpLogin\Models;

use Afsakar\FilamentOtpLogin\FilamentOtpLoginPlugin;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * @property string $code
 * @property string $identifier
 * @property Carbon $expires_at
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

        try {
            $this->setTable(FilamentOtpLoginPlugin::get()->getTableName());
        } catch (Throwable) {
            $this->setTable('otp_codes');
        }
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
