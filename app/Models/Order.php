<?php

namespace App\Models;

use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_METHOD_CASH = 'cash';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_REFUNDED = 'refunded';

    protected $fillable = [
        'user_id',
        'address_id',
        'coupon_id',
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'city',
        'address',
        'subtotal',
        'delivery_fee',
        'discount',
        'tax_percentage',
        'tax_amount',
        'total',
        'currency',
        'coupon_code',
        'payment_method',
        'payment_status',
        'status',
        'customer_notes',
        'admin_notes',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'address_id' => 'integer',
            'coupon_id' => 'integer',
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        /*
         * إنشاء رقم طلب فريد تلقائيًا.
         */
        static::creating(function (Order $order): void {
            if (filled($order->order_number)) {
                return;
            }

            do {
                $orderNumber = 'GW-'
                    . now()->format('Ymd')
                    . '-'
                    . Str::upper(Str::random(6));
            } while (
                static::query()
                    ->where(
                        'order_number',
                        $orderNumber
                    )
                    ->exists()
            );

            $order->order_number = $orderNumber;
        });

        /*
         * تسجيل أوقات مراحل الطلب تلقائيًا.
         */
        static::updating(function (Order $order): void {
            if (! $order->isDirty('status')) {
                return;
            }

            $timestamp = now();

            switch ($order->status) {
                case self::STATUS_CONFIRMED:
                    $order->confirmed_at ??= $timestamp;
                    break;

                case self::STATUS_PROCESSING:
                    $order->confirmed_at ??= $timestamp;
                    break;

                case self::STATUS_SHIPPED:
                    $order->confirmed_at ??= $timestamp;
                    $order->shipped_at ??= $timestamp;
                    break;

                case self::STATUS_DELIVERED:
                    $order->confirmed_at ??= $timestamp;
                    $order->shipped_at ??= $timestamp;
                    $order->delivered_at ??= $timestamp;
                    break;

                case self::STATUS_CANCELLED:
                    $order->cancelled_at ??= $timestamp;
                    break;
            }
        });

        /*
         * إرسال إشعار عند تغيير حالة الطلب.
         */
        static::updated(function (Order $order): void {
            if (
                ! $order->wasChanged('status')
                || ! $order->user_id
            ) {
                return;
            }

            // Access the related User model instance directly to preserve its type
            $user = $order->user;

            if (! $user) {
                return;
            }

            $previousStatus = $order->getRawOriginal(
                'status'
            );

            $user->notify(
                new OrderStatusUpdatedNotification(
                    $order,
                    is_string($previousStatus)
                        ? $previousStatus
                        : null
                )
            );
        });
    }

    /**
     * العميل صاحب الطلب.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /**
     * عنوان التوصيل المحفوظ المستخدم في الطلب.
     */
    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(
            Address::class,
            'address_id'
        );
    }

    /**
     * الكوبون المستخدم في الطلب.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(
            Coupon::class,
            'coupon_id'
        );
    }

    /**
     * منتجات الطلب.
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            OrderItem::class,
            'order_id'
        );
    }
}