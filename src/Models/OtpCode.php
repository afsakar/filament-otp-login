<?php

namespace Afsakar\FilamentOtpLogin\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $code
 * @property string $email
 * @property \Carbon\Carbon $expires_at
 */
class OtpCode extends Model
{
    protected $guarded = [];
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isValid()
    {
        return $this->expires_at->isFuture();
    }

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeCode($query, $code)
    {
        return $query->where('code', $code);
    }
}
