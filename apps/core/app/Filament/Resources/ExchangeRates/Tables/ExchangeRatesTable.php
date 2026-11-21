<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExchangeRates\Tables;

use App\Core\Support\Money\Currency;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ExchangeRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fetched_at', 'desc')
            ->columns([
                TextColumn::make('base_currency')
                    ->label('From')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quote_currency')
                    ->label('To')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rate')
                    ->numeric(decimalPlaces: 10)
                    ->sortable(),
                TextColumn::make('source')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('fetched_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('base_currency')
                    ->label('From currency')
                    ->options(fn (): array => self::currencyOptions()),
                SelectFilter::make('quote_currency')
                    ->label('To currency')
                    ->options(fn (): array => self::currencyOptions()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
