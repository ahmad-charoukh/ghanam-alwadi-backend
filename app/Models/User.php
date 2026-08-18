<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_delivery_driver',
        'delivery_phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_delivery_driver' => 'boolean',
        ];
    }

    /**
     * السماح بالدخول إلى لوحة Filament
     * لحسابات الإدارة فقط.
     */
    public function canAccessPanel(
        Panel $panel
    ): bool {
        return match ($panel->getId()) {
            'admin' => $this->is_admin === true,
            'delivery' => $this->is_delivery_driver === true,
            default => false,
        };
    }

    /**
     * جميع طلبات العميل.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(
            Order::class,
            'user_id'
        );
    }

    /**
     * جميع تذاكر الدعم الخاصة بالعميل.
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(
            SupportTicket::class,
            'user_id'
        );
    }

    /**
     * عناصر سلة المشتريات الخاصة بالعميل.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(
            CartItem::class,
            'user_id'
        );
    }

    /**
     * المنتجات المفضلة الخاصة بالعميل.
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(
            Favorite::class,
            'user_id'
        );
    }

    /**
     * عناوين التوصيل الخاصة بالعميل.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(
            Address::class,
            'user_id'
        );
    }

    /**
     * تقييمات العميل.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(
            Review::class,
            'user_id'
        );
    }

    /**
     * رسائل الدعم التي أرسلها المستخدم.
     */
    public function supportMessages(): HasMany
    {
        return $this->hasMany(
            SupportMessage::class,
            'sender_id'
        );
    }
    /**
     * الطلبات المعيّنة لهذا المندوب.
     */
    public function assignedDeliveryOrders(): HasMany
    {
        return $this->hasMany(
            Order::class,
            'delivery_driver_id'
        );
    }
}