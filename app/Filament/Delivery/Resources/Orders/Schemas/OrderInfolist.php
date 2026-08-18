<?php

namespace App\Filament\Delivery\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(
        Schema $schema
    ): Schema {
        return $schema
            ->components([
                Section::make('حالة التوصيل')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        TextEntry::make('order_number')
                            ->label('رقم الطلب')
                            ->copyable()
                            ->copyMessage('تم نسخ رقم الطلب')
                            ->weight('bold')
                            ->color('primary'),

                        TextEntry::make('status')
                            ->label('الحالة الحالية')
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
                            ),

                        TextEntry::make('assigned_at')
                            ->label('وقت تحويل الطلب إليك')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label('تاريخ إنشاء الطلب')
                            ->dateTime('Y-m-d H:i'),
                    ])
                    ->columns(2),

                Section::make('بيانات العميل')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('اسم العميل')
                            ->weight('bold')
                            ->placeholder('-'),

                        TextEntry::make('customer_phone')
                            ->label('رقم الجوال — اضغط للاتصال')
                            ->copyable()
                            ->copyMessage('تم نسخ رقم الجوال')
                            ->icon('heroicon-o-phone')
                            ->color('success')
                            ->url(
                                fn (?string $state): ?string =>
                                    filled($state)
                                        ? 'tel:' . $state
                                        : null
                            )
                            ->placeholder('غير متوفر'),

                        TextEntry::make('city')
                            ->label('المدينة')
                            ->badge()
                            ->color('gray')
                            ->placeholder('-'),

                        TextEntry::make('address')
                            ->label('عنوان التوصيل — اضغط لفتح الخريطة')
                            ->copyable()
                            ->copyMessage('تم نسخ العنوان')
                            ->icon('heroicon-o-map-pin')
                            ->color('primary')
                            ->url(
                                fn (Order $record): string =>
                                    'https://www.google.com/maps/search/?api=1&query=' .
                                    urlencode(
                                        trim(
                                            $record->address .
                                            ' ' .
                                            $record->city
                                        )
                                    ),
                                shouldOpenInNewTab: true
                            )
                            ->placeholder('العنوان غير متوفر')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('الدفع والمبلغ')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        TextEntry::make('payment_method')
                            ->label('طريقة الدفع')
                            ->badge()
                            ->formatStateUsing(
                                fn (?string $state): string =>
                                    match ($state) {
                                        'cash' =>
                                            'الدفع عند الاستلام',
                                        'card' =>
                                            'بطاقة بنكية',
                                        'bank_transfer' =>
                                            'تحويل بنكي',
                                        'online' =>
                                            'دفع إلكتروني',
                                        default => 'غير محدد',
                                    }
                            )
                            ->color('info'),

                        TextEntry::make('payment_status')
                            ->label('حالة الدفع')
                            ->badge()
                            ->formatStateUsing(
                                fn (?string $state): string =>
                                    match ($state) {
                                        Order::PAYMENT_PENDING =>
                                            'بانتظار الدفع',
                                        Order::PAYMENT_PAID =>
                                            'مدفوع',
                                        Order::PAYMENT_FAILED =>
                                            'فشل الدفع',
                                        Order::PAYMENT_REFUNDED =>
                                            'تم الاسترجاع',
                                        default => 'غير محدد',
                                    }
                            )
                            ->color(
                                fn (?string $state): string =>
                                    match ($state) {
                                        Order::PAYMENT_PENDING =>
                                            'warning',
                                        Order::PAYMENT_PAID =>
                                            'success',
                                        Order::PAYMENT_FAILED =>
                                            'danger',
                                        Order::PAYMENT_REFUNDED =>
                                            'info',
                                        default => 'gray',
                                    }
                            ),

                        TextEntry::make('total')
                            ->label('المبلغ المطلوب')
                            ->money('SAR')
                            ->weight('bold')
                            ->size('lg')
                            ->color('success'),
                    ])
                    ->columns(3),

                Section::make('الملاحظات')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        TextEntry::make('customer_notes')
                            ->label('ملاحظات العميل')
                            ->placeholder(
                                'لا توجد ملاحظات من العميل'
                            )
                            ->columnSpanFull(),

                        TextEntry::make('delivery_notes')
                            ->label('ملاحظات الإدارة للمندوب')
                            ->placeholder(
                                'لا توجد ملاحظات خاصة بالتوصيل'
                            )
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}