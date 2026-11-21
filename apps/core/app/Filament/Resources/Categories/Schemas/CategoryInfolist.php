<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Schemas;

use App\Core\Catalog\Models\Category;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('slug'),
                        TextEntry::make('parent.name')
                            ->label('Parent')
                            ->placeholder('None'),
                        TextEntry::make('position')
                            ->numeric(),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                        TextEntry::make('products_count')
                            ->label('Products')
                            ->state(fn (Category $record): int => $record->products()->count()),
                        TextEntry::make('description')
                            ->placeholder('No description')
                            ->columnSpanFull(),
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
