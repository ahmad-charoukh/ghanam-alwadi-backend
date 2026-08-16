@php
    /** @var \App\Models\SupportMessage $message */
    $message = $getRecord();
    $isAdmin = $message->isFromAdmin();
    $senderName = $message->sender?->name
        ?? ($isAdmin ? 'إدارة غنم الوادي' : 'العميل');
    $attachment = trim((string) $message->attachment);
    $attachmentUrl = $attachment !== ''
        ? asset('storage/' . ltrim($attachment, '/'))
        : null;
    $isImage = $attachment !== ''
        && preg_match('/\.(jpe?g|png|webp)$/i', $attachment) === 1;
@endphp

<div
    dir="rtl"
    style="
        display: flex;
        width: 100%;
        justify-content: {{ $isAdmin ? 'flex-start' : 'flex-end' }};
        padding: 5px 8px;
    "
>
    <div
        style="
            width: fit-content;
            max-width: min(78%, 720px);
            padding: 12px 14px 9px;
            border-radius: {{ $isAdmin ? '18px 18px 4px 18px' : '18px 18px 18px 4px' }};
            background: {{ $isAdmin ? '#0d3b2b' : '#f2ead7' }};
            color: {{ $isAdmin ? '#ffffff' : '#16352b' }};
            border: 1px solid {{ $isAdmin ? '#18543f' : '#e2d3ad' }};
            box-shadow: 0 4px 14px rgba(15, 52, 40, 0.08);
        "
    >
        <div
            style="
                display: flex;
                align-items: center;
                gap: 7px;
                margin-bottom: 6px;
                font-size: 12px;
                font-weight: 800;
                color: {{ $isAdmin ? '#e4bd52' : '#0d3b2b' }};
            "
        >
            <span>{{ $isAdmin ? '🛡️' : '👤' }}</span>
            <span>{{ $senderName }}</span>
        </div>

        <div
            style="
                font-size: 14px;
                line-height: 1.9;
                overflow-wrap: anywhere;
                white-space: normal;
            "
        >
            {!! nl2br(e($message->message)) !!}
        </div>

        @if ($attachmentUrl)
            <div style="margin-top: 10px;">
                @if ($isImage)
                    <a
                        href="{{ $attachmentUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        style="display: block;"
                    >
                        <img
                            src="{{ $attachmentUrl }}"
                            alt="مرفق الرسالة"
                            style="
                                display: block;
                                width: 100%;
                                max-width: 360px;
                                max-height: 280px;
                                object-fit: cover;
                                border-radius: 12px;
                                border: 1px solid rgba(255, 255, 255, 0.22);
                            "
                        >
                    </a>
                @else
                    <a
                        href="{{ $attachmentUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        style="
                            display: inline-flex;
                            align-items: center;
                            gap: 8px;
                            padding: 9px 11px;
                            border-radius: 10px;
                            background: rgba(255, 255, 255, 0.14);
                            color: inherit;
                            font-size: 13px;
                            font-weight: 700;
                            text-decoration: none;
                        "
                    >
                        <span>📎</span>
                        <span>فتح المرفق</span>
                    </a>
                @endif
            </div>
        @endif

        <div
            style="
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 5px;
                margin-top: 7px;
                font-size: 10px;
                opacity: 0.72;
            "
        >
            <span>
                {{ $message->created_at?->format('d/m/Y · h:i A') }}
            </span>

            @if ($isAdmin)
                <span title="{{ $message->is_read ? 'قرأها العميل' : 'لم يقرأها العميل بعد' }}">
                    {{ $message->is_read ? '✓✓' : '✓' }}
                </span>
            @endif
        </div>
    </div>
</div>