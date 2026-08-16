<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Order;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات المستخدم')
                    ->description(
                        'المعلومات الأساسية ونوع الحساب.'
                    )
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextEntry::make('id')
                            ->label('رقم المستخدم')
                            ->formatStateUsing(
                                fn (mixed $state): string =>
                                    '#' . $state
                            )
                            ->icon(
                                'heroicon-m-identification'
                            ),

                        TextEntry::make('name')
                            ->label('اسم المستخدم')
                            ->icon('heroicon-m-user')
                            ->weight('bold'),

                        TextEntry::make('email')
                            ->label('البريد الإلكتروني')
                            ->icon(
                                'heroicon-m-envelope'
                            )
                            ->copyable()
                            ->copyMessage(
                                'تم نسخ البريد الإلكتروني'
                            ),

                        TextEntry::make('is_admin')
                            ->label('نوع الحساب')
                            ->formatStateUsing(
                                fn (
                                    bool $state
                                ): string =>
                                    $state
                                        ? 'حساب إدارة'
                                        : 'حساب عميل'
                            )
                            ->badge()
                            ->color(
                                fn (
                                    bool $state
                                ): string =>
                                    $state
                                        ? 'danger'
                                        : 'info'
                            )
                            ->icon(
                                fn (
                                    bool $state
                                ): string =>
                                    $state
                                        ? 'heroicon-m-shield-check'
                                        : 'heroicon-m-user'
                            ),

                        TextEntry::make(
                            'email_verification_status'
                        )
                            ->label(
                                'حالة توثيق البريد'
                            )
                            ->state(
                                fn (
                                    User $record
                                ): string =>
                                    $record
                                        ->email_verified_at
                                            ? 'موثّق'
                                            : 'غير موثّق'
                            )
                            ->badge()
                            ->color(
                                fn (
                                    string $state
                                ): string =>
                                    $state === 'موثّق'
                                        ? 'success'
                                        : 'warning'
                            )
                            ->icon(
                                fn (
                                    string $state
                                ): string =>
                                    $state === 'موثّق'
                                        ? 'heroicon-m-check-badge'
                                        : 'heroicon-m-exclamation-circle'
                            ),

                        TextEntry::make(
                            'email_verified_at'
                        )
                            ->label(
                                'تاريخ توثيق البريد'
                            )
                            ->dateTime(
                                'd/m/Y - h:i A'
                            )
                            ->placeholder(
                                'لم يتم توثيق البريد'
                            )
                            ->icon(
                                'heroicon-m-calendar-days'
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('إحصائيات الطلبات')
                    ->description(
                        'ملخص الطلبات والمشتريات الخاصة بالمستخدم.'
                    )
                    ->icon(
                        'heroicon-o-chart-bar'
                    )
                    ->schema([
                        TextEntry::make('orders_count')
                            ->label('عدد الطلبات')
                            ->state(
                                fn (
                                    User $record
                                ): int =>
                                    $record
                                        ->orders()
                                        ->count()
                            )
                            ->formatStateUsing(
                                fn (
                                    mixed $state
                                ): string =>
                                    number_format(
                                        (int) $state
                                    ) . ' طلب'
                            )
                            ->badge()
                            ->color('info')
                            ->icon(
                                'heroicon-m-shopping-cart'
                            ),

                        TextEntry::make('orders_total')
                            ->label(
                                'إجمالي المشتريات'
                            )
                            ->state(
                                fn (
                                    User $record
                                ): float =>
                                    (float) $record
                                        ->orders()
                                        ->sum('total')
                            )
                            ->formatStateUsing(
                                fn (
                                    mixed $state
                                ): string =>
                                    number_format(
                                        (float) $state,
                                        2
                                    ) . ' ر.س'
                            )
                            ->badge()
                            ->color('success')
                            ->icon(
                                'heroicon-m-banknotes'
                            ),

                        TextEntry::make(
                            'paid_orders_count'
                        )
                            ->label(
                                'الطلبات المدفوعة'
                            )
                            ->state(
                                fn (
                                    User $record
                                ): int =>
                                    $record
                                        ->orders()
                                        ->where(
                                            'payment_status',
                                            'paid'
                                        )
                                        ->count()
                            )
                            ->formatStateUsing(
                                fn (
                                    mixed $state
                                ): string =>
                                    number_format(
                                        (int) $state
                                    ) . ' طلب'
                            )
                            ->badge()
                            ->color('success')
                            ->icon(
                                'heroicon-m-check-circle'
                            ),

                        TextEntry::make(
                            'last_order_date'
                        )
                            ->label('آخر طلب')
                            ->state(
                                fn (
                                    User $record
                                ) =>
                                    $record
                                        ->orders()
                                        ->latest(
                                            'created_at'
                                        )
                                        ->first()
                                        ?->created_at
                            )
                            ->dateTime(
                                'd/m/Y - h:i A'
                            )
                            ->placeholder(
                                'لا توجد طلبات'
                            )
                            ->icon(
                                'heroicon-m-clock'
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('نشاط المستخدم')
                    ->description(
                        'ملخص نشاط المستخدم داخل المتجر.'
                    )
                    ->icon(
                        'heroicon-o-squares-2x2'
                    )
                    ->schema([
                        TextEntry::make(
                            'addresses_count'
                        )
                            ->label(
                                'عناوين التوصيل'
                            )
                            ->state(
                                fn (
                                    User $record
                                ): int =>
                                    $record
                                        ->addresses()
                                        ->count()
                            )
                            ->badge()
                            ->color('info')
                            ->icon(
                                'heroicon-m-map-pin'
                            ),

                        TextEntry::make(
                            'favorites_count'
                        )
                            ->label(
                                'المنتجات المفضلة'
                            )
                            ->state(
                                fn (
                                    User $record
                                ): int =>
                                    $record
                                        ->favorites()
                                        ->count()
                            )
                            ->badge()
                            ->color('danger')
                            ->icon(
                                'heroicon-m-heart'
                            ),

                        TextEntry::make(
                            'reviews_count'
                        )
                            ->label('التقييمات')
                            ->state(
                                fn (
                                    User $record
                                ): int =>
                                    $record
                                        ->reviews()
                                        ->count()
                            )
                            ->badge()
                            ->color('warning')
                            ->icon(
                                'heroicon-m-star'
                            ),

                        TextEntry::make(
                            'support_tickets_count'
                        )
                            ->label('تذاكر الدعم')
                            ->state(
                                fn (
                                    User $record
                                ): int =>
                                    $record
                                        ->supportTickets()
                                        ->count()
                            )
                            ->badge()
                            ->color('primary')
                            ->icon(
                                'heroicon-m-chat-bubble-left-right'
                            ),

                        TextEntry::make(
                            'cart_items_count'
                        )
                            ->label(
                                'عناصر السلة الحالية'
                            )
                            ->state(
                                fn (
                                    User $record
                                ): int =>
                                    $record
                                        ->cartItems()
                                        ->count()
                            )
                            ->badge()
                            ->color('gray')
                            ->icon(
                                'heroicon-m-shopping-bag'
                            ),

                        TextEntry::make(
                            'unread_notifications_count'
                        )
                            ->label(
                                'الإشعارات غير المقروءة'
                            )
                            ->state(
                                fn (
                                    User $record
                                ): int =>
                                    $record
                                        ->unreadNotifications()
                                        ->count()
                            )
                            ->badge()
                            ->color('warning')
                            ->icon(
                                'heroicon-m-bell-alert'
                            ),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('معلومات الحساب')
                    ->icon(
                        'heroicon-o-calendar'
                    )
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(
                                'تاريخ التسجيل'
                            )
                            ->dateTime(
                                'd/m/Y - h:i A'
                            )
                            ->placeholder('-')
                            ->icon(
                                'heroicon-m-calendar-days'
                            ),

                        TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime(
                                'd/m/Y - h:i A'
                            )
                            ->placeholder('-')
                            ->icon(
                                'heroicon-m-arrow-path'
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}