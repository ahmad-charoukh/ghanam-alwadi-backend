<?php

namespace App\Filament\Resources\Faqs\Tables;

use App\Models\Faq;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')
                    ->label('السؤال')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(80)
                    ->tooltip(
                        fn (Faq $record): string =>
                            $record->question
                    ),

                TextColumn::make('category')
                    ->label('التصنيف')
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            match ($state) {
                                'general' =>
                                    'أسئلة عامة',

                                'products' =>
                                    'المنتجات',

                                'orders' =>
                                    'الطلبات',

                                'payment' =>
                                    'الدفع',

                                'delivery' =>
                                    'التوصيل',

                                'account' =>
                                    'الحساب',

                                'support' =>
                                    'خدمة العملاء',

                                default =>
                                    'غير مصنف',
                            }
                    )
                    ->badge()
                    ->color(
                        fn (?string $state): string =>
                            match ($state) {
                                'products' =>
                                    'success',

                                'orders' =>
                                    'warning',

                                'payment' =>
                                    'info',

                                'delivery' =>
                                    'primary',

                                'account' =>
                                    'gray',

                                'support' =>
                                    'danger',

                                default =>
                                    'gray',
                            }
                    )
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('منشور')
                    ->boolean()
                    ->trueIcon(
                        'heroicon-o-check-circle'
                    )
                    ->falseIcon(
                        'heroicon-o-x-circle'
                    )
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('d/m/Y - h:i A')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('d/m/Y - h:i A')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('التصنيف')
                    ->options([
                        'general' =>
                            'أسئلة عامة',

                        'products' =>
                            'المنتجات',

                        'orders' =>
                            'الطلبات',

                        'payment' =>
                            'الدفع',

                        'delivery' =>
                            'التوصيل',

                        'account' =>
                            'الحساب',

                        'support' =>
                            'خدمة العملاء',
                    ])
                    ->native(false),

                TernaryFilter::make('is_active')
                    ->label('حالة النشر')
                    ->placeholder('جميع الأسئلة')
                    ->trueLabel('الأسئلة المنشورة')
                    ->falseLabel('الأسئلة المخفية')
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل'),

                DeleteAction::make()
                    ->label('حذف')
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('نشر المحدد')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(
                            function (
                                Collection $records
                            ): void {
                                $records->each->update([
                                    'is_active' => true,
                                ]);
                            }
                        )
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('hide')
                        ->label('إخفاء المحدد')
                        ->icon(
                            'heroicon-o-eye-slash'
                        )
                        ->color('warning')
                        ->action(
                            function (
                                Collection $records
                            ): void {
                                $records->each->update([
                                    'is_active' => false,
                                ]);
                            }
                        )
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->emptyStateHeading(
                'لا توجد أسئلة شائعة'
            )
            ->emptyStateDescription(
                'أضف أول سؤال شائع ليظهر للعملاء في التطبيق.'
            )
            ->emptyStateIcon(
                'heroicon-o-question-mark-circle'
            );
    }
}