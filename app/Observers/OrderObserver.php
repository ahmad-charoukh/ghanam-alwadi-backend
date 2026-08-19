<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (
            ! $order->wasChanged('delivery_driver_id') ||
            blank($order->delivery_driver_id)
        ) {
            return;
        }

        $driver = User::query()
            ->whereKey($order->delivery_driver_id)
            ->where('is_delivery_driver', true)
            ->first();

        if (! $driver) {
            return;
        }

        $orderNumber = filled($order->order_number)
            ? $order->order_number
            : '#' . $order->getKey();

        $orderUrl = rtrim(
            (string) config('app.url'),
            '/'
        ) . '/delivery/orders/' . $order->getKey();

        Notification::make()
            ->title('تم تعيين طلب جديد لك')
            ->body("رقم الطلب: {$orderNumber}")
            ->warning()
            ->icon('heroicon-o-truck')
            ->actions([
                Action::make('view')
                    ->label('فتح الطلب')
                    ->button()
                    ->url($orderUrl)
                    ->markAsRead(),
            ])
            ->sendToDatabase($driver);
    }
}