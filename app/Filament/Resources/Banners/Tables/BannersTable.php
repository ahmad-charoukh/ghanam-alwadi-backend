<?php

namespace App\Filament\Resources\Banners\Tables;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('صورة البنر')
                    ->disk('public')
                    ->visibility('public')
                    ->imageHeight(75)
                    ->imageWidth(130),

                TextColumn::make('title')
                    ->label('عنوان البنر')
                    ->icon('heroicon-m-megaphone')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40)
                    ->description(
                        fn (Banner $record): string =>
                            $record->subtitle ?: 'لا يوجد عنوان فرعي'
                    ),

                TextColumn::make('link_type')
                    ->label('نوع الرابط')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'none' => 'بدون رابط',
                            'product' => 'منتج',
                            'category' => 'تصنيف',
                            'external' => 'رابط خارجي',
                            default => 'غير محدد',
                        }
                    )
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
                            'none' => 'heroicon-m-minus-circle',
                            'product' => 'heroicon-m-shopping-bag',
                            'category' => 'heroicon-m-squares-2x2',
                            'external' => 'heroicon-m-globe-alt',
                            default => 'heroicon-m-question-mark-circle',
                        }
                    ),

                TextColumn::make('linked_target')
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
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->limit(40)
                    ->tooltip(
                        fn (Banner $record): ?string =>
                            $record->link_type === 'external'
                                ? $record->external_url
                                : null
                    ),

                TextColumn::make('button_text')
                    ->label('نص الزر')
                    ->placeholder('بدون زر')
                    ->icon('heroicon-m-cursor-arrow-rays')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('display_status')
                    ->label('حالة الظهور')
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
                            'ظاهر الآن' => 'heroicon-m-eye',
                            'مجدول' => 'heroicon-m-clock',
                            'منتهي' => 'heroicon-m-x-circle',
                            'متوقف' => 'heroicon-m-pause-circle',
                            default => 'heroicon-m-question-mark-circle',
                        }
                    ),

                TextColumn::make('sort_order')
                    ->label('ترتيب الظهور')
                    ->numeric()
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-bars-arrow-up')
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label('بداية الظهور')
                    ->dateTime('d/m/Y - h:i A')
                    ->placeholder('يظهر فورًا')
                    ->icon('heroicon-m-play')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('expires_at')
                    ->label('نهاية الظهور')
                    ->dateTime('d/m/Y - h:i A')
                    ->placeholder('بدون انتهاء')
                    ->icon('heroicon-m-clock')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y - h:i A')
                    ->icon('heroicon-m-calendar-days')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('link_type')
                    ->label('نوع الرابط')
                    ->options([
                        'none' => 'بدون رابط',
                        'product' => 'منتج',
                        'category' => 'تصنيف',
                        'external' => 'رابط خارجي',
                    ])
                    ->placeholder('جميع أنواع الروابط'),

                TernaryFilter::make('is_active')
                    ->label('حالة التفعيل')
                    ->placeholder('جميع البنرات')
                    ->trueLabel('البنرات المفعّلة')
                    ->falseLabel('البنرات المتوقفة'),
            ])

            ->recordActions([
                ViewAction::make()
                    ->label('عرض')
                    ->icon('heroicon-m-eye')
                    ->color('info'),

                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-m-pencil-square')
                    ->color('primary'),

                DeleteAction::make()
                    ->label('حذف')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('حذف البنر الإعلاني')
                    ->modalDescription(
                        'هل أنت متأكد من حذف هذا البنر؟ لا يمكن التراجع عن العملية.'
                    )
                    ->modalSubmitActionLabel('نعم، حذف البنر'),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف البنرات المحددة')
                        ->requiresConfirmation(),
                ]),
            ])

            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->striped()
            ->paginated([10, 25, 50])

            ->emptyStateHeading('لا توجد بنرات إعلانية')
            ->emptyStateDescription(
                'ابدأ بإضافة أول بنر للصفحة الرئيسية في تطبيق غنم الوادي.'
            )
            ->emptyStateIcon('heroicon-o-photo');
    }
}