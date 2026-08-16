<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends StatsOverviewWidget
{
    protected ?string $heading = 'إحصائيات المتجر';

    protected ?string $description =
        'ملخص مباشر لأهم بيانات غنم الوادي';

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $newOrders = Order::query()
            ->where('status', 'new')
            ->count();

        $todaySales = Order::query()
            ->whereDate('created_at', today())
            ->where('payment_status', 'paid')
            ->sum('total');

        $totalSales = Order::query()
            ->where('payment_status', 'paid')
            ->sum('total');

        $customersCount = User::query()->count();

        $productsCount = Product::query()->count();

        $lowStockCount = Product::query()
            ->where('stock', '<=', 5)
            ->count();

        $cancelledOrders = Order::query()
            ->where('status', 'cancelled')
            ->count();

        $deliveredOrders = Order::query()
            ->where('status', 'delivered')
            ->count();

        return [
            Stat::make('الطلبات الجديدة', $newOrders)
                ->description(
                    $newOrders > 0
                        ? 'تحتاج إلى مراجعة الآن'
                        : 'لا توجد طلبات جديدة',
                )
                ->descriptionIcon(
                    $newOrders > 0
                        ? 'heroicon-m-bell-alert'
                        : 'heroicon-m-check-circle',
                )
                ->color(
                    $newOrders > 0
                        ? 'danger'
                        : 'success',
                )
                ->chart([
                    2,
                    3,
                    2,
                    5,
                    4,
                    6,
                    $newOrders,
                ]),

            Stat::make(
                'مبيعات اليوم',
                number_format((float) $todaySales, 2) . ' ر.س',
            )
                ->description('الطلبات المدفوعة اليوم')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([
                    100,
                    250,
                    180,
                    400,
                    300,
                    520,
                    (float) $todaySales,
                ]),

            Stat::make(
                'إجمالي المبيعات',
                number_format((float) $totalSales, 2) . ' ر.س',
            )
                ->description('إجمالي الطلبات المدفوعة')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color('primary')
                ->chart([
                    500,
                    900,
                    1200,
                    1700,
                    2200,
                    2800,
                    (float) $totalSales,
                ]),

            Stat::make('العملاء', $customersCount)
                ->description('إجمالي الحسابات المسجلة')
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->chart([
                    1,
                    2,
                    3,
                    4,
                    5,
                    7,
                    $customersCount,
                ]),

            Stat::make('المنتجات', $productsCount)
                ->description('جميع المنتجات المسجلة')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning')
                ->chart([
                    1,
                    3,
                    4,
                    5,
                    6,
                    8,
                    $productsCount,
                ]),

            Stat::make('مخزون منخفض', $lowStockCount)
                ->description(
                    $lowStockCount > 0
                        ? 'منتجات تحتاج إعادة تعبئة'
                        : 'المخزون بحالة جيدة',
                )
                ->descriptionIcon(
                    $lowStockCount > 0
                        ? 'heroicon-m-exclamation-triangle'
                        : 'heroicon-m-check-badge',
                )
                ->color(
                    $lowStockCount > 0
                        ? 'danger'
                        : 'success',
                )
                ->chart([
                    7,
                    6,
                    5,
                    4,
                    3,
                    2,
                    $lowStockCount,
                ]),

            Stat::make('طلبات مكتملة', $deliveredOrders)
                ->description('تم تسليمها للعملاء')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('طلبات ملغاة', $cancelledOrders)
                ->description('إجمالي الطلبات الملغاة')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color(
                    $cancelledOrders > 0
                        ? 'danger'
                        : 'gray',
                ),
        ];
    }
}