<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('صورة المنتج')
                    ->description('الصورة المعروضة للعملاء داخل التطبيق')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        ImageEntry::make('image')
                            ->label('صورة المنتج')
                            ->disk('public')
                            ->visibility('public')
                            ->imageHeight(250)
                            ->square()
                            ->placeholder('لا توجد صورة')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('بيانات المنتج')
                    ->description('المعلومات الأساسية الخاصة بالمنتج')
                    ->icon('heroicon-o-shopping-bag')
                    ->schema([
                        TextEntry::make('id')
                            ->label('رقم المنتج')
                            ->formatStateUsing(
                                fn ($state): string => '#' . $state
                            )
                            ->icon('heroicon-m-identification'),

                        TextEntry::make('name')
                            ->label('اسم المنتج')
                            ->icon('heroicon-m-shopping-bag'),

                        TextEntry::make('category_name')
                            ->label('التصنيف')
                            ->state(
                                fn (Product $record): string =>
                                    $record->productCategory?->name
                                    ?? $record->category
                                    ?? 'بدون تصنيف'
                            )
                            ->badge()
                            ->color('success')
                            ->icon('heroicon-m-squares-2x2'),

                        TextEntry::make('price')
                            ->label('سعر المنتج')
                            ->money('SAR')
                            ->badge()
                            ->color('primary')
                            ->icon('heroicon-m-banknotes'),

                        TextEntry::make('stock')
                            ->label('الكمية المتوفرة')
                            ->formatStateUsing(
                                fn ($state): string =>
                                    number_format((int) $state) . ' قطعة'
                            )
                            ->badge()
                            ->color(
                                fn ($state): string => match (true) {
                                    (int) $state <= 0 => 'danger',
                                    (int) $state <= 5 => 'warning',
                                    default => 'success',
                                }
                            )
                            ->icon(
                                fn ($state): string => match (true) {
                                    (int) $state <= 0 =>
                                        'heroicon-m-x-circle',

                                    (int) $state <= 5 =>
                                        'heroicon-m-exclamation-triangle',

                                    default =>
                                        'heroicon-m-check-circle',
                                }
                            ),

                        TextEntry::make('description')
                            ->label('وصف المنتج')
                            ->placeholder('لا يوجد وصف لهذا المنتج')
                            ->icon('heroicon-m-document-text')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('حالة المنتج')
                    ->description('حالة ظهور المنتج وتمييزه داخل التطبيق')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        TextEntry::make('active_status')
                            ->label('الظهور في التطبيق')
                            ->state(
                                fn (Product $record): string =>
                                    $record->is_active
                                        ? 'ظاهر'
                                        : 'مخفي'
                            )
                            ->badge()
                            ->color(
                                fn (string $state): string =>
                                    $state === 'ظاهر'
                                        ? 'success'
                                        : 'danger'
                            )
                            ->icon(
                                fn (string $state): string =>
                                    $state === 'ظاهر'
                                        ? 'heroicon-m-check-circle'
                                        : 'heroicon-m-x-circle'
                            ),

                        TextEntry::make('featured_status')
                            ->label('المنتج المميز')
                            ->state(
                                fn (Product $record): string =>
                                    $record->is_featured
                                        ? 'منتج مميز'
                                        : 'منتج عادي'
                            )
                            ->badge()
                            ->color(
                                fn (string $state): string =>
                                    $state === 'منتج مميز'
                                        ? 'warning'
                                        : 'gray'
                            )
                            ->icon(
                                fn (string $state): string =>
                                    $state === 'منتج مميز'
                                        ? 'heroicon-m-star'
                                        : 'heroicon-m-minus-circle'
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('معلومات التسجيل')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاريخ إضافة المنتج')
                            ->dateTime('d/m/Y - h:i A')
                            ->placeholder('-')
                            ->icon('heroicon-m-calendar-days'),

                        TextEntry::make('updated_at')
                            ->label('آخر تعديل')
                            ->dateTime('d/m/Y - h:i A')
                            ->placeholder('-')
                            ->icon('heroicon-m-arrow-path'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}