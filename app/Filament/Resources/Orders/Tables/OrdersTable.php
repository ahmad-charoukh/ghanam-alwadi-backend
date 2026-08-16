<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('تم نسخ رقم الطلب')
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('customer_name')
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('customer_phone')
                    ->label('رقم الجوال')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('تم نسخ رقم الجوال'),

                TextColumn::make('city')
                    ->label('المدينة')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('total')
                    ->label('إجمالي الطلب')
                    ->money('SAR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

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
                        },
                    )
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'new' => 'danger',
                            'confirmed' => 'info',
                            'preparing' => 'warning',
                            'shipped' => 'primary',
                            'delivered' => 'success',
                            'cancelled' => 'gray',
                            default => 'gray',
                        },
                    )
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('طريقة الدفع')
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'cash' => 'الدفع عند الاستلام',
                            'card' => 'بطاقة بنكية',
                            'bank_transfer' => 'تحويل بنكي',
                            'online' => 'دفع إلكتروني',
                            default => 'غير محدد',
                        },
                    )
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'pending' => 'بانتظار الدفع',
                            'paid' => 'مدفوع',
                            'failed' => 'فشل الدفع',
                            'refunded' => 'تم الاسترجاع',
                            default => 'غير محدد',
                        },
                    )
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'pending' => 'warning',
                            'paid' => 'success',
                            'failed' => 'danger',
                            'refunded' => 'info',
                            default => 'gray',
                        },
                    )
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('customer_email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->placeholder('غير متوفر')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('subtotal')
                    ->label('المجموع الفرعي')
                    ->money('SAR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('delivery_fee')
                    ->label('رسوم التوصيل')
                    ->money('SAR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('discount')
                    ->label('الخصم')
                    ->money('SAR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('status')
                    ->label('حالة الطلب')
                    ->options([
                        'new' => 'طلب جديد',
                        'confirmed' => 'تم التأكيد',
                        'preparing' => 'قيد التجهيز',
                        'shipped' => 'خرج للتوصيل',
                        'delivered' => 'تم التسليم',
                        'cancelled' => 'ملغي',
                    ])
                    ->multiple(),

                SelectFilter::make('payment_status')
                    ->label('حالة الدفع')
                    ->options([
                        'pending' => 'بانتظار الدفع',
                        'paid' => 'مدفوع',
                        'failed' => 'فشل الدفع',
                        'refunded' => 'تم الاسترجاع',
                    ]),

                SelectFilter::make('payment_method')
                    ->label('طريقة الدفع')
                    ->options([
                        'cash' => 'الدفع عند الاستلام',
                        'card' => 'بطاقة بنكية',
                        'bank_transfer' => 'تحويل بنكي',
                        'online' => 'دفع إلكتروني',
                    ]),

                SelectFilter::make('city')
                    ->label('المدينة')
                    ->options(
                        fn (): array => \App\Models\Order::query()
                            ->whereNotNull('city')
                            ->distinct()
                            ->orderBy('city')
                            ->pluck('city', 'city')
                            ->all(),
                    )
                    ->searchable()
                    ->preload(),
            ])

            ->recordActions([
                ViewAction::make()
                    ->label('عرض'),

                EditAction::make()
                    ->label('تعديل'),

                DeleteAction::make()
                    ->label('حذف')
                    ->requiresConfirmation(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف الطلبات المحددة'),
                ]),
            ])

            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}