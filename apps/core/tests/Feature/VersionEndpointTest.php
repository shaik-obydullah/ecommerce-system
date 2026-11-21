<?php

declare(strict_types=1);

it('returns build and runtime metadata', function (): void {
    $this->getJson('/api/v1/version')
        ->assertOk()
        ->assertJsonPath('data.name', config('app.name'))
        ->assertJsonPath('data.version', config('ecommerce.version'))
        ->assertJsonPath('data.storefront_mode', config('ecommerce.storefront_mode'))
        ->assertJsonPath('data.api_version', 'v1')
        ->assertJsonStructure([
            'data' => [
                'name',
                'version',
                'commit',
                'environment',
                'storefront_mode',
                'api_version',
                'php_version',
                'laravel_version',
            ],
        ]);
});
