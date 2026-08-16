<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'category',
        'price',
        'stock',
        'image',
        'description',
        'is_active',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * التصنيف التابع له المنتج.
     */
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(
            Category::class,
            'category_id'
        );
    }

    /**
     * عناصر الطلبات التي تحتوي على هذا المنتج.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(
            OrderItem::class,
            'product_id'
        );
    }

    /**
     * عناصر السلة التي تحتوي على هذا المنتج.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(
            CartItem::class,
            'product_id'
        );
    }

    /**
     * العملاء الذين أضافوا المنتج للمفضلة.
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(
            Favorite::class,
            'product_id'
        );
    }

    /**
     * جميع تقييمات المنتج.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(
            Review::class,
            'product_id'
        );
    }

    /**
     * تقييمات المنتج الموافق عليها فقط.
     */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(
            Review::class,
            'product_id'
        )->where('is_approved', true);
    }

    /**
     * متوسط تقييم المنتج من 5.
     */
    public function getAverageRatingAttribute(): float
    {
        return round(
            (float) $this
                ->approvedReviews()
                ->avg('rating'),
            1
        );
    }

    /**
     * عدد التقييمات المنشورة للمنتج.
     */
    public function getReviewsCountAttribute(): int
    {
        return $this
            ->approvedReviews()
            ->count();
    }
}