<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * عرض جميع التصنيفات النشطة.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:50',
            ],
        ], [
            'search.max' =>
                'عبارة البحث طويلة جداً.',

            'per_page.max' =>
                'لا يمكن عرض أكثر من 50 تصنيفاً في الصفحة.',
        ]);

        $query = Category::query()
            ->where('is_active', true)
            ->withCount([
                'products as active_products_count' =>
                    fn ($productQuery) =>
                        $productQuery->where(
                            'is_active',
                            true
                        ),
            ])
            ->orderBy('sort_order')
            ->orderBy('name');

        if (filled($validated['search'] ?? null)) {
            $search = trim($validated['search']);

            $query->where(
                function ($query) use ($search): void {
                    $query
                        ->where(
                            'name',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'description',
                            'like',
                            '%' . $search . '%'
                        );
                }
            );
        }

        $categories = $query->paginate(
            $validated['per_page'] ?? 20
        );

        $categoryItems = $categories
            ->getCollection()
            ->map(
                fn (Category $category): array =>
                    $this->formatCategory(
                        $category,
                        $request
                    )
            )
            ->values();

        return response()->json([
            'success' => true,

            'message' =>
                'تم جلب التصنيفات بنجاح.',

            'data' => [
                'categories' =>
                    $categoryItems,

                'pagination' => [
                    'current_page' =>
                        $categories->currentPage(),

                    'last_page' =>
                        $categories->lastPage(),

                    'per_page' =>
                        $categories->perPage(),

                    'total' =>
                        $categories->total(),
                ],
            ],
        ]);
    }

    /**
     * عرض تصنيف نشط واحد.
     */
    public function show(
        Request $request,
        Category $category
    ): JsonResponse {
        if (! $category->is_active) {
            return response()->json([
                'success' => false,

                'message' =>
                    'التصنيف غير موجود أو غير متاح.',
            ], 404);
        }

        $category->loadCount([
            'products as active_products_count' =>
                fn ($productQuery) =>
                    $productQuery->where(
                        'is_active',
                        true
                    ),
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'تم جلب بيانات التصنيف.',

            'data' => [
                'category' =>
                    $this->formatCategory(
                        $category,
                        $request
                    ),

                'products_endpoint' =>
                    url(
                        '/api/products?category_id='
                        . $category->id
                    ),
            ],
        ]);
    }

    /**
     * تجهيز بيانات التصنيف للـAPI.
     */
    private function formatCategory(
        Category $category,
        Request $request
    ): array {
        return [
            'id' =>
                $category->id,

            'name' =>
                $category->name,

            'slug' =>
                $category->slug,

            'description' =>
                $category->description,

            'image_url' =>
                $this->imageUrl(
                    $category->image,
                    $request
                ),

            'sort_order' =>
                $category->sort_order,

            'products_count' =>
                (int) (
                    $category->active_products_count
                    ?? 0
                ),

            'created_at' =>
                $category->created_at
                    ?->toIso8601String(),

            'updated_at' =>
                $category->updated_at
                    ?->toIso8601String(),
        ];
    }

    /**
     * إنشاء رابط كامل لصورة التصنيف.
     */
    private function imageUrl(
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