<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class SupportTicketController extends Controller
{
    /**
     * إنشاء تذكرة دعم جديدة مع رسالة العميل الأولى.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                'required_without:phone',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
                'required_without:email',
            ],

            'subject' => [
                'required',
                'string',
                'max:200',
            ],

            'category' => [
                'required',
                Rule::in([
                    'general',
                    'order',
                    'product',
                    'delivery',
                    'payment',
                    'complaint',
                    'suggestion',
                    'other',
                ]),
            ],

            'message' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],

            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:5120',
            ],
        ], [
            'name.required' =>
                'اسم العميل مطلوب.',

            'email.email' =>
                'البريد الإلكتروني غير صحيح.',

            'email.required_without' =>
                'يجب إدخال البريد الإلكتروني أو رقم الهاتف.',

            'phone.required_without' =>
                'يجب إدخال رقم الهاتف أو البريد الإلكتروني.',

            'subject.required' =>
                'عنوان الطلب مطلوب.',

            'category.required' =>
                'نوع الطلب مطلوب.',

            'category.in' =>
                'نوع الطلب المحدد غير صحيح.',

            'message.required' =>
                'رسالة طلب الدعم مطلوبة.',

            'message.min' =>
                'يجب ألا تقل الرسالة عن 10 أحرف.',

            'message.max' =>
                'رسالة طلب الدعم طويلة جدًا.',

            'attachment.file' =>
                'المرفق غير صالح.',

            'attachment.mimes' =>
                'المرفق يجب أن يكون صورة أو ملف PDF.',

            'attachment.max' =>
                'حجم المرفق يجب ألا يتجاوز 5 ميغابايت.',
        ]);

        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request
                ->file('attachment')
                ->store('support-tickets', 'public');
        }

        try {
            $ticket = DB::transaction(
                function () use (
                    $request,
                    $validated,
                    $attachmentPath
                ): SupportTicket {
                    $ticket = SupportTicket::query()->create([
                        'user_id' =>
                            $request->user()?->id,

                        'name' =>
                            $validated['name'],

                        'email' =>
                            $validated['email'] ?? null,

                        'phone' =>
                            $validated['phone'] ?? null,

                        'subject' =>
                            $validated['subject'],

                        'category' =>
                            $validated['category'],

                        'priority' =>
                            SupportTicket::PRIORITY_NORMAL,

                        'message' =>
                            $validated['message'],

                        'attachment' =>
                            $attachmentPath,

                        'status' =>
                            SupportTicket::STATUS_NEW,

                        'admin_reply' =>
                            null,

                        'assigned_to' =>
                            null,

                        'replied_at' =>
                            null,

                        'closed_at' =>
                            null,
                    ]);

                    /*
                     * حفظ رسالة العميل الأولى داخل المحادثة.
                     */
                    $ticket->messages()->create([
                        'sender_type' =>
                            SupportMessage::SENDER_CUSTOMER,

                        'sender_id' =>
                            $request->user()?->id,

                        'message' =>
                            $validated['message'],

                        'attachment' =>
                            $attachmentPath,

                        'is_read' =>
                            false,

                        'read_at' =>
                            null,
                    ]);

                    return $ticket;
                }
            );
        } catch (Throwable $exception) {
            if (filled($attachmentPath)) {
                Storage::disk('public')
                    ->delete($attachmentPath);
            }

            throw $exception;
        }

        $ticket->load([
            'messages' => fn ($query) =>
                $query
                    ->with('sender:id,name')
                    ->oldest(),
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'تم إرسال طلبك إلى خدمة العملاء بنجاح.',

            'data' => $this->ticketData(
                $ticket,
                $request
            ),
        ], 201);
    }

    /**
     * متابعة التذكرة بواسطة رقمها
     * والبريد الإلكتروني أو رقم الهاتف.
     */
    public function track(
        Request $request,
        string $ticketNumber
    ): JsonResponse {
        $validated = $request->validate([
            'email' => [
                'nullable',
                'email',
                'required_without:phone',
            ],

            'phone' => [
                'nullable',
                'string',
                'required_without:email',
            ],
        ], [
            'email.email' =>
                'البريد الإلكتروني غير صحيح.',

            'email.required_without' =>
                'أدخل البريد الإلكتروني أو رقم الهاتف.',

            'phone.required_without' =>
                'أدخل رقم الهاتف أو البريد الإلكتروني.',
        ]);

        $query = SupportTicket::query()
            ->where('ticket_number', $ticketNumber);

        if (! empty($validated['email'])) {
            $query->where(
                'email',
                $validated['email']
            );
        }

        if (! empty($validated['phone'])) {
            $query->where(
                'phone',
                $validated['phone']
            );
        }

        $ticket = $query
            ->with([
                'messages' => fn ($query) =>
                    $query
                        ->with('sender:id,name')
                        ->oldest(),
            ])
            ->first();

        if (! $ticket) {
            return response()->json([
                'success' => false,

                'message' =>
                    'لم يتم العثور على طلب دعم مطابق للبيانات.',
            ], 404);
        }

        return response()->json([
            'success' => true,

            'message' =>
                'تم العثور على طلب الدعم.',

            'data' => $this->ticketData(
                $ticket,
                $request
            ),
        ]);
    }

    /**
     * إرسال رد جديد من العميل داخل التذكرة.
     */
    public function reply(
        Request $request,
        string $ticketNumber
    ): JsonResponse {
        $validated = $request->validate([
            'email' => [
                'nullable',
                'email',
                'required_without:phone',
            ],

            'phone' => [
                'nullable',
                'string',
                'required_without:email',
            ],

            'message' => [
                'required',
                'string',
                'min:2',
                'max:5000',
            ],

            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:5120',
            ],
        ], [
            'email.email' =>
                'البريد الإلكتروني غير صحيح.',

            'email.required_without' =>
                'أدخل البريد الإلكتروني أو رقم الهاتف.',

            'phone.required_without' =>
                'أدخل رقم الهاتف أو البريد الإلكتروني.',

            'message.required' =>
                'نص الرد مطلوب.',

            'message.min' =>
                'يجب ألا يقل الرد عن حرفين.',

            'message.max' =>
                'الرد طويل جدًا.',

            'attachment.mimes' =>
                'المرفق يجب أن يكون صورة أو ملف PDF.',

            'attachment.max' =>
                'حجم المرفق يجب ألا يتجاوز 5 ميغابايت.',
        ]);

        $query = SupportTicket::query()
            ->where('ticket_number', $ticketNumber);

        if (! empty($validated['email'])) {
            $query->where(
                'email',
                $validated['email']
            );
        }

        if (! empty($validated['phone'])) {
            $query->where(
                'phone',
                $validated['phone']
            );
        }

        $ticket = $query->first();

        if (! $ticket) {
            return response()->json([
                'success' => false,

                'message' =>
                    'لم يتم العثور على تذكرة مطابقة للبيانات.',
            ], 404);
        }

        if ($ticket->status === SupportTicket::STATUS_CLOSED) {
            return response()->json([
                'success' => false,

                'message' =>
                    'لا يمكن الرد على تذكرة مغلقة.',
            ], 422);
        }

        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request
                ->file('attachment')
                ->store('support-messages', 'public');
        }

        try {
            DB::transaction(
                function () use (
                    $request,
                    $validated,
                    $ticket,
                    $attachmentPath
                ): void {
                    $ticket->messages()->create([
                        'sender_type' =>
                            SupportMessage::SENDER_CUSTOMER,

                        'sender_id' =>
                            $ticket->user_id ===
                            $request->user()?->id
                                ? $ticket->user_id
                                : null,

                        'message' =>
                            $validated['message'],

                        'attachment' =>
                            $attachmentPath,

                        'is_read' =>
                            false,

                        'read_at' =>
                            null,
                    ]);

                    if (
                        $ticket->status ===
                        SupportTicket::STATUS_RESOLVED
                    ) {
                        $ticket->update([
                            'status' =>
                                SupportTicket::STATUS_IN_PROGRESS,

                            'closed_at' =>
                                null,
                        ]);
                    }
                }
            );
        } catch (Throwable $exception) {
            if (filled($attachmentPath)) {
                Storage::disk('public')
                    ->delete($attachmentPath);
            }

            throw $exception;
        }

        $ticket->refresh()->load([
            'messages' => fn ($query) =>
                $query
                    ->with('sender:id,name')
                    ->oldest(),
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'تم إرسال ردك إلى خدمة العملاء.',

            'data' => $this->ticketData(
                $ticket,
                $request
            ),
        ], 201);
    }

    /**
     * تجهيز بيانات التذكرة والمحادثة.
     */
    private function ticketData(
        SupportTicket $ticket,
        Request $request
    ): array {
        return [
            'id' =>
                $ticket->id,

            'ticket_number' =>
                $ticket->ticket_number,

            'name' =>
                $ticket->name,

            'email' =>
                $ticket->email,

            'phone' =>
                $ticket->phone,

            'subject' =>
                $ticket->subject,

            'category' =>
                $ticket->category,

            'priority' =>
                $ticket->priority,

            'status' =>
                $ticket->status,

            'admin_reply' =>
                $ticket->admin_reply,

            'attachment_url' =>
                $this->attachmentUrl(
                    $ticket->attachment,
                    $request
                ),

            'messages' =>
                $ticket->messages
                    ->map(
                        fn (SupportMessage $message): array => [
                            'id' =>
                                $message->id,

                            'sender_type' =>
                                $message->sender_type,

                            'sender_name' =>
                                $message->sender?->name
                                ?? (
                                    $message->isFromAdmin()
                                        ? 'إدارة غنم الوادي'
                                        : $ticket->name
                                ),

                            'message' =>
                                $message->message,

                            'attachment_url' =>
                                $this->attachmentUrl(
                                    $message->attachment,
                                    $request
                                ),

                            'is_read' =>
                                $message->is_read,

                            'read_at' =>
                                $message->read_at
                                    ?->toIso8601String(),

                            'created_at' =>
                                $message->created_at
                                    ?->toIso8601String(),
                        ]
                    )
                    ->values(),

            'replied_at' =>
                $ticket->replied_at
                    ?->toIso8601String(),

            'closed_at' =>
                $ticket->closed_at
                    ?->toIso8601String(),

            'created_at' =>
                $ticket->created_at
                    ?->toIso8601String(),

            'updated_at' =>
                $ticket->updated_at
                    ?->toIso8601String(),
        ];
    }

    /**
     * إنشاء رابط كامل للمرفق.
     */
    private function attachmentUrl(
        ?string $path,
        Request $request
    ): ?string {
        if (blank($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');

        $storageUrl = $publicDisk->url($path);

        if (filter_var($storageUrl, FILTER_VALIDATE_URL)) {
            return $storageUrl;
        }

        return rtrim(
            $request->getSchemeAndHttpHost(),
            '/'
        )
            . '/'
            . ltrim($storageUrl, '/');
    }
}