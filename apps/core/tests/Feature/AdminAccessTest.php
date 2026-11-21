<?php

declare(strict_types=1);

use App\Models\Admin;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('creates admins with hashed passwords', function (): void {
    $admin = Admin::factory()->create(['email' => 'owner@example.com']);

    expect($admin->email)->toBe('owner@example.com')
        ->and($admin->password)->not->toBe('password')
        ->and(password_verify('password', $admin->password))->toBeTrue();
});

it('allows admins to access the admin panel', function (): void {
    $admin = Admin::factory()->create();

    expect($admin->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

it('allows only authenticated admins to view horizon', function (): void {
    $admin = Admin::factory()->create();

    expect(Gate::allows('viewHorizon'))->toBeFalse();

    $this->actingAs($admin, 'admin');

    expect(Gate::allows('viewHorizon'))->toBeTrue();
});
