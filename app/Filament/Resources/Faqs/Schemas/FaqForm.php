<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(
        Schema $schema
    ): Schema {
        return $schema
            ->components([
                TextInput::make('question')
                    ->label('السؤال')
                    ->placeholder(
                        'مثال: ما هي طرق الدفع المتاحة؟'
                    )
                    ->required()
                    ->minLength(5)
                    ->maxLength(500)
                    ->autofocus()
                    ->columnSpanFull(),

                Textarea::make('answer')
                    ->label('الإجابة')
                    ->placeholder(
                        'اكتب إجابة واضحة ومفصلة للعميل...'
                    )
                    ->required()
                    ->minLength(5)
                    ->maxLength(10000)
                    ->rows(8)
                    ->columnSpanFull(),

                Select::make('category')
                    ->label('التصنيف')
                    ->options([
                        'general' =>
                            'أسئلة عامة',

                        'products' =>
                            'المنتجات',

                        'orders' =>
                            'الطلبات',

                        'payment' =>
                            'الدفع',

                        'delivery' =>
                            'التوصيل',

                        'account' =>
                            'الحساب',

                        'support' =>
                            'خدمة العملاء',
                    ])
                    ->default('general')
                    ->required()
                    ->searchable()
                    ->native(false),

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

                Toggle::make('is_active')
                    ->label('نشر السؤال')
                    ->default(true)
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-eye')
                    ->offIcon('heroicon-m-eye-slash')
                    ->helperText(
                        'عند إيقافه لن يظهر السؤال في التطبيق.'
                    ),
            ])
            ->columns(2);
    }
}