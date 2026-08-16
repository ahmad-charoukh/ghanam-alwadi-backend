<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات التقييم')
                    ->description(
                        'بيانات العميل والمنتج المرتبطين بالتقييم.'
                    )
                    ->icon('heroicon-o-star')
                    ->schema([
                        Select::make('user_id')
                            ->label('العميل')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('عميل غير مسجل')
                            ->prefixIcon('heroicon-m-user'),

                        Select::make('product_id')
                            ->label('المنتج')
                            ->relationship(
                                name: 'product',
                                titleAttribute: 'name',
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->disabled()
                            ->dehydrated(false)
                            ->prefixIcon('heroicon-m-shopping-bag'),

                        Select::make('order_id')
                            ->label('الطلب المرتبط')
                            ->relationship(
                                name: 'order',
                                titleAttribute: 'order_number',
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('لا يوجد طلب مرتبط')
                            ->prefixIcon('heroicon-m-document-text'),

                        Select::make('rating')
                            ->label('عدد النجوم')
                            ->options([
                                1 => '⭐ نجمة واحدة',
                                2 => '⭐⭐ نجمتان',
                                3 => '⭐⭐⭐ ثلاث نجوم',
                                4 => '⭐⭐⭐⭐ أربع نجوم',
                                5 => '⭐⭐⭐⭐⭐ خمس نجوم',
                            ])
                            ->disabled()
                            ->dehydrated(false)
                            ->native(false)
                            ->prefixIcon('heroicon-m-star'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('محتوى التقييم')
                    ->description(
                        'العنوان والتعليق الذي أرسله العميل.'
                    )
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان التقييم')
                            ->placeholder('لا يوجد عنوان')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefixIcon('heroicon-m-pencil-square'),

                        Textarea::make('comment')
                            ->label('تعليق العميل')
                            ->placeholder('لا يوجد تعليق مكتوب')
                            ->rows(6)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('مراجعة الإدارة')
                    ->description(
                        'وافق على التقييم وأضف ردًا يظهر للعميل.'
                    )
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Toggle::make('is_approved')
                            ->label('الموافقة على نشر التقييم')
                            ->helperText(
                                'عند التفعيل سيصبح التقييم مسموحًا للظهور داخل التطبيق.'
                            )
                            ->live()
                            ->inline(false)
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('danger')
                            ->afterStateUpdated(
                                function (
                                    Set $set,
                                    Get $get,
                                    bool $state
                                ): void {
                                    if ($state) {
                                        if (blank($get('approved_at'))) {
                                            $set('approved_at', now());
                                        }

                                        return;
                                    }

                                    $set('approved_at', null);
                                }
                            ),

                        DateTimePicker::make('approved_at')
                            ->label('تاريخ الموافقة')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d/m/Y - h:i A')
                            ->prefixIcon('heroicon-m-calendar-days')
                            ->helperText(
                                'يتم تعبئته تلقائيًا عند الموافقة ويمكن تعديله.'
                            ),

                        Textarea::make('admin_reply')
                            ->label('رد الإدارة')
                            ->placeholder(
                                'مثال: نشكرك على تقييمك وثقتك بمتجر غنم الوادي...'
                            )
                            ->rows(5)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}