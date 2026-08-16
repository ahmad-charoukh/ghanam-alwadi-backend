<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected ?string $heading = 'نظرة عامة';

    protected ?string $description =
        'ملخص سريع لحالة المنتجات والمخزون في غنم الوادي';

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalProducts = Product::query()->count();

        $activeProducts = Product::query()
            ->where('is_active', true)
            ->count();

        $featuredProducts = Product::query()
            ->where('is_featured', true)
            ->count();

        $lowStockProducts = Product::query()
            ->where('stock', '<=', 5)
            ->count();

        return [
            Stat::make('إجمالي المنتجات', $totalProducts)
                ->description('جميع المنتجات المسجلة')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary')
                ->chart([2, 4, 3, 5, 6, 7, $totalProducts]),

            Stat::make('المنتجات الظاهرة', $activeProducts)
                ->description('متاحة للعملاء في التطبيق')
                ->descriptionIcon('heroicon-m-eye')
                ->color('success')
                ->chart([1, 2, 2, 3, 4, 5, $activeProducts]),

            Stat::make('المنتجات المميزة', $featuredProducts)
                ->description('تظهر في قسم المنتجات المميزة')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning')
                ->chart([0, 1, 1, 2, 2, 3, $featuredProducts]),

            Stat::make('مخزون منخفض', $lowStockProducts)
                ->description(
                    $lowStockProducts > 0
                        ? 'تحتاج هذه المنتجات إلى إعادة تعبئة'
                        : 'المخزون بحالة جيدة',
                )
                ->descriptionIcon(
                    $lowStockProducts > 0
                        ? 'heroicon-m-exclamation-triangle'
                        : 'heroicon-m-check-circle',
                )
                ->color(
                    $lowStockProducts > 0
                        ? 'danger'
                        : 'success',
                )
                ->chart([5, 4, 4, 3, 2, 2, $lowStockProducts]),
        ];
    }
}