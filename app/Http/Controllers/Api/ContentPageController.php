<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentPageController extends Controller
{
    /**
     * عرض قائمة الصفحات المنشورة.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        $pages = ContentPage::query()
            ->active()
            ->ordered()
            ->when(
                filled($validated['search'] ?? null),
                function ($query) use ($validated): void {
                    $search = trim(
                        $validated['search']
                    );

                    $query->where(
                        function ($query) use ($search): void {
                            $query
                                ->where(
                                    'title',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'excerpt',
                                    'like',
                                    '%' . $search . '%'
                                );
                        }
                    );
                }
            )
            ->get()
            ->map(
                fn (ContentPage $page): array => [
                    'id' =>
                        $page->id,

                    'title' =>
                        $page->title,

                    'slug' =>
                        $page->slug,

                    'excerpt' =>
                        $page->excerpt,

                    'sort_order' =>
                        $page->sort_order,

                    'updated_at' =>
                        $page->updated_at
                            ?->toIso8601String(),
                ]
            )
            ->values();

        return response()->json([
            'success' => true,

            'message' =>
                'تم تحميل الصفحات بنجاح.',

            'data' => [
                'pages' =>
                    $pages,

                'count' =>
                    $pages->count(),
            ],
        ]);
    }

    /**
     * عرض صفحة واحدة بواسطة slug.
     */
    public function show(
        ContentPage $contentPage
    ): JsonResponse {
        if (! $contentPage->is_active) {
            return response()->json([
                'success' => false,

                'message' =>
                    'الصفحة غير موجودة.',
            ], 404);
        }

        return response()->json([
            'success' => true,

            'message' =>
                'تم تحميل الصفحة بنجاح.',

            'data' => [
                'page' => [
                    'id' =>
                        $contentPage->id,

                    'title' =>
                        $contentPage->title,

                    'slug' =>
                        $contentPage->slug,

                    'excerpt' =>
                        $contentPage->excerpt,

                    'content' =>
                        $contentPage->content,

                    'meta_title' =>
                        $contentPage->meta_title
                        ?? $contentPage->title,

                    'meta_description' =>
                        $contentPage->meta_description
                        ?? $contentPage->excerpt,

                    'created_at' =>
                        $contentPage->created_at
                            ?->toIso8601String(),

                    'updated_at' =>
                        $contentPage->updated_at
                            ?->toIso8601String(),
                ],
            ],
        ]);
    }
}