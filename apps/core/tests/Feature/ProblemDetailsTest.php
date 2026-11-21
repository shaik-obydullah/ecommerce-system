<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('returns RFC 9457 problem details for unknown API routes', function (): void {
    $this->getJson('/api/v1/does-not-exist')
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('status', 404)
        ->assertJsonPath('instance', '/api/v1/does-not-exist')
        ->assertJsonStructure(['type', 'title', 'status', 'detail', 'instance']);
});

it('returns validation errors in the problem details shape', function (): void {
    Route::post('/api/v1/test-validation', function (Request $request): void {
        $request->validate([
            'name' => ['required', 'string'],
        ]);
    });

    $this->postJson('/api/v1/test-validation', [])
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('status', 422)
        ->assertJsonPath('title', 'Validation failed')
        ->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'instance',
            'errors' => ['name'],
        ]);
});

it('returns problem details for unauthenticated API requests', function (): void {
    Route::get('/api/v1/test-auth', fn (): string => 'ok')->middleware('auth:sanctum');

    $this->getJson('/api/v1/test-auth')
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('status', 401);
});
