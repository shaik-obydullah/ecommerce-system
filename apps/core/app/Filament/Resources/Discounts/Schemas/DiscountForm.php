<?php

declare(strict_types=1);

namespace App\Filament\Resources\Discounts\Schemas;

use App\Core\Promotions\Enums\DiscountType;
use App\Core\Support\Money\Currency;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Coupon')
                    ->description('Coupons are validated and priced server-side. Clients only submit the code.')
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->maxLength(64)
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                $set('code', Str::upper(trim((string) $state)));
                            })
                            ->helperText('Stored uppercase. Customers enter it on the cart page.'),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->options(self::typeOptions())
                            ->default(DiscountType::Percentage->value)
                            ->required()
                            ->live(),
                        TextInput::make('value')
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->helperText(fn (Get $get): string => $get('type') === DiscountType::Fixed->value
                                ? 'Amount in minor units, for example 500 = $5.00.'
                                : 'Basis points, for example 1000 = 10% and 1250 = 12.5%.'),
                        Select::make('currency')
                            ->options(self::currencyOptions())
                            ->placeholder('Any currency')
                            ->helperText('Required for fixed-amount coupons; percentage coupons work in every currency.')
                            ->visible(fn (Get $get): bool => $get('type') === DiscountType::Fixed->value)
                            ->required(fn (Get $get): bool => $get('type') === DiscountType::Fixed->value),
                        TextInput::make('min_subtotal_amount')
                            ->label('Minimum subtotal')
                            ->integer()
                            ->minValue(0)
                            ->helperText('Optional minimum cart subtotal in minor units.'),
                        Toggle::make('is_active')
                            ->label('Available at checkout')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Schedule and limits')
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Starts at')
                            ->seconds(false),
                        DateTimePicker::make('ends_at')
                            ->label('Ends at')
                            ->seconds(false),
                        TextInput::make('usage_limit')
                            ->integer()
                            ->minValue(1)
                            ->helperText('Total redemptions allowed. Leave blank for unlimited.'),
                        TextInput::make('per_user_limit')
                            ->label('Per-customer limit')
                            ->integer()
                            ->minValue(1)
                            ->helperText('Applies to authenticated customers. Leave blank for unlimited.'),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function typeOptions(): array
    {
        $options = [];

        foreach (DiscountType::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private static function currencyOptions(): array
    {
        $options = [];

        foreach (Currency::supportedCodes() as $code) {
            $options[$code] = $code;
        }

        return $options;
    }
}
