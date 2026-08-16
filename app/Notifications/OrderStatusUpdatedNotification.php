<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification
{
    use Queueable;

    /**
     * إنشاء إشعار تحديث حالة الطلب.
     */
    public function __construct(
        public Order $order,
        public ?string $previousStatus = null,
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
            'type' => 'order_status_updated',
            'title' => $this->notificationTitle(),
            'message' => $this->notificationMessage(),
            'icon' => $this->notificationIcon(),
            'action' => [
                'type' => 'order',
                'order_id' => $this->order->id,
                'order_number' =>
                    $this->order->order_number,
            ],
            'order' => [
                'id' => $this->order->id,
                'order_number' =>
                    $this->order->order_number,
                'previous_status' => $this->previousStatus,
                'previous_status_label' =>
                    $this->statusLabel($this->previousStatus),
                'status' => $this->order->status,
                'status_label' =>
                    $this->statusLabel($this->order->status),
                'total' => (float) $this->order->total,
                'currency' => $this->order->currency,
                'updated_at' => $this->order->updated_at
                    ?->toIso8601String(),
            ],
        ];
    }

    /**
     * عنوان الإشعار بحسب حالة الطلب.
     */
    private function notificationTitle(): string
    {
        return match ($this->order->status) {
            Order::STATUS_CONFIRMED =>
                'تم تأكيد طلبك',
            Order::STATUS_PROCESSING =>
                'طلبك قيد التجهيز',
            Order::STATUS_SHIPPED =>
                'تم شحن طلبك',
            Order::STATUS_DELIVERED =>
                'تم تسليم طلبك',
            Order::STATUS_CANCELLED =>
                'تم إلغاء طلبك',
            default =>
                'تم تحديث حالة طلبك',
        };
    }

    /**
     * نص الإشعار بحسب حالة الطلب.
     */
    private function notificationMessage(): string
    {
        $orderNumber = $this->order->order_number;

        return match ($this->order->status) {
            Order::STATUS_CONFIRMED =>
                'تم تأكيد طلبك رقم '
                . $orderNumber
                . ' وسيبدأ تجهيزه قريبًا.',
            Order::STATUS_PROCESSING =>
                'بدأنا بتجهيز طلبك رقم '
                . $orderNumber
                . '.',
            Order::STATUS_SHIPPED =>
                'تم شحن طلبك رقم '
                . $orderNumber
                . ' وهو في طريقه إليك.',
            Order::STATUS_DELIVERED =>
                'تم تسليم طلبك رقم '
                . $orderNumber
                . ' بنجاح. شكرًا لاختيارك غنم الوادي.',
            Order::STATUS_CANCELLED =>
                'تم إلغاء طلبك رقم '
                . $orderNumber
                . '.',
            default =>
                'تم تحديث حالة طلبك رقم '
                . $orderNumber
                . ' إلى «'
                . $this->statusLabel($this->order->status)
                . '».',
        };
    }

    /**
     * أيقونة الإشعار بحسب حالة الطلب.
     */
    private function notificationIcon(): string
    {
        return match ($this->order->status) {
            Order::STATUS_CONFIRMED => 'check-circle',
            Order::STATUS_PROCESSING => 'cog',
            Order::STATUS_SHIPPED => 'truck',
            Order::STATUS_DELIVERED => 'package-check',
            Order::STATUS_CANCELLED => 'x-circle',
            default => 'shopping-bag',
        };
    }

    /**
     * الاسم العربي لحالة الطلب.
     */
    private function statusLabel(?string $status): ?string
    {
        if (blank($status)) {
            return null;
        }

        return match ($status) {
            Order::STATUS_NEW => 'جديد',
            Order::STATUS_CONFIRMED => 'تم التأكيد',
            Order::STATUS_PROCESSING => 'قيد التجهيز',
            Order::STATUS_SHIPPED => 'تم الشحن',
            Order::STATUS_DELIVERED => 'تم التسليم',
            Order::STATUS_CANCELLED => 'ملغي',
            default => 'غير معروف',
        };
    }
}