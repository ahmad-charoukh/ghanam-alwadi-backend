<?php

namespace App\Notifications;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SupportReplyNotification extends Notification
{
    use Queueable;

    /**
     * إنشاء إشعار رد خدمة العملاء.
     */
    public function __construct(
        public SupportTicket $ticket,
        public SupportMessage $reply,
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
            'type' => 'support_reply',
            'title' => 'رد جديد من خدمة العملاء',
            'message' => 'تم الرد على تذكرتك رقم '
                . $this->ticket->ticket_number
                . ': '
                . Str::limit(
                    $this->reply->message,
                    150
                ),
            'icon' => 'message-circle',
            'action' => [
                'type' => 'support_ticket',
                'ticket_id' => $this->ticket->id,
                'ticket_number' =>
                    $this->ticket->ticket_number,
            ],
            'ticket' => [
                'id' => $this->ticket->id,
                'ticket_number' =>
                    $this->ticket->ticket_number,
                'subject' => $this->ticket->subject,
                'status' => $this->ticket->status,
            ],
            'reply' => [
                'id' => $this->reply->id,
                'message' => $this->reply->message,
                'attachment' => $this->reply->attachment,
                'created_at' => $this->reply->created_at
                    ?->toIso8601String(),
            ],
        ];
    }
}