<?php

namespace App\Filament\Delivery\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(
        Table $table
    ): Table {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('customer_name')
                    ->label('العميل')
                    ->searchable()
                    ->weight('bold')
                    ->description(
                        fn (Order $record): string =>
                            $record->customer_phone
                                ?: 'لا يوجد رقم جوال'
                    ),

                TextColumn::make('city')
                    ->label('المدينة')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('address')
                    ->label('عنوان التوصيل')
                    ->wrap()
                    ->limit(45)
                    ->searchable(),

                TextColumn::make('total')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->weight('bold')
                    ->color('success')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('حالة التوصيل')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            match ($state) {
                                Order::STATUS_NEW =>
                                    'طلب جديد',
                                Order::STATUS_CONFIRMED =>
                                    'تم التأكيد',
                                Order::STATUS_PROCESSING =>
                                    'استلمه المندوب',
                                Order::STATUS_SHIPPED =>
                                    'بالطريق للعميل',
                                Order::STATUS_DELIVERED =>
                                    'تم التسليم',
                                Order::STATUS_CANCELLED =>
                                    'ملغي',
                                default => 'غير محدد',
                            }
                    )
                    ->color(
                        fn (?string $state): string =>
                            match ($state) {
                                Order::STATUS_NEW =>
                                    'danger',
                                Order::STATUS_CONFIRMED =>
                                    'info',
                                Order::STATUS_PROCESSING =>
                                    'warning',
                                Order::STATUS_SHIPPED =>
                                    'primary',
                                Order::STATUS_DELIVERED =>
                                    'success',
                                Order::STATUS_CANCELLED =>
                                    'gray',
                                default => 'gray',
                            }
                    )
                    ->sortable(),

                TextColumn::make('assigned_at')
                    ->label('وقت التعيين')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('-')
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
                            'استلمه المندوب',
                        Order::STATUS_SHIPPED =>
                            'بالطريق للعميل',
                        Order::STATUS_DELIVERED =>
                            'تم التسليم',
                        Order::STATUS_CANCELLED =>
                            'ملغي',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('التفاصيل')
                    ->icon('heroicon-o-eye'),

                Action::make('receive_order')
                    ->label('استلمت الطلب')
                    ->icon('heroicon-o-hand-raised')
                    ->color('warning')
                    ->button()
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد استلام الطلب')
                    ->modalDescription(
                        'هل استلمت الطلب وأصبحت مسؤولًا عن توصيله؟'
                    )
                    ->visible(
                        fn (Order $record): bool =>
                            in_array(
                                $record->status,
                                [
                                    Order::STATUS_NEW,
                                    Order::STATUS_CONFIRMED,
                                ],
                                true
                            )
                    )
                    ->action(
                        function (Order $record): void {
                            $record->update([
                                'status' =>
                                    Order::STATUS_PROCESSING,
                            ]);

                            Notification::make()
                                ->title('تم تسجيل استلام الطلب')
                                ->success()
                                ->send();
                        }
                    ),

                Action::make('start_delivery')
                    ->label('بالطريق للعميل')
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->button()
                    ->requiresConfirmation()
                    ->modalHeading('بدء التوصيل')
                    ->modalDescription(
                        'سيتم إبلاغ العميل أن الطلب أصبح في الطريق.'
                    )
                    ->visible(
                        fn (Order $record): bool =>
                            $record->status ===
                                Order::STATUS_PROCESSING
                    )
                    ->action(
                        function (Order $record): void {
                            $record->update([
                                'status' =>
                                    Order::STATUS_SHIPPED,
                            ]);

                            Notification::make()
                                ->title('الطلب بالطريق للعميل')
                                ->success()
                                ->send();
                        }
                    ),

                Action::make('deliver_order')
                    ->label('تم التسليم')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->button()
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد تسليم الطلب')
                    ->modalDescription(
                        'تأكد من تسليم الطلب للعميل واستلام المبلغ إن كان الدفع نقديًا.'
                    )
                    ->visible(
                        fn (Order $record): bool =>
                            $record->status ===
                                Order::STATUS_SHIPPED
                    )
                    ->action(
                        function (Order $record): void {
                            $changes = [
                                'status' =>
                                    Order::STATUS_DELIVERED,
                            ];

                            if (
                                $record->payment_method ===
                                    Order::PAYMENT_METHOD_CASH
                            ) {
                                $changes['payment_status'] =
                                    Order::PAYMENT_PAID;
                            }

                            $record->update($changes);

                            Notification::make()
                                ->title('تم تسليم الطلب بنجاح')
                                ->success()
                                ->send();
                        }
                    ),
            ])
            ->defaultSort('assigned_at', 'desc')
            ->poll('20s')
            ->emptyStateHeading(
                'لا توجد طلبات معيّنة لك حاليًا'
            )
            ->emptyStateDescription(
                'ستظهر هنا الطلبات بعد تحويلها إليك من الإدارة.'
            )
            ->emptyStateIcon('heroicon-o-truck')
            ->striped();
    }
}