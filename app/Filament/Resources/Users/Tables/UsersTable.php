<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder =>
                    $query
                        ->withCount('orders')
                        ->withSum(
                            'orders',
                            'total'
                        )
            )
            ->columns([
                TextColumn::make('name')
                    ->label('اسم المستخدم')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(
                        fn (User $record): string =>
                            'رقم المستخدم: #'
                            . $record->id
                    ),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->icon('heroicon-m-envelope')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(
                        'تم نسخ البريد الإلكتروني'
                    ),

                TextColumn::make('is_admin')
                    ->label('نوع الحساب')
                    ->formatStateUsing(
                        fn (bool $state): string =>
                            $state
                                ? 'إدارة'
                                : 'عميل'
                    )
                    ->badge()
                    ->color(
                        fn (bool $state): string =>
                            $state
                                ? 'danger'
                                : 'info'
                    )
                    ->icon(
                        fn (bool $state): string =>
                            $state
                                ? 'heroicon-m-shield-check'
                                : 'heroicon-m-user'
                    )
                    ->sortable(),

                TextColumn::make('email_status')
                    ->label('توثيق البريد')
                    ->state(
                        fn (User $record): string =>
                            $record->email_verified_at
                                ? 'موثّق'
                                : 'غير موثّق'
                    )
                    ->badge()
                    ->color(
                        fn (
                            User $record
                        ): string =>
                            $record->email_verified_at
                                ? 'success'
                                : 'warning'
                    )
                    ->icon(
                        fn (
                            User $record
                        ): string =>
                            $record->email_verified_at
                                ? 'heroicon-m-check-badge'
                                : 'heroicon-m-exclamation-circle'
                    ),

                TextColumn::make('orders_count')
                    ->label('عدد الطلبات')
                    ->formatStateUsing(
                        fn (mixed $state): string =>
                            number_format(
                                (int) $state
                            ) . ' طلب'
                    )
                    ->badge()
                    ->color('info')
                    ->icon(
                        'heroicon-m-shopping-cart'
                    )
                    ->sortable(),

                TextColumn::make('orders_sum_total')
                    ->label('إجمالي المشتريات')
                    ->formatStateUsing(
                        fn (mixed $state): string =>
                            number_format(
                                (float) ($state ?? 0),
                                2
                            ) . ' ر.س'
                    )
                    ->icon(
                        'heroicon-m-banknotes'
                    )
                    ->color('success')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime(
                        'd/m/Y - h:i A'
                    )
                    ->icon(
                        'heroicon-m-calendar-days'
                    )
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->since()
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),
            ])
            ->filters([
                TernaryFilter::make('is_admin')
                    ->label('نوع الحساب')
                    ->placeholder(
                        'جميع المستخدمين'
                    )
                    ->trueLabel(
                        'حسابات الإدارة'
                    )
                    ->falseLabel(
                        'حسابات العملاء'
                    ),

                TernaryFilter::make(
                    'email_verified_at'
                )
                    ->label('توثيق البريد')
                    ->placeholder(
                        'جميع حالات التوثيق'
                    )
                    ->trueLabel(
                        'البريد الموثق'
                    )
                    ->falseLabel(
                        'البريد غير الموثق'
                    )
                    ->nullable(),

                Filter::make('registration_date')
                    ->label('تاريخ التسجيل')
                    ->form([
                        DatePicker::make(
                            'created_from'
                        )
                            ->label('من تاريخ')
                            ->native(false),

                        DatePicker::make(
                            'created_until'
                        )
                            ->label('حتى تاريخ')
                            ->native(false),
                    ])
                    ->columns(2)
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {
                            return $query
                                ->when(
                                    $data['created_from']
                                    ?? null,
                                    fn (
                                        Builder $query,
                                        string $date
                                    ): Builder =>
                                        $query->whereDate(
                                            'created_at',
                                            '>=',
                                            $date
                                        )
                                )
                                ->when(
                                    $data['created_until']
                                    ?? null,
                                    fn (
                                        Builder $query,
                                        string $date
                                    ): Builder =>
                                        $query->whereDate(
                                            'created_at',
                                            '<=',
                                            $date
                                        )
                                );
                        }
                    ),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('عرض')
                    ->icon('heroicon-m-eye')
                    ->color('info'),

                EditAction::make()
                    ->label('تعديل')
                    ->icon(
                        'heroicon-m-pencil-square'
                    )
                    ->color('primary'),

                DeleteAction::make()
                    ->label('حذف')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(
                        'حذف حساب المستخدم'
                    )
                    ->modalDescription(
                        fn (
                            User $record
                        ): string =>
                            'هل أنت متأكد من حذف حساب '
                            . $record->name
                            . '؟ لا يمكن التراجع عن هذه العملية.'
                    )
                    ->modalSubmitActionLabel(
                        'نعم، حذف الحساب'
                    )
                    ->hidden(
                        function (
                            User $record
                        ): bool {
                            /*
                             * منع حذف الحساب الحالي.
                             */
                            if (
                                auth()->id()
                                === $record->id
                            ) {
                                return true;
                            }

                            /*
                             * منع حذف آخر مدير.
                             */
                            return $record->is_admin
                                && User::query()
                                    ->where(
                                        'is_admin',
                                        true
                                    )
                                    ->count() <= 1;
                        }
                    ),
            ])
            ->defaultSort(
                'created_at',
                'desc'
            )
            ->striped()
            ->paginated([
                10,
                25,
                50,
                100,
            ])
            ->emptyStateHeading(
                'لا يوجد مستخدمون'
            )
            ->emptyStateDescription(
                'ستظهر حسابات العملاء والإدارة هنا بعد إنشائها.'
            )
            ->emptyStateIcon(
                'heroicon-o-user-group'
            );
    }
}