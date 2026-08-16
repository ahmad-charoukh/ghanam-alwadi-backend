<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BannerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('صورة البنر')
                    ->description(
                        'الصورة التي تظهر للعملاء داخل الصفحة الرئيسية.'
                    )
                    ->icon('heroicon-o-photo')
                    ->schema([
                        ImageEntry::make('image')
                            ->label('صورة البنر')
                            ->disk('public')
                            ->visibility('public')
                            ->imageHeight(320)
                            ->placeholder('لا توجد صورة')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('محتوى البنر')
                    ->description(
                        'العنوان والنصوص التي تظهر فوق صورة البنر.'
                    )
                    ->icon('heroicon-o-megaphone')
                    ->schema([
                        TextEntry::make('id')
                            ->label('رقم البنر')
                            ->formatStateUsing(
                                fn ($state): string => '#' . $state
                            )
                            ->icon('heroicon-m-identification'),

                        TextEntry::make('title')
                            ->label('عنوان البنر')
                            ->icon('heroicon-m-megaphone')
                            ->weight('bold'),

                        TextEntry::make('subtitle')
                            ->label('العنوان الفرعي')
                            ->placeholder('لا يوجد عنوان فرعي')
                            ->icon('heroicon-m-document-text'),

                        TextEntry::make('button_text')
                            ->label('نص الزر')
                            ->placeholder('لا يوجد زر')
                            ->icon('heroicon-m-cursor-arrow-rays'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('الرابط والوجهة')
                    ->description(
                        'الوجهة التي ينتقل إليها العميل عند الضغط على البنر.'
                    )
                    ->icon('heroicon-o-link')
                    ->schema([
                        TextEntry::make('link_type')
                            ->label('نوع الرابط')
                            ->formatStateUsing(
                                fn (?string $state): string => match ($state) {
                                    'none' => 'بدون رابط',
                                    'product' => 'فتح منتج',
                                    'category' => 'فتح تصنيف',
                                    'external' => 'رابط خارجي',
                                    default => 'غير محدد',
                                }
                            )
                            ->badge()
                            ->color(
                                fn (?string $state): string => match ($state) {
                                    'none' => 'gray',
                                    'product' => 'success',
                                    'category' => 'info',
                                    'external' => 'primary',
                                    default => 'gray',
                                }
                            )
                            ->icon(
                                fn (?string $state): string => match ($state) {
                                    'none' =>
                                        'heroicon-m-minus-circle',

                                    'product' =>
                                        'heroicon-m-shopping-bag',

                                    'category' =>
                                        'heroicon-m-squares-2x2',

                                    'external' =>
                                        'heroicon-m-globe-alt',

                                    default =>
                                        'heroicon-m-question-mark-circle',
                                }
                            ),

                        TextEntry::make('linked_target')
                            ->label('الوجهة المرتبطة')
                            ->state(
                                fn (Banner $record): string => match (
                                    $record->link_type
                                ) {
                                    'product' => Product::query()
                                        ->whereKey($record->link_id)
                                        ->value('name')
                                        ?? 'المنتج غير موجود',

                                    'category' => Category::query()
                                        ->whereKey($record->link_id)
                                        ->value('name')
                                        ?? 'التصنيف غير موجود',

                                    'external' =>
                                        $record->external_url
                                        ?: 'الرابط غير محدد',

                                    default => 'بدون وجهة',
                                }
                            )
                            ->badge()
                            ->color(
                                fn (Banner $record): string => match (
                                    $record->link_type
                                ) {
                                    'product' => 'success',
                                    'category' => 'info',
                                    'external' => 'primary',
                                    default => 'gray',
                                }
                            )
                            ->icon(
                                'heroicon-m-arrow-top-right-on-square'
                            ),

                        TextEntry::make('external_url')
                            ->label('الرابط الخارجي')
                            ->placeholder('لا يوجد رابط خارجي')
                            ->icon('heroicon-m-globe-alt')
                            ->visible(
                                fn (Banner $record): bool =>
                                    $record->link_type === 'external'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('حالة الظهور')
                    ->description(
                        'حالة البنر الحالية وترتيبه داخل التطبيق.'
                    )
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        TextEntry::make('display_status')
                            ->label('حالة البنر')
                            ->state(
                                fn (Banner $record): string => match (true) {
                                    ! $record->is_active =>
                                        'متوقف',

                                    $record->starts_at !== null
                                    && $record->starts_at->isFuture() =>
                                        'مجدول',

                                    $record->expires_at !== null
                                    && $record->expires_at->isPast() =>
                                        'منتهي',

                                    default =>
                                        'ظاهر الآن',
                                }
                            )
                            ->badge()
                            ->color(
                                fn (string $state): string => match ($state) {
                                    'ظاهر الآن' => 'success',
                                    'مجدول' => 'info',
                                    'منتهي' => 'danger',
                                    'متوقف' => 'gray',
                                    default => 'gray',
                                }
                            )
                            ->icon(
                                fn (string $state): string => match ($state) {
                                    'ظاهر الآن' =>
                                        'heroicon-m-eye',

                                    'مجدول' =>
                                        'heroicon-m-clock',

                                    'منتهي' =>
                                        'heroicon-m-x-circle',

                                    'متوقف' =>
                                        'heroicon-m-pause-circle',

                                    default =>
                                        'heroicon-m-question-mark-circle',
                                }
                            ),

                        TextEntry::make('activation_status')
                            ->label('حالة التفعيل')
                            ->state(
                                fn (Banner $record): string =>
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

                        TextEntry::make('sort_order')
                            ->label('ترتيب الظهور')
                            ->formatStateUsing(
                                fn ($state): string =>
                                    'الترتيب رقم '
                                    . number_format((int) $state)
                            )
                            ->badge()
                            ->color('primary')
                            ->icon('heroicon-m-bars-arrow-up'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('مدة الظهور')
                    ->description(
                        'موعد بداية ظهور البنر وموعد انتهاء عرضه.'
                    )
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        TextEntry::make('starts_at')
                            ->label('تاريخ بداية الظهور')
                            ->dateTime('d/m/Y - h:i A')
                            ->placeholder('يظهر فورًا')
                            ->icon('heroicon-m-play'),

                        TextEntry::make('expires_at')
                            ->label('تاريخ انتهاء الظهور')
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
                            ->label('تاريخ إنشاء البنر')
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