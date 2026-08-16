<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات كوبون الخصم')
                    ->description(
                        'أدخل رمز الكوبون وحدد نوع وقيمة الخصم.'
                    )
                    ->icon('heroicon-o-ticket')
                    ->schema([
                        TextInput::make('code')
                            ->label('رمز الكوبون')
                            ->placeholder('مثال: WADI20')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(
                                fn (?string $state): string =>
                                    Str::upper(
                                        trim($state ?? '')
                                    )
                            )
                            ->helperText(
                                'سيتم حفظ الرمز بأحرف إنجليزية كبيرة.'
                            ),

                        Select::make('discount_type')
                            ->label('نوع الخصم')
                            ->options([
                                'percentage' =>
                                    'خصم بنسبة مئوية',

                                'fixed' =>
                                    'خصم بمبلغ ثابت',
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(
                                function (
                                    ?string $state,
                                    Set $set
                                ): void {
                                    if (
                                        $state !== 'percentage'
                                    ) {
                                        $set(
                                            'maximum_discount_amount',
                                            null
                                        );
                                    }
                                }
                            ),

                        TextInput::make('discount_value')
                            ->label('قيمة الخصم')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->maxValue(
                                fn (Get $get): ?float =>
                                    $get('discount_type')
                                    === 'percentage'
                                        ? 100
                                        : null
                            )
                            ->suffix(
                                fn (Get $get): string =>
                                    $get('discount_type')
                                    === 'percentage'
                                        ? '%'
                                        : 'ر.س'
                            )
                            ->helperText(
                                fn (Get $get): string =>
                                    $get('discount_type')
                                    === 'percentage'
                                        ? 'يجب أن تكون النسبة بين 0.01 و100%.'
                                        : 'أدخل مبلغ الخصم بالريال.'
                            ),

                        TextInput::make(
                            'minimum_order_amount'
                        )
                            ->label(
                                'الحد الأدنى لقيمة الطلب'
                            )
                            ->numeric()
                            ->minValue(0)
                            ->suffix('ر.س')
                            ->placeholder('بدون حد أدنى')
                            ->helperText(
                                'اتركه فارغًا إذا كان الكوبون يعمل مع أي قيمة طلب.'
                            ),

                        TextInput::make(
                            'maximum_discount_amount'
                        )
                            ->label(
                                'الحد الأقصى لمبلغ الخصم'
                            )
                            ->numeric()
                            ->minValue(0)
                            ->suffix('ر.س')
                            ->visible(
                                fn (Get $get): bool =>
                                    $get('discount_type')
                                    === 'percentage'
                            )
                            ->helperText(
                                'أقصى مبلغ يمكن خصمه عند استخدام النسبة المئوية.'
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('حدود الاستخدام')
                    ->description(
                        'حدد عدد مرات استخدام الكوبون.'
                    )
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        TextInput::make('usage_limit')
                            ->label(
                                'إجمالي مرات الاستخدام'
                            )
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->placeholder('استخدام غير محدود')
                            ->helperText(
                                'اتركه فارغًا للسماح بعدد غير محدود من الاستخدامات.'
                            ),

                        TextInput::make(
                            'usage_limit_per_user'
                        )
                            ->label(
                                'مرات الاستخدام لكل عميل'
                            )
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->default(1),

                        TextInput::make('used_count')
                            ->label(
                                'عدد مرات الاستخدام الحالية'
                            )
                            ->numeric()
                            ->integer()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText(
                                'يتم تحديث هذا العدد تلقائيًا عند إنشاء أو إلغاء الطلبات.'
                            ),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('مدة الصلاحية وحالة الكوبون')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('تاريخ بداية الكوبون')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat(
                                'd/m/Y - h:i A'
                            )
                            ->helperText(
                                'اتركه فارغًا ليبدأ الكوبون مباشرة.'
                            ),

                        DateTimePicker::make('expires_at')
                            ->label('تاريخ انتهاء الكوبون')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat(
                                'd/m/Y - h:i A'
                            )
                            ->after('starts_at')
                            ->helperText(
                                'اتركه فارغًا إذا لم يكن للكوبون تاريخ انتهاء.'
                            ),

                        Toggle::make('is_active')
                            ->label('الكوبون فعال')
                            ->default(true)
                            ->inline(false)
                            ->helperText(
                                'عند إيقافه لن يتمكن العملاء من استخدامه.'
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('وصف الكوبون')
                    ->schema([
                        Textarea::make('description')
                            ->label('الوصف')
                            ->placeholder(
                                'اكتب ملاحظات أو وصفًا للكوبون...'
                            )
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }
}