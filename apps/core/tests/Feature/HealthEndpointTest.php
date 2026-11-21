<?php

declare(strict_types=1);

it('reports a healthy database and cache', function (): void {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJsonPath('data.status', 'ok')
        ->assertJsonPath('data.checks.database.status', 'ok')
        ->assertJsonPath('data.checks.cache.status', 'ok')
        ->assertJsonStructure([
            'data' => [
                'status',
                'checks' => [
                    'database' => ['status', 'latency_ms'],
                    'cache' => ['status', 'latency_ms'],
                ],
                'timestamp',
            ],
        ]);
});
