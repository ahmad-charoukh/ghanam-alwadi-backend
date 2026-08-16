<?php

namespace App\Filament\Resources\ContentPages\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ContentPageForm
{
    public static function configure(
        Schema $schema
    ): Schema {
        return $schema
            ->components([
                Section::make('محتوى الصفحة')
                    ->description(
                        'اكتب عنوان ومحتوى الصفحة الذي سيظهر للعملاء.'
                    )
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان الصفحة')
                            ->placeholder(
                                'مثال: سياسة الخصوصية'
                            )
                            ->required()
                            ->minLength(3)
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                function (
                                    Get $get,
                                    Set $set,
                                    ?string $old,
                                    ?string $state
                                ): void {
                                    if (
                                        filled($get('slug'))
                                        && $get('slug')
                                            !== Str::slug(
                                                (string) $old
                                            )
                                    ) {
                                        return;
                                    }

                                    $set(
                                        'slug',
                                        Str::slug(
                                            (string) $state
                                        )
                                    );
                                }
                            ),

                        TextInput::make('slug')
                            ->label('رابط الصفحة')
                            ->placeholder(
                                'privacy-policy'
                            )
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true
                            )
                            ->alphaDash()
                            ->helperText(
                                'يستخدم داخل رابط الصفحة، مثال: privacy-policy'
                            ),

                        Textarea::make('excerpt')
                            ->label('وصف مختصر')
                            ->placeholder(
                                'وصف مختصر يظهر في قائمة الصفحات...'
                            )
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),

                        RichEditor::make('content')
                            ->label('محتوى الصفحة')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('النشر والترتيب')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('نشر الصفحة')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->onIcon('heroicon-m-eye')
                            ->offIcon(
                                'heroicon-m-eye-slash'
                            )
                            ->helperText(
                                'الصفحة المخفية لن تظهر في التطبيق.'
                            ),

                        TextInput::make('sort_order')
                            ->label('ترتيب الظهور')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText(
                                'الرقم الأصغر يظهر أولًا.'
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('إعدادات محركات البحث SEO')
                    ->description(
                        'حقول اختيارية لتحسين ظهور الصفحة في نتائج البحث.'
                    )
                    ->schema([
                        TextInput::make('meta_title')
                            ->label('عنوان SEO')
                            ->maxLength(255)
                            ->placeholder(
                                'اتركه فارغًا لاستخدام عنوان الصفحة'
                            ),

                        Textarea::make(
                            'meta_description'
                        )
                            ->label('وصف SEO')
                            ->maxLength(1000)
                            ->rows(3)
                            ->placeholder(
                                'اتركه فارغًا لاستخدام الوصف المختصر'
                            )
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}