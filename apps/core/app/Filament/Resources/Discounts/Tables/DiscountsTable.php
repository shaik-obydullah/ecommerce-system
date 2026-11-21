<?php

declare(strict_types=1);

namespace App\Filament\Resources\Discounts\Tables;

use App\Core\Promotions\Enums\DiscountType;
use App\Core\Promotions\Models\Discount;
use App\Core\Support\Money\Currency;
use App\Core\Support\Money\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class DiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (DiscountType $state): string => $state->label()),
                TextColumn::make('value')
                    ->label('Value')
                    ->formatStateUsing(fn (mixed $state, Discount $record): string => $record->type->isPercentage()
                        ? rtrim(rtrim(number_format(((int) $state) / 100, 2, '.', ''), '0'), '.').'%'
                        : Money::of((int) $state, $record->currency ?? Currency::base()->code())->format()),
                TextColumn::make('currency')
                    ->placeholder('Any'),
                TextColumn::make('usage_count')
                    ->label('Used')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('usage_limit')
                    ->label('Limit')
                    ->numeric()
                    ->placeholder('Unlimited'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->placeholder('Always')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->placeholder('Never')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        DiscountType::Percentage->value => DiscountType::Percentage->label(),
                        DiscountType::Fixed->value => DiscountType::Fixed->label(),
                    ]),
                SelectFilter::make('currency')
                    ->options(fn (): array => self::currencyOptions()),
                TernaryFilter::make('is_active')
                    ->label('Active'),
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
