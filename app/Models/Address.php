<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'country',
        'city',
        'district',
        'street',
        'building_number',
        'apartment_number',
        'postal_code',
        'additional_details',
        'latitude',
        'longitude',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_default' => 'boolean',
        ];
    }

    /**
     * العميل صاحب العنوان.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /**
     * إنشاء نص كامل للعنوان.
     */
    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->country,
            $this->city,
            $this->district,
            $this->street,
            $this->building_number
                ? 'مبنى ' . $this->building_number
                : null,
            $this->apartment_number
                ? 'شقة ' . $this->apartment_number
                : null,
            $this->postal_code,
            $this->additional_details,
        ])
            ->filter()
            ->implode('، ');
    }
}