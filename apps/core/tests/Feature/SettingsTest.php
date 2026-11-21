<?php

declare(strict_types=1);

use App\Core\Support\Settings\Settings;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists and retrieves settings', function (): void {
    $settings = app(Settings::class);

    $settings->set('store.name', 'Acme Store', 'string', 'store');

    expect($settings->get('store.name'))->toBe('Acme Store')
        ->and($settings->get('missing.key', 'fallback'))->toBe('fallback');

    $settings->flush();

    expect(app(Settings::class)->get('store.name'))->toBe('Acme Store');
});

it('seeds the store configuration', function (): void {
    $this->seed(SettingsSeeder::class);

    $settings = app(Settings::class);

    expect($settings->get('currency.base'))->toBe(config('ecommerce.base_currency'))
        ->and($settings->get('currency.supported'))->toBe(config('ecommerce.supported_currencies'))
        ->and($settings->get('checkout.currency_locked'))->toBeTrue();
});
