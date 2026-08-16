<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestOrders extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->heading('آخر الطلبات')
            ->description('أحدث 7 طلبات وصلت إلى متجر غنم الوادي')

            ->query(
                fn (): Builder => Order::query()
                    ->latest('created_at')
                    ->limit(7)
            )

            ->columns([
                TextColumn::make('order_number')
                    ->label('رقم الطلب')
                    ->icon('heroicon-m-hashtag')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('تم نسخ رقم الطلب'),

                TextColumn::make('customer_name')
                    ->label('اسم العميل')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->limit(25),

                TextColumn::make('total')
                    ->label('إجمالي الطلب')
                    ->formatStateUsing(
                        fn ($state): string =>
                            number_format((float) $state, 2) . ' ر.س'
                    )
                    ->icon('heroicon-m-banknotes')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'paid' => 'مدفوع',
                            'pending' => 'بانتظار الدفع',
                            'failed' => 'فشل الدفع',
                            'refunded' => 'مسترجع',
                            default => 'غير محدد',
                        }
                    )
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'paid' => 'success',
                            'pending' => 'warning',
                            'failed' => 'danger',
                            'refunded' => 'info',
                            default => 'gray',
                        }
                    )
                    ->icon(
                        fn (?string $state): string => match ($state) {
                            'paid' => 'heroicon-m-check-circle',
                            'pending' => 'heroicon-m-clock',
                            'failed' => 'heroicon-m-x-circle',
                            'refunded' => 'heroicon-m-arrow-uturn-left',
                            default => 'heroicon-m-question-mark-circle',
                        }
                    ),

                TextColumn::make('status')
                    ->label('حالة الطلب')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'new' => 'طلب جديد',
                            'confirmed' => 'تم التأكيد',
                            'preparing' => 'قيد التجهيز',
                            'shipped' => 'خرج للتوصيل',
                            'delivered' => 'تم التسليم',
                            'cancelled' => 'ملغي',
                            default => 'غير محدد',
                        }
                    )
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'new' => 'info',
                            'confirmed' => 'primary',
                            'preparing' => 'warning',
                            'shipped' => 'info',
                            'delivered' => 'success',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }
                    ),

                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime('d/m/Y - h:i A')
                    ->icon('heroicon-m-calendar-days')
                    ->sortable(),
            ])

            ->recordActions([
                Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-m-eye')
                    ->color('primary')
                    ->button()
                    ->url(
                        fn (Order $record): string =>
                            OrderResource::getUrl(
                                'view',
                                ['record' => $record]
                            )
                    ),
            ])

            ->paginated(false)
            ->striped()
            ->emptyStateHeading('لا توجد طلبات حتى الآن')
            ->emptyStateDescription(
                'ستظهر أحدث الطلبات هنا بمجرد وصول أول طلب.'
            )
            ->emptyStateIcon('heroicon-o-shopping-cart');
    }
}