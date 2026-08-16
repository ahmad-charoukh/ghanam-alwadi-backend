<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('محتوى البنر')
                    ->description(
                        'أدخل النصوص التي ستظهر فوق صورة البنر داخل التطبيق.'
                    )
                    ->icon('heroicon-o-megaphone')
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان البنر')
                            ->placeholder('مثال: أفضل الأغنام بأسعار مميزة')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-megaphone'),

                        TextInput::make('subtitle')
                            ->label('العنوان الفرعي')
                            ->placeholder('مثال: اطلب الآن واستفد من العرض')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-document-text'),

                        TextInput::make('button_text')
                            ->label('نص الزر')
                            ->placeholder('مثال: تسوق الآن')
                            ->maxLength(100)
                            ->prefixIcon('heroicon-m-cursor-arrow-rays')
                            ->helperText(
                                'اتركه فارغًا إذا كنت لا تريد إظهار زر داخل البنر.'
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('صورة البنر')
                    ->description(
                        'ارفع صورة أفقية عالية الجودة ومناسبة للصفحة الرئيسية.'
                    )
                    ->icon('heroicon-o-photo')
                    ->schema([
                        FileUpload::make('image')
                            ->label('صورة البنر')
                            ->image()
                            ->required()
                            ->disk('public')
                            ->directory('banners')
                            ->visibility('public')
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ])
                            ->maxSize(5120)
                            ->imageEditor()
                            ->imageEditorAspectRatioOptions([
                                '16:9',
                                '3:1',
                                '4:3',
                            ])
                            ->imagePreviewHeight('300')
                            ->openable()
                            ->downloadable()
                            ->helperText(
                                'يفضّل استخدام صورة أفقية بنسبة 16:9، والحجم الأقصى 5 ميغابايت.'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('رابط البنر')
                    ->description(
                        'حدد ما الذي سيحدث عندما يضغط العميل على البنر أو الزر.'
                    )
                    ->icon('heroicon-o-link')
                    ->schema([
                        Select::make('link_type')
                            ->label('نوع الرابط')
                            ->options([
                                'none' => 'بدون رابط',
                                'product' => 'فتح منتج',
                                'category' => 'فتح تصنيف',
                                'external' => 'فتح رابط خارجي',
                            ])
                            ->default('none')
                            ->required()
                            ->native(false)
                            ->live()
                            ->prefixIcon('heroicon-m-link')
                            ->afterStateUpdated(
                                function (Set $set): void {
                                    $set('link_id', null);
                                    $set('external_url', null);
                                }
                            ),

                        Select::make('link_id')
                            ->label(
                                fn (Get $get): string =>
                                    $get('link_type') === 'product'
                                        ? 'المنتج المرتبط'
                                        : 'التصنيف المرتبط'
                            )
                            ->placeholder(
                                fn (Get $get): string =>
                                    $get('link_type') === 'product'
                                        ? 'اختر المنتج'
                                        : 'اختر التصنيف'
                            )
                            ->options(
                                fn (Get $get): array => match (
                                    $get('link_type')
                                ) {
                                    'product' => Product::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all(),

                                    'category' => Category::query()
                                        ->orderBy('sort_order')
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all(),

                                    default => [],
                                }
                            )
                            ->visible(
                                fn (Get $get): bool =>
                                    in_array(
                                        $get('link_type'),
                                        ['product', 'category'],
                                        true
                                    )
                            )
                            ->required(
                                fn (Get $get): bool =>
                                    in_array(
                                        $get('link_type'),
                                        ['product', 'category'],
                                        true
                                    )
                            )
                            ->dehydrated(
                                fn (Get $get): bool =>
                                    in_array(
                                        $get('link_type'),
                                        ['product', 'category'],
                                        true
                                    )
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->prefixIcon('heroicon-m-arrow-top-right-on-square'),

                        TextInput::make('external_url')
                            ->label('الرابط الخارجي')
                            ->placeholder('https://example.com')
                            ->url()
                            ->visible(
                                fn (Get $get): bool =>
                                    $get('link_type') === 'external'
                            )
                            ->required(
                                fn (Get $get): bool =>
                                    $get('link_type') === 'external'
                            )
                            ->dehydrated(
                                fn (Get $get): bool =>
                                    $get('link_type') === 'external'
                            )
                            ->prefixIcon('heroicon-m-globe-alt')
                            ->helperText(
                                'يجب أن يبدأ الرابط بـ https:// أو http://'
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('الظهور والترتيب')
                    ->description(
                        'تحكم بحالة البنر وترتيبه ومدة ظهوره داخل التطبيق.'
                    )
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('البنر مفعّل')
                            ->default(true)
                            ->inline(false)
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('danger')
                            ->helperText(
                                'عند إيقافه لن يظهر البنر للعملاء.'
                            ),

                        TextInput::make('sort_order')
                            ->label('ترتيب الظهور')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->prefixIcon('heroicon-m-bars-arrow-up')
                            ->helperText(
                                'الرقم الأصغر يظهر أولًا.'
                            ),

                        DateTimePicker::make('starts_at')
                            ->label('تاريخ بداية الظهور')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d/m/Y - h:i A')
                            ->prefixIcon('heroicon-m-play')
                            ->helperText(
                                'اتركه فارغًا ليظهر البنر فورًا.'
                            ),

                        DateTimePicker::make('expires_at')
                            ->label('تاريخ انتهاء الظهور')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d/m/Y - h:i A')
                            ->prefixIcon('heroicon-m-clock')
                            ->rule('nullable|after_or_equal:starts_at')
                            ->helperText(
                                'اتركه فارغًا ليبقى البنر دون تاريخ انتهاء.'
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}