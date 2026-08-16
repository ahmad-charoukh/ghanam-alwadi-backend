<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات المنتج')
                    ->description(
                        'أدخل اسم المنتج وتصنيفه وسعره والكمية المتوفرة.'
                    )
                    ->icon('heroicon-o-shopping-bag')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم المنتج')
                            ->placeholder('مثال: خروف نعيمي')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-shopping-bag'),

                        Select::make('category_id')
                            ->label('التصنيف')
                            ->placeholder('اختر تصنيف المنتج')
                            ->relationship(
                                name: 'productCategory',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (
                                    Builder $query
                                ): Builder => $query
                                    ->orderByDesc('is_active')
                                    ->orderBy('sort_order')
                                    ->orderBy('name')
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->prefixIcon('heroicon-m-squares-2x2')
                            ->helperText(
                                'اختر التصنيف الذي سيظهر المنتج بداخله.'
                            )
                            ->noOptionsMessage(
                                'لا توجد تصنيفات. أضف تصنيفًا أولًا.'
                            )
                            ->afterStateUpdated(
                                function (Set $set, $state): void {
                                    $categoryName = Category::query()
                                        ->whereKey($state)
                                        ->value('name');

                                    $set('category', $categoryName);
                                }
                            ),

                        Hidden::make('category'),

                        TextInput::make('price')
                            ->label('السعر')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('ر.س')
                            ->placeholder('0.00')
                            ->prefixIcon('heroicon-m-banknotes'),

                        TextInput::make('stock')
                            ->label('الكمية المتوفرة')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->suffix('قطعة')
                            ->prefixIcon('heroicon-m-cube'),
                        
                        Textarea::make('description')
                            ->label('وصف المنتج')
                            ->placeholder(
                                'اكتب وصفًا واضحًا للمنتج...'
                            )
                            ->rows(6)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('صورة المنتج')
                    ->description(
                        'ارفع صورة حقيقية وواضحة للمنتج لتظهر داخل تطبيق غنم الوادي.'
                    )
                    ->icon('heroicon-o-photo')
                    ->schema([
                        FileUpload::make('image')
                            ->label('صورة المنتج')
                            ->image()
                            ->disk('public')
                            ->directory('products')
                            ->visibility('public')
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ])
                            ->maxSize(5120)
                            ->imageEditor()
                            ->imageEditorAspectRatioOptions([
                                '1:1',
                                '4:3',
                                '16:9',
                            ])
                            ->imagePreviewHeight('250')
                            ->openable()
                            ->downloadable()
                            ->helperText(
                                'الأنواع المسموحة: JPG وPNG وWEBP، والحجم الأقصى 5 ميغابايت.'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('حالة المنتج')
                    ->description(
                        'تحكم بظهور المنتج داخل التطبيق والصفحة الرئيسية.'
                    )
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('إظهار المنتج في التطبيق')
                            ->helperText(
                                'عند إيقافه لن يظهر المنتج للعملاء.'
                            )
                            ->default(true)
                            ->inline(false)
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('danger'),

                        Toggle::make('is_featured')
                            ->label('منتج مميز')
                            ->helperText(
                                'يظهر ضمن قسم المنتجات المميزة.'
                            )
                            ->default(false)
                            ->inline(false)
                            ->onIcon('heroicon-m-star')
                            ->offIcon('heroicon-m-minus')
                            ->onColor('warning')
                            ->offColor('gray'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}