<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use App\Models\SupportTicket;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupportTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات التذكرة')
                    ->icon('heroicon-o-ticket')
                    ->schema([
                        TextEntry::make('ticket_number')
                            ->label('رقم التذكرة')
                            ->icon('heroicon-m-hashtag')
                            ->copyable()
                            ->copyMessage('تم نسخ رقم التذكرة'),

                        TextEntry::make('created_at')
                            ->label('تاريخ إرسال الطلب')
                            ->dateTime('d/m/Y - h:i A')
                            ->icon('heroicon-m-calendar-days'),

                        TextEntry::make('category')
                            ->label('نوع الطلب')
                            ->formatStateUsing(
                                fn ($state): string => match ($state) {
                                    'general' => 'استفسار عام',
                                    'order' => 'مشكلة في طلب',
                                    'product' => 'استفسار عن منتج',
                                    'delivery' => 'التوصيل',
                                    'payment' => 'الدفع',
                                    'complaint' => 'شكوى',
                                    'suggestion' => 'اقتراح',
                                    'other' => 'أخرى',
                                    default => 'غير محدد',
                                }
                            )
                            ->badge()
                            ->color('info'),

                        TextEntry::make('priority')
                            ->label('الأولوية')
                            ->formatStateUsing(
                                fn ($state): string => match ($state) {
                                    SupportTicket::PRIORITY_LOW =>
                                        'منخفضة',
                                    SupportTicket::PRIORITY_NORMAL =>
                                        'عادية',
                                    SupportTicket::PRIORITY_HIGH =>
                                        'مرتفعة',
                                    SupportTicket::PRIORITY_URGENT =>
                                        'عاجلة',
                                    default => 'غير محددة',
                                }
                            )
                            ->badge()
                            ->color(
                                fn ($state): string => match ($state) {
                                    SupportTicket::PRIORITY_LOW =>
                                        'gray',
                                    SupportTicket::PRIORITY_NORMAL =>
                                        'info',
                                    SupportTicket::PRIORITY_HIGH =>
                                        'warning',
                                    SupportTicket::PRIORITY_URGENT =>
                                        'danger',
                                    default => 'gray',
                                }
                            ),

                        TextEntry::make('status')
                            ->label('حالة الطلب')
                            ->formatStateUsing(
                                fn ($state): string => match ($state) {
                                    SupportTicket::STATUS_NEW =>
                                        'جديد',
                                    SupportTicket::STATUS_IN_PROGRESS =>
                                        'قيد المتابعة',
                                    SupportTicket::STATUS_RESOLVED =>
                                        'تم الحل',
                                    SupportTicket::STATUS_CLOSED =>
                                        'مغلق',
                                    default => 'غير محددة',
                                }
                            )
                            ->badge()
                            ->color(
                                fn ($state): string => match ($state) {
                                    SupportTicket::STATUS_NEW =>
                                        'danger',
                                    SupportTicket::STATUS_IN_PROGRESS =>
                                        'warning',
                                    SupportTicket::STATUS_RESOLVED =>
                                        'success',
                                    SupportTicket::STATUS_CLOSED =>
                                        'gray',
                                    default => 'gray',
                                }
                            )
                            ->icon(
                                fn ($state): string => match ($state) {
                                    SupportTicket::STATUS_NEW =>
                                        'heroicon-m-bell-alert',
                                    SupportTicket::STATUS_IN_PROGRESS =>
                                        'heroicon-m-clock',
                                    SupportTicket::STATUS_RESOLVED =>
                                        'heroicon-m-check-circle',
                                    SupportTicket::STATUS_CLOSED =>
                                        'heroicon-m-lock-closed',
                                    default =>
                                        'heroicon-m-question-mark-circle',
                                }
                            ),

                        TextEntry::make('assignedAgent.name')
                            ->label('الموظف المسؤول')
                            ->placeholder('لم يتم تعيين موظف')
                            ->icon('heroicon-m-user-circle'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('بيانات العميل')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextEntry::make('name')
                            ->label('اسم العميل')
                            ->icon('heroicon-m-user'),

                        TextEntry::make('user.name')
                            ->label('حساب العميل')
                            ->placeholder('عميل غير مسجل')
                            ->icon('heroicon-m-identification'),

                        TextEntry::make('email')
                            ->label('البريد الإلكتروني')
                            ->placeholder('غير متوفر')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),

                        TextEntry::make('phone')
                            ->label('رقم الهاتف')
                            ->placeholder('غير متوفر')
                            ->icon('heroicon-m-phone')
                            ->copyable(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('رسالة العميل')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        TextEntry::make('subject')
                            ->label('عنوان الطلب')
                            ->columnSpanFull(),

                        TextEntry::make('message')
                            ->label('تفاصيل الرسالة')
                            ->columnSpanFull(),

                        TextEntry::make('attachment')
                            ->label('المرفق')
                            ->placeholder('لا يوجد مرفق')
                            ->icon('heroicon-m-paper-clip')
                            ->copyable(),
                    ])
                    ->columnSpanFull(),

                Section::make('رد ومتابعة خدمة العملاء')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        TextEntry::make('admin_reply')
                            ->label('رد خدمة العملاء')
                            ->placeholder('لم يتم الرد على الطلب بعد')
                            ->columnSpanFull(),

                        TextEntry::make('replied_at')
                            ->label('تاريخ الرد')
                            ->dateTime('d/m/Y - h:i A')
                            ->placeholder('لم يتم الرد بعد')
                            ->icon('heroicon-m-chat-bubble-left-ellipsis'),

                        TextEntry::make('closed_at')
                            ->label('تاريخ الإغلاق')
                            ->dateTime('d/m/Y - h:i A')
                            ->placeholder('الطلب غير مغلق')
                            ->icon('heroicon-m-lock-closed'),

                        TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime('d/m/Y - h:i A')
                            ->icon('heroicon-m-arrow-path'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}