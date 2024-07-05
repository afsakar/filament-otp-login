<?php

namespace Afsakar\FilamentOtpLogin;

use Afsakar\FilamentOtpLogin\Commands\PruneOtpCodes;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentOtpLoginServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-otp-login';

    public static string $viewNamespace = 'filament-otp-login';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('afsakar/filament-otp-login');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        //
    }

    protected function getAssetPackageName(): ?string
    {
        return 'afsakar/filament-otp-login';
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            PruneOtpCodes::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_filament_otp_login_table',
        ];
    }
}
