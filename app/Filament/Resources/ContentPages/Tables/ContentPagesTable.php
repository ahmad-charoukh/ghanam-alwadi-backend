<?php

namespace App\Filament\Resources\ContentPages\Tables;

use App\Models\ContentPage;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ContentPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('عنوان الصفحة')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(60)
                    ->icon('heroicon-m-document-text'),

                TextColumn::make('slug')
                    ->label('الرابط المختصر')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->copyMessage('تم نسخ الرابط المختصر')
                    ->icon('heroicon-m-link'),

                TextColumn::make('excerpt')
                    ->label('الوصف المختصر')
                    ->limit(70)
                    ->wrap()
                    ->placeholder('لا يوجد وصف')
                    ->toggleable(),

                TextColumn::make('meta_title')
                    ->label('عنوان محركات البحث')
                    ->searchable()
                    ->limit(50)
                    ->placeholder('غير محدد')
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                IconColumn::make('is_active')
                    ->label('حالة النشر')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y - h:i A')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                TextColumn::make('updated_at')
                    ->label('آخر تعديل')
                    ->dateTime('d/m/Y - h:i A')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('حالة النشر')
                    ->placeholder('جميع الصفحات')
                    ->trueLabel('الصفحات المنشورة')
                    ->falseLabel('الصفحات المخفية'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-m-pencil-square'),

                DeleteAction::make()
                    ->label('حذف')
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation()
                    ->modalHeading('حذف الصفحة')
                    ->modalDescription(
                        'هل أنت متأكد من حذف هذه الصفحة؟'
                    )
                    ->modalSubmitActionLabel('نعم، حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('نشر الصفحات المحددة')
                        ->icon('heroicon-m-eye')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(
                            function (
                                Collection $records
                            ): void {
                                $records->each(
                                    fn (
                                        ContentPage $record
                                    ) => $record->update([
                                        'is_active' => true,
                                    ])
                                );
                            }
                        )
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('hide')
                        ->label('إخفاء الصفحات المحددة')
                        ->icon('heroicon-m-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(
                            function (
                                Collection $records
                            ): void {
                                $records->each(
                                    fn (
                                        ContentPage $record
                                    ) => $record->update([
                                        'is_active' => false,
                                    ])
                                );
                            }
                        )
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->label('حذف الصفحات المحددة')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->emptyStateHeading('لا توجد صفحات تعريفية')
            ->emptyStateDescription(
                'أضف صفحات مثل: من نحن، سياسة الخصوصية، والشروط والأحكام.'
            )
            ->emptyStateIcon('heroicon-o-document-text');
    }
}