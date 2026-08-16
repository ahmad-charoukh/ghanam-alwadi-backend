<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات التصنيف')
                    ->description('أدخل المعلومات الأساسية الخاصة بالتصنيف')
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم التصنيف')
                            ->placeholder('مثال: الأغنام')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                function (
                                    Set $set,
                                    ?string $state,
                                    string $operation
                                ): void {
                                    if ($operation !== 'create') {
                                        return;
                                    }

                                    $set(
                                        'slug',
                                        Str::slug($state ?? '')
                                    );
                                }
                            )
                            ->prefixIcon('heroicon-m-tag'),

                        TextInput::make('slug')
                            ->label('الرابط المختصر')
                            ->placeholder('مثال: sheep')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText(
                                'يُستخدم في رابط التصنيف، ويُفضّل كتابته بالإنجليزية.'
                            )
                            ->prefixIcon('heroicon-m-link'),

                        Textarea::make('description')
                            ->label('وصف التصنيف')
                            ->placeholder(
                                'اكتب وصفًا مختصرًا وواضحًا للتصنيف...'
                            )
                            ->rows(5)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('صورة التصنيف')
                    ->description(
                        'ارفع صورة واضحة ومناسبة لعرض التصنيف داخل التطبيق.'
                    )
                    ->icon('heroicon-o-photo')
                    ->schema([
                        FileUpload::make('image')
                            ->label('الصورة')
                            ->image()
                            ->disk('public')
                            ->directory('categories')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(4096)
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ])
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('إعدادات الظهور')
                    ->description(
                        'تحكم بحالة التصنيف وترتيبه داخل التطبيق.'
                    )
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('التصنيف مفعّل')
                            ->helperText(
                                'عند إيقافه لن يظهر التصنيف للعملاء.'
                            )
                            ->default(true)
                            ->inline(false)
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('danger'),

                        TextInput::make('sort_order')
                            ->label('ترتيب الظهور')
                            ->helperText(
                                'الرقم الأصغر يظهر أولًا.'
                            )
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->prefixIcon('heroicon-m-bars-arrow-up'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}