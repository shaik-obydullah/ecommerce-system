<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Support\Settings\Settings;
use Illuminate\Database\Seeder;

final class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(Settings::class);

        $settings->set('store.name', (string) config('ecommerce.store_name'), 'string', 'store');
        $settings->set('store.timezone', (string) config('ecommerce.store_timezone'), 'string', 'store');
        $settings->set('currency.base', (string) config('ecommerce.base_currency'), 'string', 'currency');
        $settings->set('currency.default', (string) config('ecommerce.default_currency'), 'string', 'currency');
        $settings->set('currency.supported', config('ecommerce.supported_currencies', []), 'array', 'currency');
        $settings->set('checkout.currency_locked', true, 'boolean', 'checkout');
        $settings->set('checkout.guest_checkout', true, 'boolean', 'checkout');
        $settings->set('checkout.stock_reservation_minutes', 15, 'integer', 'checkout');
        $settings->set('catalog.products_per_page', 24, 'integer', 'catalog');
        $settings->set('storefront.mode', (string) config('ecommerce.storefront_mode'), 'string', 'storefront');
    }
}
