<?php

use Afsakar\FilamentOtpLogin\FilamentOtpLoginPlugin;
use Afsakar\FilamentOtpLogin\Notifications\SendOtpCode;

it('configures otp login from the plugin', function () {
    $plugin = FilamentOtpLoginPlugin::make()
        ->tableName('panel_otps')
        ->identifierColumn('identifier')
        ->identifierFormField('phone', 'Phone', 'tel')
        ->userIdentifierColumn('phone')
        ->userModel('App\\Models\\Admin')
        ->otpCode(8, 300)
        ->rateLimit(7, 90)
        ->resendLimit(2, 180)
        ->passwordless()
        ->notification(SendOtpCode::class);

    expect($plugin->getTableName())->toBe('panel_otps')
        ->and($plugin->getIdentifierColumn())->toBe('identifier')
        ->and($plugin->getIdentifierFormField())->toBe('phone')
        ->and($plugin->getIdentifierLabel())->toBe('Phone')
        ->and($plugin->getIdentifierType())->toBe('tel')
        ->and($plugin->getUserIdentifierColumn())->toBe('phone')
        ->and($plugin->getUserModel())->toBe('App\\Models\\Admin')
        ->and($plugin->getOtpCodeLength())->toBe(8)
        ->and($plugin->getOtpCodeExpiresIn())->toBe(300)
        ->and($plugin->getRateLimitAttempts())->toBe(7)
        ->and($plugin->getRateLimitDecaySeconds())->toBe(90)
        ->and($plugin->getResendLimitAttempts())->toBe(2)
        ->and($plugin->getResendLimitDecaySeconds())->toBe(180)
        ->and($plugin->isPasswordless())->toBeTrue()
        ->and($plugin->getNotificationClass())->toBe(SendOtpCode::class);
});

it('uses identifier in the fresh migration and renames old email columns', function () {
    $createMigration = file_get_contents(__DIR__ . '/../database/migrations/create_filament_otp_login_table.php.stub');
    $renameMigration = file_get_contents(__DIR__ . '/../database/migrations/rename_filament_otp_login_email_column.php.stub');

    expect($createMigration)
        ->toContain("string('identifier')")
        ->not->toContain("string('email')")
        ->and($renameMigration)
        ->toContain("renameColumn('email', 'identifier')");
});
