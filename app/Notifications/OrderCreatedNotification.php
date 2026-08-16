<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification
{
    use Queueable;

    /**
     * إنشاء إشعار الطلب الجديد.
     */
    public function __construct(
        public Order $order,
    ) {
    }

    /**
     * قنوات إرسال الإشعار.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * البيانات المخزنة في جدول الإشعارات.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_created',

            'title' => 'تم استلام طلبك',

            'message' => 'تم استلام طلبك رقم '
                . $this->order->order_number
                . ' وسيتم مراجعته قريبًا.',

            'icon' => 'shopping-bag',

            'action' => [
                'type' => 'order',

                'order_id' =>
                    $this->order->id,

                'order_number' =>
                    $this->order->order_number,
            ],

            'order' => [
                'id' =>
                    $this->order->id,

                'order_number' =>
                    $this->order->order_number,

                'status' =>
                    $this->order->status,

                'payment_status' =>
                    $this->order->payment_status,

                'total' =>
                    (float) $this->order->total,

                'currency' =>
                    $this->order->currency,

                'created_at' =>
                    $this->order->created_at
                        ?->toIso8601String(),
            ],
        ];
    }
}