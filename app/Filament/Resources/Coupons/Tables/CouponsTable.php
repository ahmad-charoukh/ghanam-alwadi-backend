<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Models\Coupon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('رمز الكوبون')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->copyable()
                    ->copyMessage('تم نسخ رمز الكوبون')
                    ->icon('heroicon-m-ticket'),

                TextColumn::make('discount_type')
                    ->label('نوع الخصم')
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            match ($state) {
                                'percentage' =>
                                    'نسبة مئوية',

                                'fixed' =>
                                    'مبلغ ثابت',

                                default =>
                                    'غير معروف',
                            }
                    )
                    ->badge()
                    ->color(
                        fn (?string $state): string =>
                            match ($state) {
                                'percentage' => 'info',
                                'fixed' => 'warning',
                                default => 'gray',
                            }
                    )
                    ->sortable(),

                TextColumn::make('discount_value')
                    ->label('قيمة الخصم')
                    ->formatStateUsing(
                        function (
                            mixed $state,
                            Coupon $record
                        ): string {
                            $value = number_format(
                                (float) $state,
                                2
                            );

                            return $record->discount_type
                                === 'percentage'
                                    ? $value . '%'
                                    : $value . ' ر.س';
                        }
                    )
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('minimum_order_amount')
                    ->label('الحد الأدنى للطلب')
                    ->formatStateUsing(
                        fn (mixed $state): string =>
                            filled($state)
                                ? number_format(
                                    (float) $state,
                                    2
                                ) . ' ر.س'
                                : 'بدون حد'
                    )
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('usage_progress')
                    ->label('مرات الاستخدام')
                    ->state(
                        fn (Coupon $record): string =>
                            $record->used_count
                            . ' / '
                            . (
                                $record->usage_limit
                                ?? '∞'
                            )
                    )
                    ->badge()
                    ->color(
                        function (
                            Coupon $record
                        ): string {
                            if (
                                $record->usage_limit === null
                            ) {
                                return 'info';
                            }

                            return $record->used_count
                                >= $record->usage_limit
                                    ? 'danger'
                                    : 'success';
                        }
                    ),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->state(
                        fn (Coupon $record): string =>
                            self::statusLabel($record)
                    )
                    ->badge()
                    ->color(
                        fn (Coupon $record): string =>
                            self::statusColor($record)
                    )
                    ->icon(
                        fn (Coupon $record): string =>
                            self::statusIcon($record)
                    ),

                TextColumn::make('starts_at')
                    ->label('تاريخ البداية')
                    ->dateTime('d/m/Y - h:i A')
                    ->placeholder('يبدأ مباشرة')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('expires_at')
                    ->label('تاريخ الانتهاء')
                    ->dateTime('d/m/Y - h:i A')
                    ->placeholder('بدون انتهاء')
                    ->sortable()
                    ->color(
                        fn (Coupon $record): string =>
                            $record->expires_at?->isPast()
                                ? 'danger'
                                : 'gray'
                    )
                    ->toggleable(),

                TextColumn::make(
                    'maximum_discount_amount'
                )
                    ->label('أقصى مبلغ للخصم')
                    ->formatStateUsing(
                        fn (mixed $state): string =>
                            filled($state)
                                ? number_format(
                                    (float) $state,
                                    2
                                ) . ' ر.س'
                                : 'غير محدد'
                    )
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                TextColumn::make(
                    'usage_limit_per_user'
                )
                    ->label('الحد لكل عميل')
                    ->numeric()
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

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
                SelectFilter::make('discount_type')
                    ->label('نوع الخصم')
                    ->options([
                        'percentage' =>
                            'نسبة مئوية',

                        'fixed' =>
                            'مبلغ ثابت',
                    ])
                    ->native(false),

                TernaryFilter::make('is_active')
                    ->label('حالة التفعيل')
                    ->placeholder('جميع الكوبونات')
                    ->trueLabel('الكوبونات المفعلة')
                    ->falseLabel('الكوبونات الموقفة'),

                Filter::make('currently_valid')
                    ->label('صالحة للاستخدام حاليًا')
                    ->query(
                        fn (Builder $query): Builder =>
                            $query
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->where(
                                    function (
                                        Builder $query
                                    ): void {
                                        $query
                                            ->whereNull(
                                                'starts_at'
                                            )
                                            ->orWhere(
                                                'starts_at',
                                                '<=',
                                                now()
                                            );
                                    }
                                )
                                ->where(
                                    function (
                                        Builder $query
                                    ): void {
                                        $query
                                            ->whereNull(
                                                'expires_at'
                                            )
                                            ->orWhere(
                                                'expires_at',
                                                '>=',
                                                now()
                                            );
                                    }
                                )
                                ->where(
                                    function (
                                        Builder $query
                                    ): void {
                                        $query
                                            ->whereNull(
                                                'usage_limit'
                                            )
                                            ->orWhereColumn(
                                                'used_count',
                                                '<',
                                                'usage_limit'
                                            );
                                    }
                                )
                    ),
            ])
            ->recordActions([
                Action::make('toggle_status')
                    ->label(
                        fn (Coupon $record): string =>
                            $record->is_active
                                ? 'إيقاف'
                                : 'تفعيل'
                    )
                    ->icon(
                        fn (Coupon $record): string =>
                            $record->is_active
                                ? 'heroicon-m-pause'
                                : 'heroicon-m-play'
                    )
                    ->color(
                        fn (Coupon $record): string =>
                            $record->is_active
                                ? 'warning'
                                : 'success'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        fn (Coupon $record): string =>
                            $record->is_active
                                ? 'إيقاف كوبون الخصم'
                                : 'تفعيل كوبون الخصم'
                    )
                    ->action(
                        fn (Coupon $record) =>
                            $record->update([
                                'is_active' =>
                                    ! $record->is_active,
                            ])
                    )
                    ->successNotificationTitle(
                        'تم تحديث حالة الكوبون'
                    ),

                EditAction::make()
                    ->label('تعديل')
                    ->icon(
                        'heroicon-m-pencil-square'
                    ),

                DeleteAction::make()
                    ->label('حذف')
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation()
                    ->modalHeading('حذف كوبون الخصم')
                    ->modalDescription(
                        'هل أنت متأكد من حذف هذا الكوبون؟'
                    )
                    ->modalSubmitActionLabel(
                        'نعم، حذف'
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('تفعيل المحدد')
                        ->icon('heroicon-m-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(
                            function (
                                Collection $records
                            ): void {
                                $records->each(
                                    fn (
                                        Coupon $record
                                    ) => $record->update([
                                        'is_active' => true,
                                    ])
                                );
                            }
                        )
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('deactivate')
                        ->label('إيقاف المحدد')
                        ->icon('heroicon-m-pause')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(
                            function (
                                Collection $records
                            ): void {
                                $records->each(
                                    fn (
                                        Coupon $record
                                    ) => $record->update([
                                        'is_active' => false,
                                    ])
                                );
                            }
                        )
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->label('حذف المحدد')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(
                'لا توجد كوبونات خصم'
            )
            ->emptyStateDescription(
                'أنشئ كوبونًا جديدًا ليتمكن العملاء من استخدامه عند الطلب.'
            )
            ->emptyStateIcon(
                'heroicon-o-ticket'
            );
    }

    /**
     * اسم حالة الكوبون.
     */
    private static function statusLabel(
        Coupon $coupon
    ): string {
        if (! $coupon->is_active) {
            return 'متوقف';
        }

        if ($coupon->starts_at?->isFuture()) {
            return 'لم يبدأ';
        }

        if ($coupon->expires_at?->isPast()) {
            return 'منتهي';
        }

        if (
            $coupon->usage_limit !== null
            && $coupon->used_count
                >= $coupon->usage_limit
        ) {
            return 'مستنفد';
        }

        return 'فعال';
    }

    /**
     * لون حالة الكوبون.
     */
    private static function statusColor(
        Coupon $coupon
    ): string {
        return match (
            self::statusLabel($coupon)
        ) {
            'فعال' => 'success',
            'لم يبدأ' => 'info',
            'منتهي' => 'danger',
            'مستنفد' => 'danger',
            default => 'gray',
        };
    }

    /**
     * أيقونة حالة الكوبون.
     */
    private static function statusIcon(
        Coupon $coupon
    ): string {
        return match (
            self::statusLabel($coupon)
        ) {
            'فعال' =>
                'heroicon-m-check-circle',

            'لم يبدأ' =>
                'heroicon-m-clock',

            'منتهي' =>
                'heroicon-m-calendar-days',

            'مستنفد' =>
                'heroicon-m-no-symbol',

            default =>
                'heroicon-m-pause-circle',
        };
    }
}