<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\RelationManagers;

use App\Core\Inventory\Models\Stock;
use App\Core\Support\Money\Currency;
use App\Core\Support\Money\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Manages the physical stock lots for a product.
 *
 * Buy and sale prices are stored in minor units of the store's base currency.
 * The form accepts decimal values and converts them before saving so no float
 * ever reaches the database.
 */
final class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $title = 'Stock lots';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Stock lot')
                    ->schema([
                        TextInput::make('reference')
                            ->maxLength(100)
                            ->placeholder('LOT-0001'),
                        DateTimePicker::make('received_at')
                            ->required()
                            ->default(now()),
                        TextInput::make('qty_on_hand')
                            ->label('Quantity on hand')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('qty_reserved')
                            ->label('Quantity reserved')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(fn (Get $get): int => (int) $get('qty_on_hand'))
                            ->default(0),
                        TextInput::make('buy_price_amount')
                            ->label('Buy price')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix(Currency::base()->symbol())
                            ->formatStateUsing(fn (mixed $state): string => $state === null
                                ? '0.00'
                                : Money::of((int) $state, Currency::base())->toDecimalString())
                            ->dehydrateStateUsing(fn (mixed $state): int => Money::fromDecimal(
                                (string) $state,
                                Currency::base(),
                            )->amount())
                            ->helperText('Recorded in '.Currency::base()->code().'.'),
                        TextInput::make('sale_price_amount')
                            ->label('Sale price')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix(Currency::base()->symbol())
                            ->formatStateUsing(fn (mixed $state): string => $state === null
                                ? '0.00'
                                : Money::of((int) $state, Currency::base())->toDecimalString())
                            ->dehydrateStateUsing(fn (mixed $state): int => Money::fromDecimal(
                                (string) $state,
                                Currency::base(),
                            )->amount())
                            ->helperText('The customer price is derived from the highest in-stock sale price.'),
                        Hidden::make('currency')
                            ->default(Currency::base()->code()),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]))
            ->defaultSort('received_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->placeholder('No reference')
                    ->searchable(),
                TextColumn::make('received_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('qty_on_hand')
                    ->label('On hand')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('qty_reserved')
                    ->label('Reserved')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('qty_available')
                    ->label('Available')
                    ->state(fn (Stock $record): int => $record->qtyAvailable())
                    ->numeric(),
                TextColumn::make('buy_price_amount')
                    ->label('Buy price')
                    ->formatStateUsing(fn (mixed $state): string => Money::of(
                        (int) $state,
                        Currency::base(),
                    )->format())
                    ->sortable(),
                TextColumn::make('sale_price_amount')
                    ->label('Sale price')
                    ->formatStateUsing(fn (mixed $state): string => Money::of(
                        (int) $state,
                        Currency::base(),
                    )->format())
                    ->sortable(),
                TextColumn::make('stock_value')
                    ->label('Stock value')
                    ->state(fn (Stock $record): string => Money::of(
                        $record->stockValueAmount(),
                        Currency::base(),
                    )->format()),
                TextColumn::make('currency')
                    ->badge(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add stock lot'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
