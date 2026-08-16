<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'minimum_order_amount',
        'maximum_discount_amount',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'minimum_order_amount' => 'decimal:2',
            'maximum_discount_amount' => 'decimal:2',
            'usage_limit' => 'integer',
            'usage_limit_per_user' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * الطلبات التي استخدمت الكوبون.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(
            Order::class,
            'coupon_id'
        );
    }

    /**
     * التحقق من صلاحية الكوبون العامة.
     */
    public function isAvailable(float $subtotal): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (
            $this->starts_at
            && $this->starts_at->isFuture()
        ) {
            return false;
        }

        if (
            $this->expires_at
            && $this->expires_at->isPast()
        ) {
            return false;
        }

        if (
            filled($this->usage_limit)
            && $this->used_count >= $this->usage_limit
        ) {
            return false;
        }

        if (
            filled($this->minimum_order_amount)
            && $subtotal
                < (float) $this->minimum_order_amount
        ) {
            return false;
        }

        return true;
    }

    /**
     * التحقق من حد الاستخدام الخاص بالعميل.
     */
    public function canBeUsedBy(
        User $user,
        float $subtotal
    ): bool {
        if (! $this->isAvailable($subtotal)) {
            return false;
        }

        if (! filled($this->usage_limit_per_user)) {
            return true;
        }

        $userUsageCount = $this->orders()
            ->where('user_id', $user->id)
            ->where(
                'status',
                '!=',
                Order::STATUS_CANCELLED
            )
            ->count();

        return $userUsageCount
            < $this->usage_limit_per_user;
    }

    /**
     * حساب قيمة الخصم.
     */
    public function calculateDiscount(
        float $subtotal
    ): float {
        if (
            $this->discount_type
            === self::TYPE_PERCENTAGE
        ) {
            $discount =
                $subtotal
                * ((float) $this->discount_value / 100);
        } else {
            $discount =
                (float) $this->discount_value;
        }

        if (filled($this->maximum_discount_amount)) {
            $discount = min(
                $discount,
                (float) $this->maximum_discount_amount
            );
        }

        /*
         * الخصم لا يمكن أن يتجاوز قيمة المنتجات.
         */
        return round(
            min($discount, $subtotal),
            2
        );
    }
}