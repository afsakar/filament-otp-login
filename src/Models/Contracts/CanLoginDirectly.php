<?php

namespace Afsakar\FilamentOtpLogin\Models\Contracts;

interface CanLoginDirectly
{
    public function canLoginDirectly(): bool;
}
