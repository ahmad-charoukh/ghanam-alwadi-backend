<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCodeNotification extends Notification
{
    use Queueable;

    /**
     * إنشاء إشعار رمز التحقق من البريد.
     */
    public function __construct(
        public string $code,
        public int $expiresInMinutes = 10,
    ) {
    }

    /**
     * إرسال الرمز عبر البريد فقط.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * محتوى رسالة التحقق من البريد.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('رمز التحقق من البريد - غنم الوادي')
            ->greeting(
                'مرحبًا ' . ($notifiable->name ?? '')
            )
            ->line(
                'استخدم الرمز التالي لتأكيد بريدك الإلكتروني في غنم الوادي.'
            )
            ->line(
                'رمز التحقق الخاص بك هو: '
                . $this->code
            )
            ->line(
                'صلاحية الرمز '
                . $this->expiresInMinutes
                . ' دقائق فقط.'
            )
            ->line(
                'إذا لم تطلب هذا الرمز، تجاهل هذه الرسالة ولا تشارك الرمز مع أي شخص.'
            )
            ->salutation('فريق غنم الوادي');
    }

    /**
     * لا نخزن رمز التحقق في إشعارات قاعدة البيانات.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
