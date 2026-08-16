<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('هوية المتجر')
                    ->description(
                        'اسم المتجر والشعار الذي سيظهر داخل التطبيق ولوحة التحكم.'
                    )
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        TextInput::make('app_name')
                            ->label('اسم المتجر')
                            ->placeholder('غنم الوادي')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-building-storefront'),

                        FileUpload::make('logo')
                            ->label('شعار المتجر')
                            ->image()
                            ->disk('public')
                            ->directory('settings')
                            ->visibility('public')
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ])
                            ->maxSize(4096)
                            ->imageEditor()
                            ->imageEditorAspectRatioOptions([
                                '1:1',
                                '4:3',
                            ])
                            ->imagePreviewHeight('220')
                            ->openable()
                            ->downloadable()
                            ->helperText(
                                'يفضل استخدام شعار مربع بصيغة PNG أو WEBP، والحجم الأقصى 4 ميغابايت.'
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('معلومات التواصل')
                    ->description(
                        'بيانات التواصل التي ستظهر للعملاء داخل التطبيق.'
                    )
                    ->icon('heroicon-o-phone')
                    ->schema([
                        TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->placeholder('+966500000000')
                            ->tel()
                            ->maxLength(30)
                            ->prefixIcon('heroicon-m-phone')
                            ->helperText(
                                'اكتب الرقم مع مفتاح الدولة.'
                            ),

                        TextInput::make('whatsapp')
                            ->label('رقم الواتساب')
                            ->placeholder('+966500000000')
                            ->tel()
                            ->maxLength(30)
                            ->prefixIcon('heroicon-m-chat-bubble-left-right')
                            ->helperText(
                                'اكتب الرقم مع مفتاح الدولة دون مسافات.'
                            ),

                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->placeholder('info@example.com')
                            ->email()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-envelope'),

                        Textarea::make('address')
                            ->label('عنوان المتجر')
                            ->placeholder(
                                'اكتب عنوان المتجر أو مناطق التوصيل...'
                            )
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Textarea::make('about')
                            ->label('نبذة عن المتجر')
                            ->placeholder(
                                'اكتب نبذة مختصرة عن غنم الوادي والخدمات المقدمة...'
                            )
                            ->rows(5)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('روابط التواصل الاجتماعي')
                    ->description(
                        'اترك أي رابط فارغًا إذا لم يكن للمتجر حساب عليه.'
                    )
                    ->icon('heroicon-o-share')
                    ->schema([
                        TextInput::make('facebook_url')
                            ->label('فيسبوك')
                            ->placeholder('https://facebook.com/...')
                            ->url()
                            ->maxLength(500)
                            ->prefixIcon('heroicon-m-link'),

                        TextInput::make('instagram_url')
                            ->label('إنستغرام')
                            ->placeholder('https://instagram.com/...')
                            ->url()
                            ->maxLength(500)
                            ->prefixIcon('heroicon-m-camera'),

                        TextInput::make('tiktok_url')
                            ->label('تيك توك')
                            ->placeholder('https://tiktok.com/@...')
                            ->url()
                            ->maxLength(500)
                            ->prefixIcon('heroicon-m-video-camera'),

                        TextInput::make('telegram_url')
                            ->label('تيليجرام')
                            ->placeholder('https://t.me/...')
                            ->url()
                            ->maxLength(500)
                            ->prefixIcon('heroicon-m-paper-airplane'),

                        TextInput::make('x_url')
                            ->label('منصة X')
                            ->placeholder('https://x.com/...')
                            ->url()
                            ->maxLength(500)
                            ->prefixIcon('heroicon-m-at-symbol'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('الضريبة والشحن')
                    ->description(
                        'القيم المالية المستخدمة في حساب إجمالي الطلب.'
                    )
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        TextInput::make('tax_percentage')
                            ->label('نسبة الضريبة')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->default(0)
                            ->suffix('%')
                            ->prefixIcon('heroicon-m-receipt-percent'),

                        TextInput::make('shipping_cost')
                            ->label('تكلفة التوصيل')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->suffix('ر.س')
                            ->prefixIcon('heroicon-m-truck'),

                        TextInput::make('free_shipping_amount')
                            ->label('التوصيل المجاني عند')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('ر.س')
                            ->prefixIcon('heroicon-m-gift')
                            ->helperText(
                                'اتركه فارغًا إذا لم يكن هناك توصيل مجاني.'
                            ),

                        Select::make('currency')
                            ->label('العملة')
                            ->options([
                                'SAR' => 'ريال سعودي (SAR)',
                                'TRY' => 'ليرة تركية (TRY)',
                                'USD' => 'دولار أمريكي (USD)',
                            ])
                            ->default('SAR')
                            ->required()
                            ->native(false)
                            ->prefixIcon('heroicon-m-banknotes'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('حالة النظام')
                    ->description(
                        'تحكم بإتاحة التطبيق للعملاء أثناء أعمال الصيانة.'
                    )
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->schema([
                        Toggle::make('maintenance_mode')
                            ->label('تفعيل وضع الصيانة')
                            ->helperText(
                                'عند تفعيله يمكن منع العملاء من استخدام المتجر مؤقتًا.'
                            )
                            ->default(false)
                            ->inline(false)
                            ->onIcon('heroicon-m-wrench-screwdriver')
                            ->offIcon('heroicon-m-check-circle')
                            ->onColor('danger')
                            ->offColor('success'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}