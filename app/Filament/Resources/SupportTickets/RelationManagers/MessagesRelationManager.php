<?php

namespace App\Filament\Resources\SupportTickets\RelationManagers;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Notifications\SupportReplyNotification;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Table;
use Throwable;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    /**
     * إبقاء المحادثة قابلة للرد حتى من صفحة عرض التذكرة.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('message')
                    ->label('الرسالة')
                    ->placeholder('اكتب رد خدمة العملاء هنا...')
                    ->required()
                    ->rows(5)
                    ->minLength(2)
                    ->maxLength(5000)
                    ->autofocus()
                    ->columnSpanFull(),

                FileUpload::make('attachment')
                    ->label('إرفاق صورة أو ملف')
                    ->disk('public')
                    ->directory('support-messages')
                    ->visibility('public')
                    ->acceptedFileTypes([
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                        'application/pdf',
                    ])
                    ->maxSize(5120)
                    ->openable()
                    ->downloadable()
                    ->preventFilePathTampering()
                    ->helperText(
                        'JPG أو PNG أو WEBP أو PDF، وبحد أقصى 5 ميغابايت.'
                    )
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        $this->markCustomerMessagesAsRead();

        return $table
            ->heading('المحادثة المباشرة')
            ->description(
                'يمكن للعميل والإدارة إرسال عدد غير محدود من الرسائل. يتم تحديث المحادثة تلقائيًا.'
            )
            ->recordTitleAttribute('message')
            ->columns([
                View::make(
                    'filament.support-tickets.message-bubble'
                ),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make('sendReply')
                    ->label('إرسال رسالة جديدة')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('success')
                    ->modalHeading('إرسال رسالة إلى العميل')
                    ->modalDescription(
                        'ستظهر الرسالة مباشرة داخل نفس المحادثة.'
                    )
                    ->modalSubmitActionLabel('إرسال الرسالة')
                    ->createAnother(false)
                    ->mutateDataUsing(
                        function (array $data): array {
                            $data['sender_type'] =
                                SupportMessage::SENDER_ADMIN;

                            $data['sender_id'] = auth()->id();

                            // تبقى غير مقروءة حتى يفتحها العميل.
                            $data['is_read'] = false;
                            $data['read_at'] = null;

                            return $data;
                        }
                    )
                    ->after(
                        function (SupportMessage $record): void {
                            $this->afterAdminMessageCreated(
                                $record
                            );
                        }
                    )
                    ->successNotificationTitle(
                        'تم إرسال الرسالة إلى المحادثة'
                    ),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('id', 'asc')
            ->paginated(false)
            ->poll('5s')
            ->emptyStateHeading('لا توجد رسائل بعد')
            ->emptyStateDescription(
                'اضغط على إرسال رسالة جديدة لبدء المحادثة مع العميل.'
            )
            ->emptyStateIcon(
                'heroicon-o-chat-bubble-left-right'
            );
    }

    /**
     * اعتبار رسائل العميل مقروءة عند فتح المحادثة في لوحة الإدارة.
     */
    private function markCustomerMessagesAsRead(): void
    {
        $this->getOwnerRecord()
            ->messages()
            ->where(
                'sender_type',
                SupportMessage::SENDER_CUSTOMER
            )
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * تحديث بيانات التذكرة وإرسال التنبيه بعد كل رسالة إدارية.
     */
    private function afterAdminMessageCreated(
        SupportMessage $message
    ): void {
        $ticket = $message->supportTicket;

        if (! $ticket) {
            return;
        }

        $ticket->update([
            // يبقى هذا الحقل للتوافق مع الصفحات القديمة فقط.
            // مصدر المحادثة الحقيقي هو جدول support_messages.
            'admin_reply' => $message->message,
            'replied_at' => $message->created_at ?? now(),
            'status' => $ticket->status === SupportTicket::STATUS_NEW
                ? SupportTicket::STATUS_IN_PROGRESS
                : $ticket->status,
        ]);

        $customer = $ticket->user()->first();

        if (! $customer) {
            return;
        }

        try {
            $customer->notify(
                new SupportReplyNotification(
                    $ticket->fresh(),
                    $message
                )
            );
        } catch (Throwable $exception) {
            // فشل التنبيه لا يجب أن يمنع حفظ الرسالة.
            report($exception);
        }
    }
}