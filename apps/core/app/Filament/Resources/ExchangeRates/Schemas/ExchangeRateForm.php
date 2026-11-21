<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExchangeRates\Schemas;

use App\Core\Support\Money\Currency;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ExchangeRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Exchange rate')
                    ->description('Rates are stored as exact decimals. The latest row for a pair is authoritative.')
                    ->schema([
                        Select::make('base_currency')
                            ->label('From currency')
                            ->options(self::currencyOptions())
                            ->default(Currency::base()->code())
                            ->required()
                            ->searchable(),
                        Select::make('quote_currency')
                            ->label('To currency')
                            ->options(self::currencyOptions())
                            ->required()
                            ->searchable(),
                        TextInput::make('rate')
                            ->required()
                            ->numeric()
                            ->minValue(0.0000000001)
                            ->step('0.0000000001')
                            ->helperText('One unit of the source currency equals this many units of the target currency.'),
                        TextInput::make('source')
                            ->maxLength(100)
                            ->placeholder('manual'),
                        DateTimePicker::make('fetched_at')
                            ->required()
                            ->default(now()),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function currencyOptions(): array
    {
        $options = [];

        foreach (Currency::supportedCodes() as $code) {
            $currency = Currency::fromCode($code);
            $options[$code] = $code.' · '.$currency->symbol();
        }

        return $options;
    }
}
