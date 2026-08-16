<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FavoriteController extends Controller
{
    /**
     * عرض المنتجات المفضلة للعميل.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(
            max(
                $request->integer('per_page', 15),
                1
            ),
            50
        );

        $favorites = Favorite::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->whereHas(
                'product',
                fn ($productQuery) =>
                    $productQuery->where(
                        'is_active',
                        true
                    )
            )
            ->with([
                'product' =>
                    fn ($productQuery) =>
                        $productQuery
                            ->with('productCategory')
                            ->withCount('approvedReviews')
                            ->withAvg(
                                'approvedReviews',
                                'rating'
                            ),
            ])
            ->latest('id')
            ->paginate($perPage);

        $favoriteItems = $favorites
            ->getCollection()
            ->map(
                fn (Favorite $favorite): array =>
                    $this->favoriteData(
                        $favorite,
                        $request
                    )
            )
            ->values();

        return response()->json([
            'success' => true,

            'message' =>
                'تم جلب المنتجات المفضلة.',

            'data' => [
                'favorites' =>
                    $favoriteItems,

                'pagination' => [
                    'current_page' =>
                        $favorites->currentPage(),

                    'last_page' =>
                        $favorites->lastPage(),

                    'per_page' =>
                        $favorites->perPage(),

                    'total' =>
                        $favorites->total(),
                ],
            ],
        ]);
    }

    /**
     * إضافة منتج إلى المفضلة.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],
        ], [
            'product_id.required' =>
                'المنتج مطلوب.',

            'product_id.exists' =>
                'المنتج المحدد غير موجود.',
        ]);

        $product = Product::query()
            ->whereKey($validated['product_id'])
            ->where('is_active', true)
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,

                'message' =>
                    'لا يمكن إضافة منتج غير متاح للمفضلة.',
            ], 422);
        }

        $favorite = Favorite::query()->firstOrCreate([
            'user_id' =>
                $request->user()->id,

            'product_id' =>
                $product->id,
        ]);

        $wasCreated = $favorite->wasRecentlyCreated;

        $favorite->load([
            'product' =>
                fn ($productQuery) =>
                    $productQuery
                        ->with('productCategory')
                        ->withCount('approvedReviews')
                        ->withAvg(
                            'approvedReviews',
                            'rating'
                        ),
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                $wasCreated
                    ? 'تم إضافة المنتج إلى المفضلة.'
                    : 'المنتج موجود في المفضلة مسبقاً.',

            'data' => [
                'favorite' =>
                    $this->favoriteData(
                        $favorite,
                        $request
                    ),
            ],
        ], $wasCreated ? 201 : 200);
    }

    /**
     * التحقق هل المنتج موجود في المفضلة.
     */
    public function check(
        Request $request,
        Product $product
    ): JsonResponse {
        $favorite = Favorite::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->where(
                'product_id',
                $product->id
            )
            ->first();

        return response()->json([
            'success' => true,

            'message' =>
                'تم التحقق من حالة المفضلة.',

            'data' => [
                'product_id' =>
                    $product->id,

                'is_favorite' =>
                    $favorite !== null,

                'favorite_id' =>
                    $favorite?->id,
            ],
        ]);
    }

    /**
     * حذف منتج من المفضلة.
     */
    public function destroy(
        Request $request,
        Product $product
    ): JsonResponse {
        $deleted = Favorite::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->where(
                'product_id',
                $product->id
            )
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'success' => false,

                'message' =>
                    'المنتج غير موجود في المفضلة.',
            ], 404);
        }

        return response()->json([
            'success' => true,

            'message' =>
                'تم حذف المنتج من المفضلة.',
        ]);
    }

    /**
     * تفريغ قائمة المفضلة.
     */
    public function clear(Request $request): JsonResponse
    {
        $deletedCount = Favorite::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->delete();

        return response()->json([
            'success' => true,

            'message' =>
                'تم تفريغ قائمة المفضلة.',

            'data' => [
                'deleted_items_count' =>
                    $deletedCount,
            ],
        ]);
    }

    /**
     * تجهيز بيانات المنتج المفضل.
     */
    private function favoriteData(
        Favorite $favorite,
        Request $request
    ): array {
        $product = $favorite->product;

        return [
            'id' =>
                $favorite->id,

            'product' => [
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
                                $product
                                    ->productCategory
                                    ->id,

                            'name' =>
                                $product
                                    ->productCategory
                                    ->name,

                            'slug' =>
                                $product
                                    ->productCategory
                                    ->slug,
                        ]
                        : null,

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
            ],

            'created_at' =>
                $favorite->created_at
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