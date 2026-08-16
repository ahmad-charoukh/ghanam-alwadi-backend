<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Models\Coupon;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CouponInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الكوبون')
                    ->description(
                        'المعلومات الأساسية وحالة صلاحية كوبون الخصم.'
                    )
                    ->icon('heroicon-o-ticket')
                    ->schema([
                        TextEntry::make('id')
                            ->label('رقم الكوبون')
                            ->formatStateUsing(
                                fn ($state): string => '#' . $state
                            )
                            ->icon('heroicon-m-identification'),

                        TextEntry::make('code')
                            ->label('كود الخصم')
                            ->badge()
                            ->color('primary')
                            ->icon('heroicon-m-ticket')
                            ->weight('bold')
                            ->copyable()
                            ->copyMessage('تم نسخ كود الخصم'),

                        TextEntry::make('validity_status')
                            ->label('حالة الكوبون')
                            ->state(
                                fn (Coupon $record): string => match (true) {
                                    ! $record->is_active =>
                                        'موقوف',

                                    $record->starts_at !== null
                                    && $record->starts_at->isFuture() =>
                                        'لم يبدأ بعد',

                                    $record->expires_at !== null
                                    && $record->expires_at->isPast() =>
                                        'منتهي',

                                    $record->usage_limit !== null
                                    && (int) $record->used_count >=
                                    (int) $record->usage_limit =>
                                        'اكتمل الاستخدام',

                                    default =>
                                        'صالح للاستخدام',
                                }
                            )
                            ->badge()
                            ->color(
                                fn (string $state): string => match ($state) {
                                    'صالح للاستخدام' => 'success',
                                    'لم يبدأ بعد' => 'info',
                                    'منتهي' => 'danger',
                                    'اكتمل الاستخدام' => 'warning',
                                    'موقوف' => 'gray',
                                    default => 'gray',
                                }
                            )
                            ->icon(
                                fn (string $state): string => match ($state) {
                                    'صالح للاستخدام' =>
                                        'heroicon-m-check-circle',

                                    'لم يبدأ بعد' =>
                                        'heroicon-m-clock',

                                    'منتهي' =>
                                        'heroicon-m-x-circle',

                                    'اكتمل الاستخدام' =>
                                        'heroicon-m-exclamation-triangle',

                                    'موقوف' =>
                                        'heroicon-m-pause-circle',

                                    default =>
                                        'heroicon-m-question-mark-circle',
                                }
                            ),

                        TextEntry::make('activation_status')
                            ->label('حالة التفعيل')
                            ->state(
                                fn (Coupon $record): string =>
                                    $record->is_active
                                        ? 'مفعّل'
                                        : 'غير مفعّل'
                            )
                            ->badge()
                            ->color(
                                fn (string $state): string =>
                                    $state === 'مفعّل'
                                        ? 'success'
                                        : 'danger'
                            )
                            ->icon(
                                fn (string $state): string =>
                                    $state === 'مفعّل'
                                        ? 'heroicon-m-check-circle'
                                        : 'heroicon-m-x-circle'
                            ),

                        TextEntry::make('description')
                            ->label('وصف الكوبون')
                            ->placeholder('لا يوجد وصف لهذا الكوبون')
                            ->icon('heroicon-m-document-text')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('تفاصيل الخصم')
                    ->description(
                        'نوع الخصم وقيمته والشروط المالية لتطبيقه.'
                    )
                    ->icon('heroicon-o-receipt-percent')
                    ->schema([
                        TextEntry::make('discount_type')
                            ->label('نوع الخصم')
                            ->formatStateUsing(
                                fn (?string $state): string => match ($state) {
                                    'percentage' => 'نسبة مئوية',
                                    'fixed' => 'مبلغ ثابت',
                                    default => 'غير محدد',
                                }
                            )
                            ->badge()
                            ->color(
                                fn (?string $state): string => match ($state) {
                                    'percentage' => 'success',
                                    'fixed' => 'info',
                                    default => 'gray',
                                }
                            )
                            ->icon(
                                fn (?string $state): string => match ($state) {
                                    'percentage' =>
                                        'heroicon-m-receipt-percent',

                                    'fixed' =>
                                        'heroicon-m-banknotes',

                                    default =>
                                        'heroicon-m-question-mark-circle',
                                }
                            ),

                        TextEntry::make('discount_value_display')
                            ->label('قيمة الخصم')
                            ->state(
                                fn (Coupon $record): string =>
                                    $record->discount_type === 'percentage'
                                        ? number_format(
                                            (float) $record->discount_value,
                                            2
                                        ) . '%'
                                        : number_format(
                                            (float) $record->discount_value,
                                            2
                                        ) . ' ر.س'
                            )
                            ->badge()
                            ->color('primary')
                            ->icon('heroicon-m-gift')
                            ->weight('bold'),

                        TextEntry::make('minimum_order_amount')
                            ->label('الحد الأدنى للطلب')
                            ->formatStateUsing(
                                fn ($state): string =>
                                    filled($state)
                                        ? number_format(
                                            (float) $state,
                                            2
                                        ) . ' ر.س'
                                        : 'بدون حد أدنى'
                            )
                            ->badge()
                            ->color(
                                fn ($state): string =>
                                    filled($state)
                                        ? 'warning'
                                        : 'gray'
                            )
                            ->icon('heroicon-m-shopping-cart'),

                        TextEntry::make('maximum_discount_display')
                            ->label('الحد الأقصى للخصم')
                            ->state(
                                function (Coupon $record): string {
                                    if (
                                        $record->discount_type !==
                                        'percentage'
                                    ) {
                                        return 'لا ينطبق على الخصم الثابت';
                                    }

                                    if (
                                        $record->maximum_discount_amount ===
                                        null
                                    ) {
                                        return 'بدون حد أقصى';
                                    }

                                    return number_format(
                                        (float) $record
                                            ->maximum_discount_amount,
                                        2
                                    ) . ' ر.س';
                                }
                            )
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-m-arrow-down-circle'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('استخدامات الكوبون')
                    ->description(
                        'عدد مرات استخدام الكوبون والحدود المسموحة.'
                    )
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        TextEntry::make('usage_progress')
                            ->label('إجمالي الاستخدام')
                            ->state(
                                fn (Coupon $record): string =>
                                    number_format(
                                        (int) $record->used_count
                                    )
                                    . ' من '
                                    . (
                                        $record->usage_limit !== null
                                            ? number_format(
                                                (int) $record->usage_limit
                                            )
                                            : 'غير محدود'
                                    )
                            )
                            ->badge()
                            ->color(
                                fn (Coupon $record): string => match (true) {
                                    $record->usage_limit === null =>
                                        'info',

                                    (int) $record->used_count >=
                                    (int) $record->usage_limit =>
                                        'danger',

                                    (int) $record->used_count >=
                                    ((int) $record->usage_limit * 0.8) =>
                                        'warning',

                                    default =>
                                        'success',
                                }
                            )
                            ->icon('heroicon-m-chart-bar'),

                        TextEntry::make('usage_limit_per_user')
                            ->label('الحد لكل عميل')
                            ->formatStateUsing(
                                fn ($state): string =>
                                    number_format((int) $state) . ' مرة'
                            )
                            ->badge()
                            ->color('primary')
                            ->icon('heroicon-m-user'),

                        TextEntry::make('remaining_uses')
                            ->label('الاستخدامات المتبقية')
                            ->state(
                                function (Coupon $record): string {
                                    if ($record->usage_limit === null) {
                                        return 'غير محدودة';
                                    }

                                    $remaining = max(
                                        0,
                                        (int) $record->usage_limit -
                                        (int) $record->used_count
                                    );

                                    return number_format($remaining)
                                        . ' استخدام';
                                }
                            )
                            ->badge()
                            ->color(
                                fn (Coupon $record): string => match (true) {
                                    $record->usage_limit === null =>
                                        'info',

                                    (int) $record->used_count >=
                                    (int) $record->usage_limit =>
                                        'danger',

                                    default =>
                                        'success',
                                }
                            )
                            ->icon('heroicon-m-arrow-path'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('مدة الصلاحية')
                    ->description(
                        'تاريخ بداية الكوبون وتاريخ انتهاء صلاحيته.'
                    )
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        TextEntry::make('starts_at')
                            ->label('تاريخ البداية')
                            ->dateTime('d/m/Y - h:i A')
                            ->placeholder('يبدأ فورًا')
                            ->icon('heroicon-m-play'),

                        TextEntry::make('expires_at')
                            ->label('تاريخ الانتهاء')
                            ->dateTime('d/m/Y - h:i A')
                            ->placeholder('بدون تاريخ انتهاء')
                            ->icon('heroicon-m-clock'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('معلومات التسجيل')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاريخ إنشاء الكوبون')
                            ->dateTime('d/m/Y - h:i A')
                            ->placeholder('-')
                            ->icon('heroicon-m-calendar-days'),

                        TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime('d/m/Y - h:i A')
                            ->placeholder('-')
                            ->icon('heroicon-m-arrow-path'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}