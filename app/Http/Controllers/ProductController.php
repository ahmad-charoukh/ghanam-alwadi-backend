<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * عرض المنتجات النشطة مع البحث والتصفية والترتيب.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'category_slug' => [
                'nullable',
                'string',
                'max:150',
            ],

            'featured' => [
                'nullable',
                'boolean',
            ],

            'in_stock' => [
                'nullable',
                'boolean',
            ],

            'min_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'max_price' => [
                'nullable',
                'numeric',
                'min:0',
                'gte:min_price',
            ],

            'sort' => [
                'nullable',
                Rule::in([
                    'latest',
                    'oldest',
                    'price_asc',
                    'price_desc',
                    'name_asc',
                    'name_desc',
                ]),
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

            'category_id.exists' =>
                'التصنيف المحدد غير موجود.',

            'max_price.gte' =>
                'أعلى سعر يجب أن يكون أكبر من أو يساوي أقل سعر.',

            'sort.in' =>
                'طريقة ترتيب المنتجات غير صحيحة.',

            'per_page.max' =>
                'لا يمكن عرض أكثر من 50 منتجاً في الصفحة.',
        ]);

        $query = Product::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query
                    ->whereNull('category_id')
                    ->orWhereHas(
                        'productCategory',
                        fn ($categoryQuery) =>
                            $categoryQuery->where(
                                'is_active',
                                true
                            )
                    );
            })
            ->with([
                'productCategory',
            ])
            ->withCount('approvedReviews')
            ->withAvg(
                'approvedReviews',
                'rating'
            );

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
                        )
                        ->orWhere(
                            'category',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhereHas(
                            'productCategory',
                            fn ($categoryQuery) =>
                                $categoryQuery->where(
                                    'name',
                                    'like',
                                    '%' . $search . '%'
                                )
                        );
                }
            );
        }

        if (filled($validated['category_id'] ?? null)) {
            $query->where(
                'category_id',
                $validated['category_id']
            );
        }

        if (filled($validated['category_slug'] ?? null)) {
            $categorySlug = trim(
                $validated['category_slug']
            );

            $query->whereHas(
                'productCategory',
                fn ($categoryQuery) =>
                    $categoryQuery->where(
                        'slug',
                        $categorySlug
                    )
            );
        }

        if (
            array_key_exists('featured', $validated)
            && $validated['featured'] !== null
        ) {
            $query->where(
                'is_featured',
                (bool) $validated['featured']
            );
        }

        if (
            array_key_exists('in_stock', $validated)
            && $validated['in_stock'] !== null
        ) {
            if ((bool) $validated['in_stock']) {
                $query->where('stock', '>', 0);
            } else {
                $query->where('stock', '<=', 0);
            }
        }

        if (isset($validated['min_price'])) {
            $query->where(
                'price',
                '>=',
                $validated['min_price']
            );
        }

        if (isset($validated['max_price'])) {
            $query->where(
                'price',
                '<=',
                $validated['max_price']
            );
        }

        $sort = $validated['sort'] ?? 'latest';

        match ($sort) {
            'oldest' =>
                $query->oldest('id'),

            'price_asc' =>
                $query->orderBy('price'),

            'price_desc' =>
                $query->orderByDesc('price'),

            'name_asc' =>
                $query->orderBy('name'),

            'name_desc' =>
                $query->orderByDesc('name'),

            default =>
                $query->latest('id'),
        };

        $products = $query->paginate(
            $validated['per_page'] ?? 15
        );

        $productItems = $products
            ->getCollection()
            ->map(
                fn (Product $product): array =>
                    $this->formatProduct(
                        $product,
                        $request
                    )
            )
            ->values();

        return response()->json([
            'success' => true,

            'message' =>
                'تم جلب المنتجات بنجاح.',

            'data' => [
                'products' =>
                    $productItems,

                'pagination' => [
                    'current_page' =>
                        $products->currentPage(),

                    'last_page' =>
                        $products->lastPage(),

                    'per_page' =>
                        $products->perPage(),

                    'total' =>
                        $products->total(),
                ],

                'filters' => [
                    'search' =>
                        $validated['search'] ?? null,

                    'category_id' =>
                        $validated['category_id'] ?? null,

                    'category_slug' =>
                        $validated['category_slug'] ?? null,

                    'featured' =>
                        $validated['featured'] ?? null,

                    'in_stock' =>
                        $validated['in_stock'] ?? null,

                    'min_price' =>
                        isset($validated['min_price'])
                            ? (float) $validated['min_price']
                            : null,

                    'max_price' =>
                        isset($validated['max_price'])
                            ? (float) $validated['max_price']
                            : null,

                    'sort' =>
                        $sort,
                ],
            ],
        ]);
    }

    /**
     * عرض منتج نشط واحد.
     */
    public function show(
        Request $request,
        Product $product
    ): JsonResponse {
        $product->load([
            'productCategory',
        ]);

        $product->loadCount('approvedReviews');
        $product->loadAvg(
            'approvedReviews',
            'rating'
        );

        if (! $product->is_active) {
            return response()->json([
                'success' => false,

                'message' =>
                    'المنتج غير موجود أو غير متاح.',
            ], 404);
        }

        if (
            $product->productCategory
            && ! $product->productCategory->is_active
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'المنتج غير موجود أو غير متاح.',
            ], 404);
        }

        return response()->json([
            'success' => true,

            'message' =>
                'تم جلب بيانات المنتج.',

            'data' => [
                'product' =>
                    $this->formatProduct(
                        $product,
                        $request
                    ),
            ],
        ]);
    }

    /**
     * تجهيز بيانات المنتج للـAPI.
     */
    private function formatProduct(
        Product $product,
        Request $request
    ): array {
        return [
            'id' =>
                $product->id,

            'name' =>
                $product->name,

            'description' =>
                $product->description,

            'price' =>
                (float) $product->price,

            'stock' =>
                $product->stock,

            'is_in_stock' =>
                $product->stock > 0,

            'is_featured' =>
                $product->is_featured,

            'image_url' =>
                $this->imageUrl(
                    $product->image,
                    $request
                ),

            'category' =>
                $product->productCategory
                    ? [
                        'id' =>
                            $product->productCategory->id,

                        'name' =>
                            $product->productCategory->name,

                        'slug' =>
                            $product->productCategory->slug,
                    ]
                    : (
                        filled($product->category)
                            ? [
                                'id' => null,
                                'name' => $product->category,
                                'slug' => null,
                            ]
                            : null
                    ),

            'rating' => [
                'average' =>
                    round(
                        (float) (
                            $product
                                ->approved_reviews_avg_rating
                            ?? 0
                        ),
                        1
                    ),

                'count' =>
                    (int) (
                        $product->approved_reviews_count
                        ?? 0
                    ),
            ],

            'created_at' =>
                $product->created_at
                    ?->toIso8601String(),

            'updated_at' =>
                $product->updated_at
                    ?->toIso8601String(),
        ];
    }

    /**
     * إنشاء رابط كامل لصورة المنتج.
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