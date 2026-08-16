<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    protected ?string $heading = 'مبيعات آخر 7 أيام';

    protected ?string $description =
        'إجمالي قيمة الطلبات المدفوعة خلال الأسبوع الأخير';

    protected string $color = 'success';

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $labels = [];
        $sales = [];

        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $date = Carbon::today()->subDays($daysAgo);

            $labels[] = $date->translatedFormat('l');

            $sales[] = (float) Order::query()
                ->whereDate('created_at', $date)
                ->where('payment_status', 'paid')
                ->sum('total');
        }

        return [
            'datasets' => [
                [
                    'label' => 'المبيعات بالريال',
                    'data' => $sales,
                    'fill' => true,
                    'tension' => 0.4,
                    'borderWidth' => 3,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 7,
                    'backgroundColor' => 'rgba(15, 43, 30, 0.12)',
                    'borderColor' => '#0F2B1E',
                    'pointBackgroundColor' => '#C9A24A',
                    'pointBorderColor' => '#C9A24A',
                ],
            ],

            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,

            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'align' => 'end',
                ],

                'tooltip' => [
                    'enabled' => true,
                ],
            ],

            'scales' => [
                'y' => [
                    'beginAtZero' => true,

                    'ticks' => [
                        'precision' => 0,
                    ],

                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.15)',
                    ],
                ],

                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}