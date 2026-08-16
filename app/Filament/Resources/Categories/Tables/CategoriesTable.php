<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder =>
                    $query->withCount('products')
            )

            ->columns([
                ImageColumn::make('image')
                    ->label('الصورة')
                    ->disk('public')
                    ->visibility('public')
                    ->imageHeight(55)
                    ->square(),

                TextColumn::make('name')
                    ->label('اسم التصنيف')
                    ->icon('heroicon-m-tag')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(
                        fn (Category $record): string =>
                            'رقم التصنيف: #' . $record->id
                    ),

                TextColumn::make('slug')
                    ->label('الرابط المختصر')
                    ->icon('heroicon-m-link')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('تم نسخ الرابط المختصر')
                    ->limit(35),

                TextColumn::make('products_count')
                    ->label('عدد المنتجات')
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
                    ->icon('heroicon-m-shopping-bag')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('حالة التصنيف')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('ترتيب الظهور')
                    ->numeric()
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-bars-arrow-up')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y - h:i A')
                    ->icon('heroicon-m-calendar-days')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                TernaryFilter::make('is_active')
                    ->label('حالة التصنيف')
                    ->placeholder('جميع التصنيفات')
                    ->trueLabel('التصنيفات المفعّلة')
                    ->falseLabel('التصنيفات غير المفعّلة'),
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
                    ->modalHeading('حذف التصنيف')
                    ->modalDescription(
                        'هل أنت متأكد من حذف هذا التصنيف؟ سيتم فصل المنتجات التابعة له عن التصنيف.'
                    )
                    ->modalSubmitActionLabel('نعم، حذف التصنيف'),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف التصنيفات المحددة')
                        ->requiresConfirmation(),
                ]),
            ])

            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->striped()
            ->paginated([10, 25, 50])

            ->emptyStateHeading('لا توجد تصنيفات')
            ->emptyStateDescription(
                'ابدأ بإضافة تصنيفات مثل الأغنام والذبائح واللحوم.'
            )
            ->emptyStateIcon('heroicon-o-squares-2x2');
    }
}