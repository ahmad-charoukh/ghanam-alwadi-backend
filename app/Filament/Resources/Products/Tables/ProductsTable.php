<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('الصورة')
                    ->disk('public')
                    ->visibility('public')
                    ->square()
                    ->imageSize(65)
                    ->defaultImageUrl(
                        'https://placehold.co/150x150?text=No+Image'
                    ),

                TextColumn::make('name')
                    ->label('اسم المنتج')
                    ->icon('heroicon-m-shopping-bag')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(35)
                    ->description(
                        fn (Product $record): string =>
                            'رقم المنتج: #' . $record->id
                    ),

                TextColumn::make('productCategory.name')
                    ->label('التصنيف')
                    ->state(
                        fn (Product $record): string =>
                            $record->productCategory?->name
                            ?? $record->category
                            ?? 'بدون تصنيف'
                    )
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->color(
                        fn (Product $record): string =>
                            $record->category_id !== null
                                ? 'success'
                                : 'gray'
                    )
                    ->icon('heroicon-m-squares-2x2'),

                TextColumn::make('price')
                    ->label('السعر')
                    ->money('SAR')
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-banknotes'),

                TextColumn::make('stock')
                    ->label('المخزون')
                    ->numeric()
                    ->sortable()
                    ->suffix(' قطعة')
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

                IconColumn::make('is_active')
                    ->label('ظاهر بالتطبيق')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                IconColumn::make('is_featured')
                    ->label('منتج مميز')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('d/m/Y - h:i A')
                    ->icon('heroicon-m-calendar-days')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('آخر تعديل')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('category_id')
                    ->label('التصنيف')
                    ->relationship(
                        'productCategory',
                        'name'
                    )
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('حالة الظهور')
                    ->placeholder('كل المنتجات')
                    ->trueLabel('المنتجات الظاهرة')
                    ->falseLabel('المنتجات المخفية'),

                TernaryFilter::make('is_featured')
                    ->label('المنتجات المميزة')
                    ->placeholder('كل المنتجات')
                    ->trueLabel('المميزة فقط')
                    ->falseLabel('غير المميزة'),
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
                    ->modalHeading('حذف المنتج')
                    ->modalDescription(
                        'هل أنت متأكد من حذف هذا المنتج؟ لا يمكن التراجع عن العملية.'
                    )
                    ->modalSubmitActionLabel('نعم، حذف المنتج'),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف المنتجات المحددة')
                        ->requiresConfirmation(),
                ]),
            ])

            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])

            ->emptyStateHeading('لا توجد منتجات')
            ->emptyStateDescription(
                'ابدأ بإضافة أول منتج إلى متجر غنم الوادي.'
            )
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }
}