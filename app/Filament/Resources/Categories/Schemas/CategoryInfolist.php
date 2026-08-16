<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('صورة التصنيف')
                    ->description('الصورة التي تظهر للعملاء داخل التطبيق')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        ImageEntry::make('image')
                            ->label('صورة التصنيف')
                            ->disk('public')
                            ->visibility('public')
                            ->imageHeight(220)
                            ->square()
                            ->placeholder('لا توجد صورة')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('بيانات التصنيف')
                    ->description('المعلومات الأساسية الخاصة بالتصنيف')
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        TextEntry::make('id')
                            ->label('رقم التصنيف')
                            ->formatStateUsing(
                                fn ($state): string => '#' . $state
                            )
                            ->icon('heroicon-m-identification'),

                        TextEntry::make('name')
                            ->label('اسم التصنيف')
                            ->icon('heroicon-m-tag')
                            ->weight('bold'),

                        TextEntry::make('slug')
                            ->label('الرابط المختصر')
                            ->icon('heroicon-m-link')
                            ->copyable()
                            ->copyMessage('تم نسخ الرابط المختصر'),

                        TextEntry::make('description')
                            ->label('وصف التصنيف')
                            ->placeholder('لا يوجد وصف لهذا التصنيف')
                            ->icon('heroicon-m-document-text')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('إحصائيات وإعدادات التصنيف')
                    ->description('حالة التصنيف وترتيبه وعدد المنتجات التابعة له')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        TextEntry::make('activation_status')
                            ->label('حالة التصنيف')
                            ->state(
                                fn (Category $record): string =>
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
                                    'الترتيب رقم ' . number_format((int) $state)
                            )
                            ->badge()
                            ->color('primary')
                            ->icon('heroicon-m-bars-arrow-up'),

                        TextEntry::make('products_count')
                            ->label('عدد المنتجات')
                            ->state(
                                fn (Category $record): int =>
                                    $record->products()->count()
                            )
                            ->formatStateUsing(
                                fn ($state): string =>
                                    number_format((int) $state) . ' منتج'
                            )
                            ->badge()
                            ->color(
                                fn ($state): string =>
                                    (int) $state > 0
                                        ? 'info'
                                        : 'gray'
                            )
                            ->icon('heroicon-m-shopping-bag'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('معلومات التسجيل')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
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