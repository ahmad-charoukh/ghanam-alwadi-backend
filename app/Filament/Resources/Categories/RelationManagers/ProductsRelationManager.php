<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'منتجات التصنيف';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')

            ->columns([
                ImageColumn::make('image')
                    ->label('الصورة')
                    ->disk('public')
                    ->visibility('public')
                    ->square()
                    ->imageSize(60),

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

                TextColumn::make('price')
                    ->label('السعر')
                    ->money('SAR')
                    ->icon('heroicon-m-banknotes')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('المخزون')
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
                    )
                    ->sortable(),

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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
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
                Action::make('openProduct')
                    ->label('عرض المنتج')
                    ->icon('heroicon-m-eye')
                    ->color('primary')
                    ->button()
                    ->url(
                        fn (Product $record): string =>
                            ProductResource::getUrl(
                                'view',
                                ['record' => $record]
                            )
                    ),
            ])

            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([5, 10, 25])

            ->emptyStateHeading('لا توجد منتجات في هذا التصنيف')
            ->emptyStateDescription(
                'ستظهر هنا المنتجات التي تم ربطها بهذا التصنيف.'
            )
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }
}