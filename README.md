# OTP Login for FilamentPHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/afsakar/filament-otp-login.svg?style=flat-square)](https://packagist.org/packages/afsakar/filament-otp-login)
[![Total Downloads](https://img.shields.io/packagist/dt/afsakar/filament-otp-login.svg?style=flat-square)](https://packagist.org/packages/afsakar/filament-otp-login)

![Screenshot](https://banners.beyondco.de/Filament%20OTP%20Login.png?theme=light&packageManager=composer+require&packageName=afsakar%2Ffilament-otp-login&pattern=architect&style=style_1&description=Simple+OTP+Login+for+FilamentPHP&md=1&showWatermark=0&fontSize=100px&images=login)


This package is an OTP Login for FilamentPHP. It is a simple package that allows you to login to your FilamentPHP application using OTP.

## Installation

You can install the package via composer:

```bash
composer require afsakar/filament-otp-login
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-otp-login-migrations"
php artisan migrate
```

You can publish the config and translations files with:

```bash
php artisan vendor:publish --tag="filament-otp-login-config"
php artisan vendor:publish --tag="filament-otp-login-translations"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-otp-login-views"
```

This is the contents of the published config file:

```php
return [
    'table_name' => 'otp_codes', // Table name to store OTP codes

    'otp_code' => [
        'length' => env('OTP_LOGIN_CODE_LENGTH', 6), // Length of the OTP code
        'expires' => env('OTP_LOGIN_CODE_EXPIRES_SECONDS', 120), // Expiration time of the OTP code in seconds
    ],
];

```

## Usage

Just register the `Afsakar\FilamentOtpLogin\FilamentOtpLoginPlugin` plugin in the your panel provider file.

```php
use Afsakar\FilamentOtpLogin\FilamentOtpLoginPlugin;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->plugins([
                FilamentOtpLoginPlugin::make(),
            ]);
    }
```

If you want to ignore specific user groups from OTP login just implement the `Afsakar\FilamentOtpLogin\Models\Contracts\CanLoginDirectly` trait in your User model.

```php
use Afsakar\FilamentOtpLogin\Models\Contracts\CanLoginDirectly;

class User extends Authenticatable implements CanLoginDirectly
{
    use HasFactory, Notifiable;

    // other user model code

    public function canLoginDirectly(): bool
    {
        return str($this->email)->endsWith('@example.com');
    }
}
```

_*Note:* For medium and large scale applications, you only need to run "php artisan model:prune" command as cron to prevent the otp_code table from bloating and performance issues._

## Custom Login Page

If you want to customize the login page, you can extend the `\Afsakar\FilamentOtpLogin\Filament\Pages\Login` page and set your custom login page to plugin in the panel provider file with `loginPage` method.

```php
<?php

namespace App\Filament\Pages;

use Afsakar\FilamentOtpLogin\Filament\Pages\Login as OtpLogin;
use Illuminate\Contracts\Support\Htmlable;

class OverrideLogin extends OtpLogin
{
    public function getHeading(): string | Htmlable
    {
        return 'Example Login Heading';
    }
}
```

```php
use App\Filament\Pages\OverrideLogin;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->plugins([
                FilamentOtpLoginPlugin::make()
                    ->loginPage(OverrideLogin::class),
            ]);
    }
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Azad Furkan ŞAKAR](https://github.com/afsakar)
- [All Contributors](../../contributors)
- [OTP Input inspiration](https://github.com/rajeshdewle/otp-pin-using-alpine-js-and-tailwindcss)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Supported By

<a href="https://www.jetbrains.com/phpstorm/" target="_blank"><img src="https://res.cloudinary.com/rupadana/image/upload/v1707040287/phpstorm_xjblau.png" width="50px" height="50px"></img></a>
