<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_name',
        'logo',
        'phone',
        'whatsapp',
        'email',
        'address',
        'about',
        'facebook_url',
        'instagram_url',
        'tiktok_url',
        'telegram_url',
        'x_url',
        'tax_percentage',
        'shipping_cost',
        'free_shipping_amount',
        'currency',
        'maintenance_mode',
    ];

    protected function casts(): array
    {
        return [
            'tax_percentage' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'free_shipping_amount' => 'decimal:2',
            'maintenance_mode' => 'boolean',
        ];
    }

    /**
     * جلب إعدادات المتجر، وإنشاؤها تلقائيًا إن لم تكن موجودة.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'app_name' => 'غنم الوادي',
                'currency' => 'SAR',
                'tax_percentage' => 0,
                'shipping_cost' => 0,
                'maintenance_mode' => false,
            ],
        );
    }
}