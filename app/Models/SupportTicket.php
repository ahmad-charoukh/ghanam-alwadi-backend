<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'ticket_number',
        'user_id',
        'name',
        'email',
        'phone',
        'subject',
        'category',
        'priority',
        'message',
        'attachment',
        'status',
        'admin_reply',
        'assigned_to',
        'replied_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'assigned_to' => 'integer',
            'replied_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SupportTicket $ticket): void {
            if (blank($ticket->ticket_number)) {
                $ticket->ticket_number =
                    self::generateTicketNumber();
            }
        });
    }

    /**
     * العميل صاحب طلب الدعم.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /**
     * موظف الإدارة المسؤول عن الطلب.
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_to'
        );
    }

    /**
     * جميع رسائل المحادثة التابعة للتذكرة.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(
            SupportMessage::class,
            'support_ticket_id'
        );
    }

    /**
     * إنشاء رقم تذكرة فريد تلقائيًا.
     */
    private static function generateTicketNumber(): string
    {
        do {
            $ticketNumber = 'SUP-'
                . now()->format('Ymd')
                . '-'
                . Str::upper(Str::random(6));
        } while (
            self::query()
                ->where('ticket_number', $ticketNumber)
                ->exists()
        );

        return $ticketNumber;
    }
}