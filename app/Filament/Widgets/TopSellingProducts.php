<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\OrderItem;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopSellingProducts extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->heading('أكثر المنتجات مبيعًا')
            ->description('المنتجات الأعلى مبيعًا في متجر غنم الوادي')

            ->query(
                fn (): Builder => Product::query()
                    ->whereHas('orderItems')
                    ->withSum(
                        'orderItems as sold_quantity',
                        'quantity'
                    )
                    ->addSelect([
                        'sales_total' => OrderItem::query()
                            ->selectRaw(
                                'COALESCE(SUM(quantity * price), 0)'
                            )
                            ->whereColumn(
                                'product_id',
                                'products.id'
                            ),
                    ])
                    ->orderByDesc('sold_quantity')
                    ->limit(7)
            )

            ->columns([
                ImageColumn::make('image')
                    ->label('الصورة')
                    ->disk('public')
                    ->visibility('public')
                    ->imageHeight(55)
                    ->square()
                    ->alt(
                        fn (Product $record): string =>
                            $record->name
                    ),

                TextColumn::make('name')
                    ->label('اسم المنتج')
                    ->icon('heroicon-m-shopping-bag')
                    ->searchable()
                    ->limit(35)
                    ->weight('bold'),

                TextColumn::make('sold_quantity')
                    ->label('الكمية المباعة')
                    ->formatStateUsing(
                        fn ($state): string =>
                            number_format((int) $state) . ' قطعة'
                    )
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-arrow-trending-up'),

                TextColumn::make('sales_total')
                    ->label('إجمالي المبيعات')
                    ->formatStateUsing(
                        fn ($state): string =>
                            number_format((float) $state, 2) . ' ر.س'
                    )
                    ->icon('heroicon-m-banknotes'),

                TextColumn::make('price')
                    ->label('سعر المنتج')
                    ->formatStateUsing(
                        fn ($state): string =>
                            number_format((float) $state, 2) . ' ر.س'
                    )
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('المخزون')
                    ->formatStateUsing(
                        fn ($state): string =>
                            number_format((int) $state) . ' متوفر'
                    )
                    ->badge()
                    ->color(
                        fn ($state): string => match (true) {
                            (int) $state <= 0 => 'danger',
                            (int) $state <= 5 => 'warning',
                            default => 'success',
                        }
                    )
                    ->icon(
                        fn ($state): string => match (true) {
                            (int) $state <= 0 =>
                                'heroicon-m-x-circle',

                            (int) $state <= 5 =>
                                'heroicon-m-exclamation-triangle',

                            default =>
                                'heroicon-m-check-circle',
                        }
                    )
                    ->sortable(),
            ])

            ->recordActions([
                Action::make('openProduct')
                    ->label('فتح المنتج')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('primary')
                    ->button()
                    ->url(
                        fn (Product $record): string =>
                            ProductResource::getUrl(
                                'edit',
                                ['record' => $record]
                            )
                    ),
            ])

            ->paginated(false)
            ->striped()

            ->emptyStateHeading('لا توجد مبيعات حتى الآن')
            ->emptyStateDescription(
                'ستظهر المنتجات الأكثر مبيعًا هنا بعد تسجيل الطلبات.'
            )
            ->emptyStateIcon('heroicon-o-chart-bar');
    }
}