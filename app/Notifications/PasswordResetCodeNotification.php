<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCodeNotification extends Notification
{
    use Queueable;

    /**
     * إنشاء إشعار رمز استرجاع كلمة المرور.
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
     * محتوى رسالة البريد الإلكتروني.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('رمز استرجاع كلمة المرور - غنم الوادي')
            ->greeting(
                'مرحبًا ' . ($notifiable->name ?? '')
            )
            ->line(
                'وصلنا طلب لتغيير كلمة مرور حسابك في غنم الوادي.'
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
                'إذا لم تطلب تغيير كلمة المرور، تجاهل هذه الرسالة ولا تشارك الرمز مع أي شخص.'
            )
            ->salutation('فريق غنم الوادي');
    }

    /**
     * لا نخزن رمز الاسترجاع في إشعارات قاعدة البيانات.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}