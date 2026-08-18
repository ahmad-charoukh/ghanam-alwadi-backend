<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الطلب')
                    ->description('رقم الطلب والحساب المرتبط به.')
                    ->schema([
                        TextInput::make('order_number')
                            ->label('رقم الطلب')
                            ->placeholder('يتم توليده تلقائيًا')
                            ->readOnly()
                            ->saved(false)
                            ->copyable(
                                copyMessage: 'تم نسخ رقم الطلب',
                            ),

                        Select::make('user_id')
                            ->label('حساب العميل')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('طلب بدون حساب مسجل'),
                    ])
                    ->columns(2),

                Section::make('بيانات العميل')
                    ->description(
                        'معلومات العميل المستخدمة للتواصل والتوصيل.',
                    )
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('اسم العميل')
                            ->placeholder('الاسم الكامل')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('customer_phone')
                            ->label('رقم الجوال')
                            ->placeholder('05xxxxxxxx')
                            ->tel()
                            ->required()
                            ->maxLength(30),

                        TextInput::make('customer_email')
                            ->label('البريد الإلكتروني')
                            ->placeholder('example@email.com')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('city')
                            ->label('المدينة')
                            ->placeholder('مثال: الرياض')
                            ->required()
                            ->maxLength(100),

                        Textarea::make('address')
                            ->label('عنوان التوصيل')
                            ->placeholder(
                                'الحي، الشارع، رقم المبنى وأقرب علامة مميزة...',
                            )
                            ->required()
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('المبالغ المالية')
                    ->description(
                        'يتم حساب إجمالي الطلب تلقائيًا.',
                    )
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('المجموع الفرعي')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->suffix('ر.س')
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                fn (Get $get, Set $set) =>
                                    self::calculateTotal($get, $set),
                            ),

                        TextInput::make('delivery_fee')
                            ->label('رسوم التوصيل')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->suffix('ر.س')
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                fn (Get $get, Set $set) =>
                                    self::calculateTotal($get, $set),
                            ),

                        TextInput::make('discount')
                            ->label('قيمة الخصم')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->suffix('ر.س')
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                fn (Get $get, Set $set) =>
                                    self::calculateTotal($get, $set),
                            ),

                        TextInput::make('total')
                            ->label('الإجمالي النهائي')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->suffix('ر.س')
                            ->readOnly(),
                    ])
                    ->columns(2),

                Section::make('الدفع وحالة الطلب')
                    ->description(
                        'تحكم بحالة الطلب والدفع من لوحة الإدارة.',
                    )
                    ->schema([
                        Select::make('payment_method')
                            ->label('طريقة الدفع')
                            ->options([
                                'cash' => 'الدفع عند الاستلام',
                                'card' => 'بطاقة بنكية',
                                'bank_transfer' => 'تحويل بنكي',
                                'online' => 'دفع إلكتروني',
                            ])
                            ->default('cash')
                            ->required()
                            ->native(false),

                        Select::make('payment_status')
                            ->label('حالة الدفع')
                            ->options([
                                'pending' => 'بانتظار الدفع',
                                'paid' => 'مدفوع',
                                'failed' => 'فشل الدفع',
                                'refunded' => 'تم استرجاع المبلغ',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),

                        Select::make('status')
                            ->label('حالة الطلب')
                            ->options([
                                'new' => 'طلب جديد',
                                'confirmed' => 'تم تأكيد الطلب',
                                'processing' => 'قيد التجهيز',
                                'shipped' => 'خرج للتوصيل',
                                'delivered' => 'تم التسليم',
                                'cancelled' => 'طلب ملغي',
                            ])
                            ->default('new')
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(
                                function (
                                    ?string $state,
                                    Get $get,
                                    Set $set,
                                ): void {
                                    self::updateStatusDates(
                                        $state,
                                        $get,
                                        $set,
                                    );
                                },
                            ),
                    ])
                    ->columns(3),

                Section::make('إدارة التوصيل')
                    ->description(
                        'عيّن الطلب لمندوب توصيل وحدد الملاحظات الخاصة به.'
                    )
                    ->icon('heroicon-o-truck')
                    ->schema([
                        Select::make('delivery_driver_id')
                            ->label('المندوب المسؤول')
                            ->relationship(
                                name: 'deliveryDriver',
                                titleAttribute: 'name',
                                modifyQueryUsing:
                                    fn (Builder $query): Builder =>
                                        $query->where(
                                            'is_delivery_driver',
                                            true
                                        ),
                            )
                            ->searchable([
                                'name',
                                'email',
                            ])
                            ->preload()
                            ->native(false)
                            ->placeholder(
                                'لم يتم تعيين مندوب'
                            )
                            ->noOptionsMessage(
                                'لا توجد حسابات مندوبي توصيل'
                            )
                            ->helperText(
                                'يظهر هنا فقط المستخدمون المفعّل لهم خيار حساب مندوب توصيل.'
                            ),

                        DateTimePicker::make('assigned_at')
                            ->label('وقت تعيين المندوب')
                            ->native(false)
                            ->seconds(false)
                            ->readOnly()
                            ->helperText(
                                'يتم تسجيله تلقائيًا عند تعيين المندوب.'
                            ),

                        Textarea::make('delivery_notes')
                            ->label('ملاحظات للمندوب')
                            ->placeholder(
                                'مثال: التواصل مع العميل قبل الوصول...'
                            )
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('ملاحظات الطلب')
                    ->schema([
                        Textarea::make('customer_notes')
                            ->label('ملاحظات العميل')
                            ->placeholder(
                                'الملاحظات التي كتبها العميل عند الطلب...',
                            )
                            ->rows(4)
                            ->maxLength(2000),

                        Textarea::make('admin_notes')
                            ->label('ملاحظات الإدارة')
                            ->placeholder(
                                'ملاحظات داخلية لا تظهر للعميل...',
                            )
                            ->rows(4)
                            ->maxLength(2000),
                    ])
                    ->columns(2),

                Section::make('مراحل تنفيذ الطلب')
                    ->description(
                        'يتم تعبئة هذه التواريخ تلقائيًا، ويمكن تعديلها يدويًا.',
                    )
                    ->schema([
                        DateTimePicker::make('confirmed_at')
                            ->label('تاريخ تأكيد الطلب'),

                        DateTimePicker::make('shipped_at')
                            ->label('تاريخ الخروج للتوصيل'),

                        DateTimePicker::make('delivered_at')
                            ->label('تاريخ تسليم الطلب'),

                        DateTimePicker::make('cancelled_at')
                            ->label('تاريخ إلغاء الطلب'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    private static function calculateTotal(
        Get $get,
        Set $set,
    ): void {
        $subtotal = (float) ($get('subtotal') ?? 0);
        $deliveryFee = (float) ($get('delivery_fee') ?? 0);
        $discount = (float) ($get('discount') ?? 0);

        $total = max(
            0,
            $subtotal + $deliveryFee - $discount,
        );

        $set('total', number_format($total, 2, '.', ''));
    }

    private static function updateStatusDates(
        ?string $status,
        Get $get,
        Set $set,
    ): void {
        $now = now();

        switch ($status) {
            case 'confirmed':
                if (blank($get('confirmed_at'))) {
                    $set('confirmed_at', $now);
                }

                $set('cancelled_at', null);
                break;

            case 'processing':
                if (blank($get('confirmed_at'))) {
                    $set('confirmed_at', $now);
                }

                $set('cancelled_at', null);
                break;

            case 'shipped':
                if (blank($get('confirmed_at'))) {
                    $set('confirmed_at', $now);
                }

                if (blank($get('shipped_at'))) {
                    $set('shipped_at', $now);
                }

                $set('cancelled_at', null);
                break;

            case 'delivered':
                if (blank($get('confirmed_at'))) {
                    $set('confirmed_at', $now);
                }

                if (blank($get('shipped_at'))) {
                    $set('shipped_at', $now);
                }

                if (blank($get('delivered_at'))) {
                    $set('delivered_at', $now);
                }

                $set('cancelled_at', null);
                break;

            case 'cancelled':
                if (blank($get('cancelled_at'))) {
                    $set('cancelled_at', $now);
                }

                break;

            case 'new':
                $set('confirmed_at', null);
                $set('shipped_at', null);
                $set('delivered_at', null);
                $set('cancelled_at', null);
                break;
        }
    }
}