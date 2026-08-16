<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات العميل والمنتج')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('اسم العميل')
                            ->placeholder('عميل زائر')
                            ->icon('heroicon-m-user'),

                        TextEntry::make('user.email')
                            ->label('البريد الإلكتروني')
                            ->placeholder('غير متوفر')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),

                        TextEntry::make('product.name')
                            ->label('المنتج')
                            ->placeholder('منتج محذوف')
                            ->icon('heroicon-m-shopping-bag'),

                        TextEntry::make('order_id')
                            ->label('رقم الطلب')
                            ->formatStateUsing(
                                fn ($state): string =>
                                    $state ? '#' . $state : 'غير مرتبط بطلب'
                            )
                            ->icon('heroicon-m-document-text'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('تفاصيل التقييم')
                    ->icon('heroicon-o-star')
                    ->schema([
                        TextEntry::make('rating')
                            ->label('عدد النجوم')
                            ->formatStateUsing(
                                fn ($state): string =>
                                    str_repeat('★', (int) $state)
                                    . ' '
                                    . (int) $state
                                    . '/5'
                            )
                            ->badge()
                            ->color(
                                fn ($state): string => match (true) {
                                    (int) $state >= 4 => 'success',
                                    (int) $state === 3 => 'warning',
                                    default => 'danger',
                                }
                            ),

                        TextEntry::make('title')
                            ->label('عنوان التقييم')
                            ->placeholder('بدون عنوان'),

                        TextEntry::make('comment')
                            ->label('تعليق العميل')
                            ->placeholder('لا يوجد تعليق مكتوب')
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label('تاريخ إرسال التقييم')
                            ->dateTime('d/m/Y - h:i A')
                            ->icon('heroicon-m-calendar-days'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('مراجعة الإدارة')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        TextEntry::make('is_approved')
                            ->label('حالة التقييم')
                            ->formatStateUsing(
                                fn ($state): string =>
                                    $state
                                        ? 'تمت الموافقة والنشر'
                                        : 'بانتظار الموافقة'
                            )
                            ->badge()
                            ->color(
                                fn ($state): string =>
                                    $state ? 'success' : 'warning'
                            )
                            ->icon(
                                fn ($state): string =>
                                    $state
                                        ? 'heroicon-m-check-circle'
                                        : 'heroicon-m-clock'
                            ),

                        TextEntry::make('approved_at')
                            ->label('تاريخ الموافقة')
                            ->dateTime('d/m/Y - h:i A')
                            ->placeholder('لم تتم الموافقة بعد')
                            ->icon('heroicon-m-calendar-days'),

                        TextEntry::make('admin_reply')
                            ->label('رد الإدارة')
                            ->placeholder('لم يتم إضافة رد من الإدارة')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}