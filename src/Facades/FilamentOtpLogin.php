<?php

namespace Afsakar\FilamentOtpLogin\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Afsakar\FilamentOtpLogin\FilamentOtpLogin
 */
class FilamentOtpLogin extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Afsakar\FilamentOtpLogin\FilamentOtpLogin::class;
    }
}
