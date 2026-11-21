<?php

declare(strict_types=1);

use App\Core\Promotions\Enums\DiscountType;
use App\Core\Promotions\Models\Discount;
use App\Filament\Resources\Discounts\Pages\CreateDiscount;
use App\Models\Admin;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('allows admins to browse coupons', function (): void {
    Discount::factory()->percentage(1_000)->create(['code' => 'WELCOME10']);
    Discount::factory()->fixed(500)->create(['code' => 'TAKE5']);

    $this->actingAs(Admin::factory()->create(), 'admin');

    $this->get('/admin/discounts')
        ->assertOk()
        ->assertSee('WELCOME10')
        ->assertSee('10%')
        ->assertSee('TAKE5')
        ->assertSee('$5.00');
});

it('creates a percentage coupon from the admin panel', function (): void {
    $this->actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(CreateDiscount::class)
        ->fillForm([
            'code' => 'spring15',
            'name' => 'Spring 15% off',
            'type' => DiscountType::Percentage->value,
            'value' => 1_500,
            'min_subtotal_amount' => 5_000,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('discounts', [
        'code' => 'SPRING15',
        'name' => 'Spring 15% off',
        'type' => DiscountType::Percentage->value,
        'value' => 1_500,
        'currency' => null,
        'min_subtotal_amount' => 5_000,
        'is_active' => true,
    ]);
});

it('requires a currency for fixed coupons', function (): void {
    $this->actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(CreateDiscount::class)
        ->fillForm([
            'code' => 'FLAT5',
            'name' => 'Flat $5 off',
            'type' => DiscountType::Fixed->value,
            'value' => 500,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['currency' => 'required']);
});

it('rejects a duplicate coupon code regardless of case', function (): void {
    Discount::factory()->create(['code' => 'WELCOME10']);

    $this->actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(CreateDiscount::class)
        ->fillForm([
            'code' => 'welcome10',
            'name' => 'Duplicate welcome',
            'type' => DiscountType::Percentage->value,
            'value' => 1_000,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => 'unique']);

    expect(Discount::query()->where('code', 'WELCOME10')->count())->toBe(1);
});
