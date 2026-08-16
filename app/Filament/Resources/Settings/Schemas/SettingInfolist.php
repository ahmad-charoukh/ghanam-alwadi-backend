<?php

namespace App\Filament\Resources\Settings\Schemas;

use App\Models\Setting;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('هوية المتجر')
                    ->description(
                        'اسم وشعار ووصف متجر غنم الوادي.'
                    )
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        ImageEntry::make('logo')
                            ->label('شعار المتجر')
                            ->disk('public')
                            ->visibility('public')
                            ->imageHeight(220)
                            ->square()
                            ->placeholder('لا يوجد شعار'),

                        TextEntry::make('app_name')
                            ->label('اسم المتجر')
                            ->icon('heroicon-m-building-storefront')
                            ->weight('bold')
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('about')
                            ->label('نبذة عن المتجر')
                            ->placeholder('لم تتم إضافة نبذة عن المتجر')
                            ->icon('heroicon-m-document-text')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('معلومات التواصل')
                    ->description(
                        'بيانات الاتصال والعنوان التي تظهر للعملاء.'
                    )
                    ->icon('heroicon-o-phone')
                    ->schema([
                        TextEntry::make('phone')
                            ->label('رقم الهاتف')
                            ->placeholder('غير محدد')
                            ->icon('heroicon-m-phone')
                            ->copyable()
                            ->copyMessage('تم نسخ رقم الهاتف'),

                        TextEntry::make('whatsapp')
                            ->label('رقم الواتساب')
                            ->placeholder('غير محدد')
                            ->icon('heroicon-m-chat-bubble-left-right')
                            ->copyable()
                            ->copyMessage('تم نسخ رقم الواتساب'),

                        TextEntry::make('email')
                            ->label('البريد الإلكتروني')
                            ->placeholder('غير محدد')
                            ->icon('heroicon-m-envelope')
                            ->copyable()
                            ->copyMessage('تم نسخ البريد الإلكتروني'),

                        TextEntry::make('address')
                            ->label('عنوان المتجر')
                            ->placeholder('لم تتم إضافة عنوان')
                            ->icon('heroicon-m-map-pin')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('روابط التواصل الاجتماعي')
                    ->description(
                        'الحسابات الرسمية الخاصة بالمتجر.'
                    )
                    ->icon('heroicon-o-share')
                    ->schema([
                        TextEntry::make('facebook_url')
                            ->label('فيسبوك')
                            ->placeholder('غير محدد')
                            ->icon('heroicon-m-link')
                            ->copyable()
                            ->copyMessage('تم نسخ رابط فيسبوك'),

                        TextEntry::make('instagram_url')
                            ->label('إنستغرام')
                            ->placeholder('غير محدد')
                            ->icon('heroicon-m-camera')
                            ->copyable()
                            ->copyMessage('تم نسخ رابط إنستغرام'),

                        TextEntry::make('tiktok_url')
                            ->label('تيك توك')
                            ->placeholder('غير محدد')
                            ->icon('heroicon-m-video-camera')
                            ->copyable()
                            ->copyMessage('تم نسخ رابط تيك توك'),

                        TextEntry::make('telegram_url')
                            ->label('تيليجرام')
                            ->placeholder('غير محدد')
                            ->icon('heroicon-m-paper-airplane')
                            ->copyable()
                            ->copyMessage('تم نسخ رابط تيليجرام'),

                        TextEntry::make('x_url')
                            ->label('منصة X')
                            ->placeholder('غير محدد')
                            ->icon('heroicon-m-at-symbol')
                            ->copyable()
                            ->copyMessage('تم نسخ رابط منصة X'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('الضريبة والشحن')
                    ->description(
                        'الإعدادات المالية المستخدمة في حساب الطلبات.'
                    )
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        TextEntry::make('tax_percentage')
                            ->label('نسبة الضريبة')
                            ->formatStateUsing(
                                fn ($state): string =>
                                    number_format(
                                        (float) $state,
                                        2
                                    ) . '%'
                            )
                            ->badge()
                            ->color('warning')
                            ->icon('heroicon-m-receipt-percent'),

                        TextEntry::make('shipping_cost')
                            ->label('تكلفة التوصيل')
                            ->formatStateUsing(
                                fn (
                                    $state,
                                    Setting $record
                                ): string =>
                                    self::formatMoney(
                                        $state,
                                        $record->currency
                                    )
                            )
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-m-truck'),

                        TextEntry::make('free_shipping_amount')
                            ->label('التوصيل المجاني عند')
                            ->formatStateUsing(
                                fn (
                                    $state,
                                    Setting $record
                                ): string =>
                                    filled($state)
                                        ? self::formatMoney(
                                            $state,
                                            $record->currency
                                        )
                                        : 'غير مفعّل'
                            )
                            ->badge()
                            ->color(
                                fn ($state): string =>
                                    filled($state)
                                        ? 'success'
                                        : 'gray'
                            )
                            ->icon('heroicon-m-gift'),

                        TextEntry::make('currency')
                            ->label('عملة المتجر')
                            ->formatStateUsing(
                                fn (?string $state): string =>
                                    match ($state) {
                                        'SAR' => 'ريال سعودي (SAR)',
                                        'TRY' => 'ليرة تركية (TRY)',
                                        'USD' => 'دولار أمريكي (USD)',
                                        default => $state ?? 'غير محددة',
                                    }
                            )
                            ->badge()
                            ->color('primary')
                            ->icon('heroicon-m-banknotes'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('حالة النظام')
                    ->description(
                        'حالة إتاحة التطبيق والمتجر للعملاء.'
                    )
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->schema([
                        TextEntry::make('maintenance_status')
                            ->label('وضع الصيانة')
                            ->state(
                                fn (Setting $record): string =>
                                    $record->maintenance_mode
                                        ? 'وضع الصيانة مفعّل'
                                        : 'المتجر يعمل بشكل طبيعي'
                            )
                            ->badge()
                            ->color(
                                fn (string $state): string =>
                                    $state === 'وضع الصيانة مفعّل'
                                        ? 'danger'
                                        : 'success'
                            )
                            ->icon(
                                fn (string $state): string =>
                                    $state === 'وضع الصيانة مفعّل'
                                        ? 'heroicon-m-wrench-screwdriver'
                                        : 'heroicon-m-check-circle'
                            ),
                    ])
                    ->columnSpanFull(),

                Section::make('معلومات التسجيل')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاريخ إنشاء الإعدادات')
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

    private static function formatMoney(
        mixed $value,
        ?string $currency
    ): string {
        $currencyLabel = match ($currency) {
            'TRY' => 'ل.ت',
            'USD' => '$',
            default => 'ر.س',
        };

        return number_format(
            (float) $value,
            2
        ) . ' ' . $currencyLabel;
    }
}