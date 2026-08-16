<?php

namespace App\Filament\Resources\Settings\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label('الشعار')
                    ->disk('public')
                    ->visibility('public')
                    ->square()
                    ->imageSize(60),

                TextColumn::make('app_name')
                    ->label('اسم المتجر')
                    ->icon('heroicon-m-building-storefront')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->icon('heroicon-m-phone')
                    ->placeholder('غير محدد')
                    ->copyable()
                    ->copyMessage('تم نسخ رقم الهاتف'),

                TextColumn::make('whatsapp')
                    ->label('رقم الواتساب')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->placeholder('غير محدد')
                    ->copyable()
                    ->copyMessage('تم نسخ رقم الواتساب'),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->icon('heroicon-m-envelope')
                    ->placeholder('غير محدد')
                    ->copyable()
                    ->copyMessage('تم نسخ البريد الإلكتروني'),

                TextColumn::make('tax_percentage')
                    ->label('الضريبة')
                    ->formatStateUsing(
                        fn ($state): string =>
                            number_format((float) $state, 2) . '%'
                    )
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-m-receipt-percent'),

                TextColumn::make('shipping_cost')
                    ->label('تكلفة التوصيل')
                    ->formatStateUsing(
                        fn ($state): string =>
                            number_format((float) $state, 2) . ' ر.س'
                    )
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-truck'),

                TextColumn::make('currency')
                    ->label('العملة')
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'SAR' => 'ريال سعودي',
                            'TRY' => 'ليرة تركية',
                            'USD' => 'دولار أمريكي',
                            default => $state ?? 'غير محددة',
                        }
                    )
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-banknotes'),

                IconColumn::make('maintenance_mode')
                    ->label('وضع الصيانة')
                    ->boolean()
                    ->trueIcon('heroicon-o-wrench-screwdriver')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('d/m/Y - h:i A')
                    ->icon('heroicon-m-arrow-path')
                    ->sortable(),
            ])

            ->recordActions([
                ViewAction::make()
                    ->label('عرض')
                    ->icon('heroicon-m-eye')
                    ->color('info'),

                EditAction::make()
                    ->label('تعديل الإعدادات')
                    ->icon('heroicon-m-pencil-square')
                    ->color('primary'),
            ])

            ->paginated(false)
            ->striped()

            ->emptyStateHeading('لم يتم إنشاء إعدادات المتجر')
            ->emptyStateDescription(
                'أنشئ سجل الإعدادات الأول لتحديد بيانات متجر غنم الوادي.'
            )
            ->emptyStateIcon('heroicon-o-cog-6-tooth');
    }
}