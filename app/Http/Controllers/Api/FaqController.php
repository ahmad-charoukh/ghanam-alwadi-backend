<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * عرض الأسئلة الشائعة المنشورة.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => [
                'nullable',
                'string',
                'max:100',
            ],

            'search' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        $faqs = Faq::query()
            ->active()
            ->ordered()
            ->when(
                filled($validated['category'] ?? null),
                fn ($query) => $query->where(
                    'category',
                    $validated['category']
                )
            )
            ->when(
                filled($validated['search'] ?? null),
                function ($query) use ($validated): void {
                    $search = trim($validated['search']);

                    $query->where(
                        function ($query) use ($search): void {
                            $query
                                ->where(
                                    'question',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'answer',
                                    'like',
                                    '%' . $search . '%'
                                );
                        }
                    );
                }
            )
            ->get()
            ->map(
                fn (Faq $faq): array =>
                    $this->faqData($faq)
            )
            ->values();

        return response()->json([
            'success' => true,

            'message' =>
                'تم تحميل الأسئلة الشائعة بنجاح.',

            'data' => [
                'faqs' =>
                    $faqs,

                'categories' =>
                    $faqs
                        ->pluck('category')
                        ->filter()
                        ->unique()
                        ->values(),

                'count' =>
                    $faqs->count(),
            ],
        ]);
    }

    /**
     * عرض سؤال شائع واحد.
     */
    public function show(Faq $faq): JsonResponse
    {
        if (! $faq->is_active) {
            return response()->json([
                'success' => false,

                'message' =>
                    'السؤال غير موجود.',
            ], 404);
        }

        return response()->json([
            'success' => true,

            'message' =>
                'تم تحميل السؤال بنجاح.',

            'data' => [
                'faq' =>
                    $this->faqData($faq),
            ],
        ]);
    }

    /**
     * تنسيق بيانات السؤال.
     */
    private function faqData(Faq $faq): array
    {
        return [
            'id' =>
                $faq->id,

            'question' =>
                $faq->question,

            'answer' =>
                $faq->answer,

            'category' =>
                $faq->category,

            'sort_order' =>
                $faq->sort_order,

            'created_at' =>
                $faq->created_at
                    ?->toIso8601String(),

            'updated_at' =>
                $faq->updated_at
                    ?->toIso8601String(),
        ];
    }
}