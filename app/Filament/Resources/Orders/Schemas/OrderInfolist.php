<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الطلب')
                    ->description('البيانات الأساسية وحالة الطلب الحالية.')
                    ->schema([
                        TextEntry::make('order_number')
                            ->label('رقم الطلب')
                            ->copyable()
                            ->copyMessage('تم نسخ رقم الطلب')
                            ->color('primary'),

                        TextEntry::make('created_at')
                            ->label('تاريخ إنشاء الطلب')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('-'),

                        TextEntry::make('status')
                            ->label('حالة الطلب')
                            ->badge()
                            ->formatStateUsing(
                                fn (?string $state): string => match ($state) {
                                    'new' => 'طلب جديد',
                                    'confirmed' => 'تم تأكيد الطلب',
                                    'processing' => 'قيد التجهيز',
                                    'shipped' => 'خرج للتوصيل',
                                    'delivered' => 'تم التسليم',
                                    'cancelled' => 'طلب ملغي',
                                    default => 'غير محدد',
                                },
                            )
                            ->color(
                                fn (?string $state): string => match ($state) {
                                    'new' => 'danger',
                                    'confirmed' => 'info',
                                    'processing' => 'warning',
                                    'shipped' => 'primary',
                                    'delivered' => 'success',
                                    'cancelled' => 'gray',
                                    default => 'gray',
                                },
                            ),

                        TextEntry::make('user.name')
                            ->label('الحساب المرتبط')
                            ->placeholder('عميل زائر'),
                    ])
                    ->columns(2),

                Section::make('بيانات العميل')
                    ->description('معلومات التواصل وعنوان التوصيل.')
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('اسم العميل')
                            ->placeholder('-'),

                        TextEntry::make('customer_phone')
                            ->label('رقم الجوال')
                            ->copyable()
                            ->copyMessage('تم نسخ رقم الجوال')
                            ->placeholder('-'),

                        TextEntry::make('customer_email')
                            ->label('البريد الإلكتروني')
                            ->copyable()
                            ->copyMessage('تم نسخ البريد الإلكتروني')
                            ->placeholder('غير متوفر'),

                        TextEntry::make('city')
                            ->label('المدينة')
                            ->badge()
                            ->color('gray')
                            ->placeholder('-'),

                        TextEntry::make('address')
                            ->label('عنوان التوصيل')
                            ->placeholder('لم يتم إدخال عنوان')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('الدفع والمبالغ')
                    ->description('تفاصيل طريقة الدفع وإجمالي الطلب.')
                    ->schema([
                        TextEntry::make('payment_method')
                            ->label('طريقة الدفع')
                            ->badge()
                            ->formatStateUsing(
                                fn (?string $state): string => match ($state) {
                                    'cash' => 'الدفع عند الاستلام',
                                    'card' => 'بطاقة بنكية',
                                    'bank_transfer' => 'تحويل بنكي',
                                    'online' => 'دفع إلكتروني',
                                    default => 'غير محدد',
                                },
                            )
                            ->color('info'),

                        TextEntry::make('payment_status')
                            ->label('حالة الدفع')
                            ->badge()
                            ->formatStateUsing(
                                fn (?string $state): string => match ($state) {
                                    'pending' => 'بانتظار الدفع',
                                    'paid' => 'مدفوع',
                                    'failed' => 'فشل الدفع',
                                    'refunded' => 'تم استرجاع المبلغ',
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
                            ),

                        TextEntry::make('subtotal')
                            ->label('المجموع الفرعي')
                            ->money('SAR', locale: 'ar_SA'),

                        TextEntry::make('delivery_fee')
                            ->label('رسوم التوصيل')
                            ->money('SAR', locale: 'ar_SA'),

                        TextEntry::make('discount')
                            ->label('الخصم')
                            ->money('SAR', locale: 'ar_SA')
                            ->color('danger'),

                        TextEntry::make('total')
                            ->label('الإجمالي النهائي')
                            ->money('SAR', locale: 'ar_SA')
                            ->color('success'),
                    ])
                    ->columns(3),

                Section::make('إدارة التوصيل')
                    ->description(
                        'المندوب المسؤول ومعلومات تسليم الطلب.'
                    )
                    ->icon('heroicon-o-truck')
                    ->schema([
                        TextEntry::make('deliveryDriver.name')
                            ->label('المندوب المسؤول')
                            ->placeholder('لم يتم تعيين مندوب')
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-m-user-circle'),

                        TextEntry::make('deliveryDriver.email')
                            ->label('بريد المندوب')
                            ->placeholder('غير متوفر')
                            ->copyable()
                            ->icon('heroicon-m-envelope'),

                        TextEntry::make('assigned_at')
                            ->label('وقت تعيين المندوب')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم يتم التعيين بعد')
                            ->icon('heroicon-m-clock'),

                        TextEntry::make('delivery_notes')
                            ->label('ملاحظات المندوب')
                            ->placeholder('لا توجد ملاحظات للمندوب')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('ملاحظات الطلب')
                    ->schema([
                        TextEntry::make('customer_notes')
                            ->label('ملاحظات العميل')
                            ->placeholder('لا توجد ملاحظات من العميل')
                            ->columnSpanFull(),

                        TextEntry::make('admin_notes')
                            ->label('ملاحظات الإدارة')
                            ->placeholder('لا توجد ملاحظات داخلية')
                            ->columnSpanFull(),
                    ]),

                Section::make('مراحل تنفيذ الطلب')
                    ->description('توقيت كل مرحلة مرّ بها الطلب.')
                    ->schema([
                        TextEntry::make('confirmed_at')
                            ->label('تاريخ تأكيد الطلب')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم يتم التأكيد بعد'),

                        TextEntry::make('shipped_at')
                            ->label('تاريخ الخروج للتوصيل')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم يخرج للتوصيل بعد'),

                        TextEntry::make('delivered_at')
                            ->label('تاريخ التسليم')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('لم يتم التسليم بعد'),

                        TextEntry::make('cancelled_at')
                            ->label('تاريخ الإلغاء')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('الطلب غير ملغي'),

                        TextEntry::make('updated_at')
                            ->label('آخر تحديث على الطلب')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}