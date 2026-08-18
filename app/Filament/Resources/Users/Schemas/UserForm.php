<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات المستخدم')
                    ->description(
                        'البيانات الأساسية لحساب المستخدم.'
                    )
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم المستخدم')
                            ->placeholder(
                                'أدخل الاسم الكامل'
                            )
                            ->required()
                            ->minLength(2)
                            ->maxLength(150)
                            ->prefixIcon(
                                'heroicon-m-user'
                            ),

                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->placeholder(
                                'example@email.com'
                            )
                            ->email()
                            ->required()
                            ->unique(
                                ignoreRecord: true
                            )
                            ->maxLength(255)
                            ->prefixIcon(
                                'heroicon-m-envelope'
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('الصلاحيات والتوثيق')
                    ->description(
                        'حدد نوع الحساب وحالة توثيق البريد الإلكتروني.'
                    )
                    ->icon(
                        'heroicon-o-shield-check'
                    )
                    ->schema([
                        Toggle::make('is_admin')
                            ->label('حساب إدارة')
                            ->default(false)
                            ->inline(false)
                            ->disabled(
                                function (
                                    ?User $record
                                ): bool {
                                    if (! $record) {
                                        return false;
                                    }

                                    /*
                                     * منع المدير الحالي من إزالة
                                     * صلاحيته بنفسه.
                                     */
                                    if (
                                        auth()->id()
                                        === $record->id
                                    ) {
                                        return true;
                                    }

                                    /*
                                     * منع إزالة صلاحية آخر مدير.
                                     */
                                    return $record->is_admin
                                        && User::query()
                                            ->where(
                                                'is_admin',
                                                true
                                            )
                                            ->count() <= 1;
                                }
                            )
                            ->dehydrated()
                            ->helperText(
                                function (
                                    ?User $record
                                ): string {
                                    if (
                                        $record
                                        && auth()->id()
                                            === $record->id
                                    ) {
                                        return
                                            'لا يمكنك إزالة صلاحية الإدارة من حسابك الحالي.';
                                    }

                                    return
                                        'حساب الإدارة يستطيع الدخول إلى لوحة التحكم وإدارة المتجر.';
                                }
                            ),

                        Toggle::make('is_delivery_driver')
                            ->label('حساب مندوب توصيل')
                            ->default(false)
                            ->inline(false)
                            ->helperText(
                                'فعّل هذا الخيار ليتمكن المستخدم من استلام الطلبات والدخول إلى لوحة المندوب.'
                            )
                            ->dehydrated(),
                        DateTimePicker::make(
                            'email_verified_at'
                        )
                            ->label(
                                'تاريخ توثيق البريد'
                            )
                            ->helperText(
                                'اتركه فارغًا إذا لم يتم توثيق البريد الإلكتروني.'
                            )
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat(
                                'd/m/Y - h:i A'
                            )
                            ->prefixIcon(
                                'heroicon-m-check-badge'
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('كلمة المرور')
                    ->description(
                        'أنشئ كلمة مرور للحساب أو اتركها فارغة عند التعديل.'
                    )
                    ->icon(
                        'heroicon-o-lock-closed'
                    )
                    ->schema([
                        TextInput::make('password')
                            ->label('كلمة المرور')
                            ->placeholder(
                                'أدخل كلمة مرور قوية'
                            )
                            ->password()
                            ->revealable()
                            ->required(
                                fn (
                                    string $operation
                                ): bool =>
                                    $operation === 'create'
                            )
                            ->minLength(8)
                            ->maxLength(255)
                            ->dehydrateStateUsing(
                                fn (
                                    string $state
                                ): string =>
                                    Hash::make($state)
                            )
                            ->dehydrated(
                                fn (
                                    ?string $state
                                ): bool =>
                                    filled($state)
                            )
                            ->helperText(
                                'يجب أن تكون 8 أحرف على الأقل. عند التعديل اتركها فارغة للحفاظ على كلمة المرور الحالية.'
                            )
                            ->prefixIcon(
                                'heroicon-m-key'
                            ),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}