<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Core\Catalog\Models\Product;
use App\Core\Inventory\Models\Stock;
use App\Core\Support\Money\Currency;
use App\Core\Support\Money\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class StockValueOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $heading = 'Inventory at a glance';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $stockValue = (int) Stock::query()
            ->selectRaw('coalesce(sum(qty_on_hand * buy_price_amount), 0) as aggregate')
            ->value('aggregate');

        $unitsOnHand = (int) Stock::query()->sum('qty_on_hand');
        $unitsAvailable = (int) Stock::query()
            ->selectRaw('coalesce(sum(qty_on_hand - qty_reserved), 0) as aggregate')
            ->value('aggregate');

        $inStockProducts = Product::query()
            ->active()
            ->whereHas('stocks', fn ($query) => $query->inStock())
            ->count();

        $outOfStockProducts = Product::query()
            ->active()
            ->whereDoesntHave('stocks', fn ($query) => $query->inStock())
            ->count();

        return [
            Stat::make('Stock value', Money::of($stockValue, Currency::base())->format())
                ->description('On-hand units at recorded buy price')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Units on hand', number_format($unitsOnHand))
                ->description($unitsAvailable.' available after reservations')
                ->descriptionIcon('heroicon-m-cube'),
            Stat::make('Products in stock', number_format($inStockProducts))
                ->description('Active products with at least one available lot')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Products out of stock', number_format($outOfStockProducts))
                ->description('Active products with no available lots')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($outOfStockProducts > 0 ? 'warning' : 'gray'),
        ];
    }
}
