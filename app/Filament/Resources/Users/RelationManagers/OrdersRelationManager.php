<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title =
        'طلبات المستخدم';

    public function table(Table $table): Table
    {
        return $table
            ->heading('طلبات المستخدم')
            ->description(
                'جميع الطلبات المرتبطة بهذا الحساب.'
            )
            ->recordTitleAttribute(
                'order_number'
            )
            ->modifyQueryUsing(
                fn (Builder $query): Builder =>
                    $query->withCount('items')
            )
            ->columns([
                TextColumn::make('order_number')
                    ->label('رقم الطلب')
                    ->icon('heroicon-m-hashtag')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(
                        'تم نسخ رقم الطلب'
                    )
                    ->weight('bold'),

                TextColumn::make('items_count')
                    ->label('عدد المنتجات')
                    ->formatStateUsing(
                        fn (mixed $state): string =>
                            number_format(
                                (int) $state
                            ) . ' منتج'
                    )
                    ->badge()
                    ->color('gray')
                    ->icon(
                        'heroicon-m-shopping-bag'
                    ),

                TextColumn::make('total')
                    ->label('إجمالي الطلب')
                    ->formatStateUsing(
                        function (
                            mixed $state,
                            Order $record
                        ): string {
                            $currency =
                                $record->currency
                                ?: 'SAR';

                            if ($currency === 'SAR') {
                                $currency = 'ر.س';
                            }

                            return number_format(
                                (float) $state,
                                2
                            ) . ' ' . $currency;
                        }
                    )
                    ->icon(
                        'heroicon-m-banknotes'
                    )
                    ->weight('bold')
                    ->color('success')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('طريقة الدفع')
                    ->formatStateUsing(
                        fn (
                            ?string $state
                        ): string =>
                            match ($state) {
                                Order::PAYMENT_METHOD_CASH =>
                                    'الدفع عند الاستلام',

                                default =>
                                    'غير محددة',
                            }
                    )
                    ->badge()
                    ->color('gray')
                    ->icon(
                        'heroicon-m-credit-card'
                    )
                    ->toggleable(),

                TextColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->badge()
                    ->formatStateUsing(
                        fn (
                            ?string $state
                        ): string =>
                            self::paymentStatusLabel(
                                $state
                            )
                    )
                    ->color(
                        fn (
                            ?string $state
                        ): string =>
                            self::paymentStatusColor(
                                $state
                            )
                    )
                    ->icon(
                        fn (
                            ?string $state
                        ): string =>
                            self::paymentStatusIcon(
                                $state
                            )
                    )
                    ->sortable(),

                TextColumn::make('status')
                    ->label('حالة الطلب')
                    ->badge()
                    ->formatStateUsing(
                        fn (
                            ?string $state
                        ): string =>
                            self::orderStatusLabel(
                                $state
                            )
                    )
                    ->color(
                        fn (
                            ?string $state
                        ): string =>
                            self::orderStatusColor(
                                $state
                            )
                    )
                    ->icon(
                        fn (
                            ?string $state
                        ): string =>
                            self::orderStatusIcon(
                                $state
                            )
                    )
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime(
                        'd/m/Y - h:i A'
                    )
                    ->icon(
                        'heroicon-m-calendar-days'
                    )
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->since()
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('حالة الطلب')
                    ->options([
                        Order::STATUS_NEW =>
                            'طلب جديد',

                        Order::STATUS_CONFIRMED =>
                            'تم التأكيد',

                        Order::STATUS_PROCESSING =>
                            'قيد التجهيز',

                        Order::STATUS_SHIPPED =>
                            'خرج للتوصيل',

                        Order::STATUS_DELIVERED =>
                            'تم التسليم',

                        Order::STATUS_CANCELLED =>
                            'ملغي',
                    ])
                    ->native(false),

                SelectFilter::make(
                    'payment_status'
                )
                    ->label('حالة الدفع')
                    ->options([
                        Order::PAYMENT_PENDING =>
                            'بانتظار الدفع',

                        Order::PAYMENT_PAID =>
                            'مدفوع',

                        Order::PAYMENT_FAILED =>
                            'فشل الدفع',

                        Order::PAYMENT_REFUNDED =>
                            'مسترجع',
                    ])
                    ->native(false),
            ])
            ->recordActions([
                Action::make('openOrder')
                    ->label('عرض الطلب')
                    ->icon('heroicon-m-eye')
                    ->color('primary')
                    ->button()
                    ->url(
                        fn (
                            Order $record
                        ): string =>
                            OrderResource::getUrl(
                                'view',
                                [
                                    'record' =>
                                        $record,
                                ]
                            )
                    ),
            ])
            ->defaultSort(
                'created_at',
                'desc'
            )
            ->striped()
            ->paginated([
                5,
                10,
                25,
            ])
            ->emptyStateHeading(
                'لا توجد طلبات لهذا المستخدم'
            )
            ->emptyStateDescription(
                'ستظهر طلبات المستخدم هنا بعد إنشاء أول طلب.'
            )
            ->emptyStateIcon(
                'heroicon-o-shopping-cart'
            );
    }

    private static function orderStatusLabel(
        ?string $status
    ): string {
        return match ($status) {
            Order::STATUS_NEW =>
                'طلب جديد',

            Order::STATUS_CONFIRMED =>
                'تم التأكيد',

            Order::STATUS_PROCESSING =>
                'قيد التجهيز',

            Order::STATUS_SHIPPED =>
                'خرج للتوصيل',

            Order::STATUS_DELIVERED =>
                'تم التسليم',

            Order::STATUS_CANCELLED =>
                'ملغي',

            default =>
                'غير محدد',
        };
    }

    private static function orderStatusColor(
        ?string $status
    ): string {
        return match ($status) {
            Order::STATUS_NEW =>
                'info',

            Order::STATUS_CONFIRMED =>
                'primary',

            Order::STATUS_PROCESSING =>
                'warning',

            Order::STATUS_SHIPPED =>
                'info',

            Order::STATUS_DELIVERED =>
                'success',

            Order::STATUS_CANCELLED =>
                'danger',

            default =>
                'gray',
        };
    }

    private static function orderStatusIcon(
        ?string $status
    ): string {
        return match ($status) {
            Order::STATUS_NEW =>
                'heroicon-m-sparkles',

            Order::STATUS_CONFIRMED =>
                'heroicon-m-check-badge',

            Order::STATUS_PROCESSING =>
                'heroicon-m-cog-6-tooth',

            Order::STATUS_SHIPPED =>
                'heroicon-m-truck',

            Order::STATUS_DELIVERED =>
                'heroicon-m-check-circle',

            Order::STATUS_CANCELLED =>
                'heroicon-m-x-circle',

            default =>
                'heroicon-m-question-mark-circle',
        };
    }

    private static function paymentStatusLabel(
        ?string $status
    ): string {
        return match ($status) {
            Order::PAYMENT_PAID =>
                'مدفوع',

            Order::PAYMENT_PENDING =>
                'بانتظار الدفع',

            Order::PAYMENT_FAILED =>
                'فشل الدفع',

            Order::PAYMENT_REFUNDED =>
                'مسترجع',

            default =>
                'غير محدد',
        };
    }

    private static function paymentStatusColor(
        ?string $status
    ): string {
        return match ($status) {
            Order::PAYMENT_PAID =>
                'success',

            Order::PAYMENT_PENDING =>
                'warning',

            Order::PAYMENT_FAILED =>
                'danger',

            Order::PAYMENT_REFUNDED =>
                'info',

            default =>
                'gray',
        };
    }

    private static function paymentStatusIcon(
        ?string $status
    ): string {
        return match ($status) {
            Order::PAYMENT_PAID =>
                'heroicon-m-check-circle',

            Order::PAYMENT_PENDING =>
                'heroicon-m-clock',

            Order::PAYMENT_FAILED =>
                'heroicon-m-x-circle',

            Order::PAYMENT_REFUNDED =>
                'heroicon-m-arrow-uturn-left',

            default =>
                'heroicon-m-question-mark-circle',
        };
    }
}