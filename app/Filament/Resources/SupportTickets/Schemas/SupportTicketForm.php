<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use App\Models\SupportTicket;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات طلب الدعم')
                    ->description('معلومات التذكرة والعميل صاحب الطلب.')
                    ->icon('heroicon-o-lifebuoy')
                    ->schema([
                        TextInput::make('ticket_number')
                            ->label('رقم التذكرة')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefixIcon('heroicon-m-hashtag'),

                        Select::make('user_id')
                            ->label('حساب العميل')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('عميل غير مسجل'),

                        TextInput::make('name')
                            ->label('اسم العميل')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefixIcon('heroicon-m-user'),

                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('غير متوفر')
                            ->prefixIcon('heroicon-m-envelope'),

                        TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('غير متوفر')
                            ->prefixIcon('heroicon-m-phone'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('رسالة العميل')
                    ->description('تفاصيل المشكلة أو الاستفسار المرسل من التطبيق.')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        TextInput::make('subject')
                            ->label('عنوان الطلب')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Select::make('category')
                            ->label('نوع الطلب')
                            ->options([
                                'general' => 'استفسار عام',
                                'order' => 'مشكلة في طلب',
                                'product' => 'استفسار عن منتج',
                                'delivery' => 'التوصيل',
                                'payment' => 'الدفع',
                                'complaint' => 'شكوى',
                                'suggestion' => 'اقتراح',
                                'other' => 'أخرى',
                            ])
                            ->native(false)
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('attachment')
                            ->label('المرفق')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('لا يوجد مرفق'),

                        Textarea::make('message')
                            ->label('رسالة العميل')
                            ->rows(7)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('متابعة الإدارة')
                    ->description('تحديد حالة وأولوية الطلب وإرسال الرد للعميل.')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Select::make('priority')
                            ->label('الأولوية')
                            ->options([
                                SupportTicket::PRIORITY_LOW => 'منخفضة',
                                SupportTicket::PRIORITY_NORMAL => 'عادية',
                                SupportTicket::PRIORITY_HIGH => 'مرتفعة',
                                SupportTicket::PRIORITY_URGENT => 'عاجلة',
                            ])
                            ->required()
                            ->native(false)
                            ->default(SupportTicket::PRIORITY_NORMAL),

                        Select::make('assigned_to')
                            ->label('الموظف المسؤول')
                            ->relationship(
                                name: 'assignedAgent',
                                titleAttribute: 'name',
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('لم يتم تعيين موظف'),

                        Select::make('status')
                            ->label('حالة الطلب')
                            ->options([
                                SupportTicket::STATUS_NEW => 'جديد',
                                SupportTicket::STATUS_IN_PROGRESS => 'قيد المتابعة',
                                SupportTicket::STATUS_RESOLVED => 'تم الحل',
                                SupportTicket::STATUS_CLOSED => 'مغلق',
                            ])
                            ->required()
                            ->native(false)
                            ->default(SupportTicket::STATUS_NEW)
                            ->live()
                            ->afterStateUpdated(
                                function (
                                    Set $set,
                                    ?string $state
                                ): void {
                                    if (
                                        $state ===
                                        SupportTicket::STATUS_CLOSED
                                    ) {
                                        $set('closed_at', now());

                                        return;
                                    }

                                    $set('closed_at', null);
                                }
                            ),

                        DateTimePicker::make('closed_at')
                            ->label('تاريخ الإغلاق')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d/m/Y - h:i A')
                            ->placeholder('الطلب غير مغلق'),

                        Textarea::make('admin_reply')
                            ->label('رد خدمة العملاء')
                            ->placeholder(
                                'اكتب الرد الذي سيظهر للعميل داخل التطبيق...'
                            )
                            ->rows(7)
                            ->maxLength(3000)
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                function (
                                    Set $set,
                                    Get $get,
                                    ?string $state
                                ): void {
                                    if (filled($state)) {
                                        if (blank($get('replied_at'))) {
                                            $set('replied_at', now());
                                        }

                                        if (
                                            $get('status') ===
                                            SupportTicket::STATUS_NEW
                                        ) {
                                            $set(
                                                'status',
                                                SupportTicket::STATUS_IN_PROGRESS
                                            );
                                        }

                                        return;
                                    }

                                    $set('replied_at', null);
                                }
                            )
                            ->columnSpanFull(),

                        DateTimePicker::make('replied_at')
                            ->label('تاريخ الرد')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d/m/Y - h:i A')
                            ->placeholder('لم يتم الرد بعد'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}