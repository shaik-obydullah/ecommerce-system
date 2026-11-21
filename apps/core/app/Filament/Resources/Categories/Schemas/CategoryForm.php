<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category')
                    ->description('Categories group products and drive storefront navigation.')
                    ->schema([
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
                        Select::make('parent_id')
                            ->label('Parent category')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('No parent'),
                        TextInput::make('position')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Lower numbers appear first.'),
                        Textarea::make('description')
                            ->rows(4)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Visible in the storefront')
                            ->default(true),
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
