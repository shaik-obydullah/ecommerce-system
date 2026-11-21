<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Core\Catalog\Models\Product;
use App\Core\Support\Money\Currency;
use App\Core\Support\Money\Money;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('category.name')
                            ->label('Category'),
                        TextEntry::make('slug'),
                        TextEntry::make('sku')
                            ->label('SKU')
                            ->placeholder('-'),
                        TextEntry::make('short_description')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                        IconEntry::make('is_featured')
                            ->label('Featured')
                            ->boolean(),
                    ])
                    ->columns(3),
                Section::make('Derived pricing and stock value')
                    ->description('Customer price is the highest in-stock sale price. Stock value is admin-only.')
                    ->schema([
                        TextEntry::make('derived_sale_price')
                            ->label('Customer price (base currency)')
                            ->state(function (Product $record): string {
                                $amount = $record->derivedSalePriceAmount();

                                return $amount === null
                                    ? 'Out of stock'
                                    : Money::of($amount, Currency::base())->format();
                            }),
                        TextEntry::make('derived_available_qty')
                            ->label('Available units')
                            ->state(fn (Product $record): int => $record->derivedAvailableQty()),
                        TextEntry::make('stock_value')
                            ->label('Stock value (at buy price)')
                            ->state(fn (Product $record): string => Money::of(
                                $record->stockValueAmount(),
                                Currency::base(),
                            )->format()),
                    ])
                    ->columns(3),
                Section::make('Search engine listing')
                    ->collapsed()
                    ->schema([
                        TextEntry::make('meta_title')
                            ->placeholder('-'),
                        TextEntry::make('meta_description')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2),
            ]);
    }
}
