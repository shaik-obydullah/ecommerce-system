<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product')
                    ->description('Products hold catalog copy only. Quantity and prices live on stock lots.')
                    ->schema([
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Used in storefront and API URLs.'),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),
                        TextInput::make('short_description')
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->rows(6)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Visible in the storefront')
                            ->default(true),
                        Toggle::make('is_featured')
                            ->label('Feature on the home page')
                            ->default(false),
                    ])
                    ->columns(2),
                Section::make('Search engine listing')
                    ->collapsed()
                    ->schema([
                        TextInput::make('meta_title')
                            ->maxLength(255),
                        TextInput::make('meta_description')
                            ->maxLength(500),
                    ])
                    ->columns(2),
            ]);
    }
}
