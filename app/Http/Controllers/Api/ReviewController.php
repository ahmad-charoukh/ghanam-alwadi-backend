<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReviewController extends Controller
{
    /**
     * عرض التقييمات المنشورة الخاصة بمنتج.
     */
    public function index(
        Request $request,
        Product $product
    ): JsonResponse {
        if (! $product->is_active) {
            return response()->json([
                'success' => false,

                'message' =>
                    'المنتج غير متوفر حالياً.',
            ], 404);
        }

        $perPage = min(
            max(
                $request->integer('per_page', 10),
                1
            ),
            50
        );

        $reviews = $product
            ->approvedReviews()
            ->with('user:id,name')
            ->latest('id')
            ->paginate($perPage);

        $reviewItems = $reviews
            ->getCollection()
            ->map(
                fn (Review $review): array => [
                    'id' =>
                        $review->id,

                    'user_name' =>
                        $review->user?->name
                        ?? 'عميل غنم الوادي',

                    'rating' =>
                        $review->rating,

                    'title' =>
                        $review->title,

                    'comment' =>
                        $review->comment,

                    'image_urls' =>
                        $this->reviewImageUrls(
                            $request,
                            $review
                        ),

                    'admin_reply' =>
                        $review->admin_reply,

                    'created_at' =>
                        $review->created_at
                            ?->toIso8601String(),
                ]
            )
            ->values();

        return response()->json([
            'success' => true,

            'message' =>
                'تم تحميل تقييمات المنتج بنجاح.',

            'data' => [
                'product' => [
                    'id' =>
                        $product->id,

                    'name' =>
                        $product->name,

                    'average_rating' =>
                        round(
                            (float) $product
                                ->approvedReviews()
                                ->avg('rating'),
                            1
                        ),

                    'reviews_count' =>
                        $product
                            ->approvedReviews()
                            ->count(),
                ],

                'reviews' =>
                    $reviewItems,

                'pagination' => [
                    'current_page' =>
                        $reviews->currentPage(),

                    'last_page' =>
                        $reviews->lastPage(),

                    'per_page' =>
                        $reviews->perPage(),

                    'total' =>
                        $reviews->total(),
                ],
            ],
        ]);
    }

    /**
     * عرض تقييمات العميل المسجل.
     */
    public function mine(Request $request): JsonResponse
    {
        $perPage = min(
            max(
                $request->integer('per_page', 15),
                1
            ),
            50
        );

        $reviews = Review::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->with([
                'product:id,name,image',
                'order:id,order_number',
            ])
            ->latest('id')
            ->paginate($perPage);

        $reviewItems = $reviews
            ->getCollection()
            ->map(
                fn (Review $review): array =>
                    $this->customerReviewData(
                        $request,
                        $review
                    )
            )
            ->values();

        return response()->json([
            'success' => true,

            'message' =>
                'تم جلب تقييماتك.',

            'data' => [
                'reviews' =>
                    $reviewItems,

                'pagination' => [
                    'current_page' =>
                        $reviews->currentPage(),

                    'last_page' =>
                        $reviews->lastPage(),

                    'per_page' =>
                        $reviews->perPage(),

                    'total' =>
                        $reviews->total(),
                ],
            ],
        ]);
    }

    /**
     * إرسال تقييم جديد من العميل المسجل.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'order_id' => [
                'required',
                'integer',
                'exists:orders,id',
            ],

            'rating' => [
                'required',
                'integer',
                'between:1,5',
            ],

            'title' => [
                'nullable',
                'string',
                'max:150',
            ],

            'comment' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'images' => [
                'nullable',
                'array',
                'max:4',
            ],

            'images.*' => [
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ], [
            'product_id.required' =>
                'المنتج مطلوب.',

            'product_id.exists' =>
                'المنتج المحدد غير موجود.',

            'order_id.required' =>
                'الطلب المرتبط بالتقييم مطلوب.',

            'order_id.exists' =>
                'الطلب المحدد غير موجود.',

            'rating.required' =>
                'عدد النجوم مطلوب.',

            'rating.between' =>
                'يجب أن يكون التقييم بين نجمة و5 نجوم.',

            'title.max' =>
                'عنوان التقييم طويل جداً.',

            'comment.max' =>
                'تعليق التقييم طويل جداً.',

            'images.array' =>
                'صيغة صور التقييم غير صحيحة.',

            'images.max' =>
                'يمكنك إضافة 4 صور كحد أقصى.',

            'images.*.image' =>
                'يجب أن يكون كل مرفق صورة صالحة.',

            'images.*.mimes' =>
                'الصور المسموحة هي JPG وPNG وWEBP فقط.',

            'images.*.max' =>
                'حجم الصورة الواحدة يجب ألا يتجاوز 5 ميغابايت.',
        ]);

        $product = Product::query()
            ->whereKey($validated['product_id'])
            ->where('is_active', true)
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,

                'message' =>
                    'لا يمكن تقييم منتج غير متوفر.',
            ], 422);
        }

        $storedImages = $this->storeReviewImages($request);

        try {
            $review = DB::transaction(
                function () use (
                    $request,
                    $validated,
                    $product,
                    $storedImages
                ): Review {
                $order = Order::query()
                    ->whereKey($validated['order_id'])
                    ->where(
                        'user_id',
                        $request->user()->id
                    )
                    ->where(
                        'status',
                        Order::STATUS_DELIVERED
                    )
                    ->whereHas(
                        'items',
                        fn ($itemQuery) =>
                            $itemQuery->where(
                                'product_id',
                                $product->id
                            )
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $order) {
                    throw ValidationException::withMessages([
                        'order_id' => [
                            'يمكن تقييم المنتج بعد استلام طلب يحتوي عليه فقط.',
                        ],
                    ]);
                }

                $alreadyReviewed = Review::query()
                    ->where(
                        'user_id',
                        $request->user()->id
                    )
                    ->where(
                        'product_id',
                        $product->id
                    )
                    ->where(
                        'order_id',
                        $order->id
                    )
                    ->exists();

                if ($alreadyReviewed) {
                    throw ValidationException::withMessages([
                        'product_id' => [
                            'سبق أن قيّمت هذا المنتج ضمن الطلب.',
                        ],
                    ]);
                }

                return Review::query()->create([
                    'user_id' =>
                        $request->user()->id,

                    'product_id' =>
                        $product->id,

                    'order_id' =>
                        $order->id,

                    'rating' =>
                        $validated['rating'],

                    'title' =>
                        $validated['title'] ?? null,

                    'comment' =>
                        $validated['comment'] ?? null,

                    'images' =>
                        $storedImages === []
                            ? null
                            : $storedImages,

                    'is_approved' =>
                        true,

                    'admin_reply' =>
                        null,

                    'approved_at' =>
                        now(),
                ]);
                }
            );
        } catch (Throwable $exception) {
            $this->deleteReviewImages($storedImages);

            throw $exception;
        }

        $review->load([
            'product:id,name,image',
            'order:id,order_number',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'تم نشر تقييمك بنجاح.',

            'data' => [
                'review' =>
                    $this->customerReviewData(
                        $request,
                        $review
                    ),
            ],
        ], 201);
    }

    /**
     * تعديل تقييم يخص العميل المسجل.
     */
    public function update(
        Request $request,
        Review $review
    ): JsonResponse {
        if (
            $review->user_id
            !== $request->user()->id
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'التقييم غير موجود.',
            ], 404);
        }

        $validated = $request->validate([
            'rating' => [
                'required',
                'integer',
                'between:1,5',
            ],

            'title' => [
                'nullable',
                'string',
                'max:150',
            ],

            'comment' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'images' => [
                'nullable',
                'array',
                'max:4',
            ],

            'images.*' => [
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'remove_images' => [
                'nullable',
                'array',
            ],

            'remove_images.*' => [
                'string',
                'max:500',
            ],
        ], [
            'rating.required' =>
                'عدد النجوم مطلوب.',

            'rating.between' =>
                'يجب أن يكون التقييم بين نجمة و5 نجوم.',

            'title.max' =>
                'عنوان التقييم طويل جداً.',

            'comment.max' =>
                'تعليق التقييم طويل جداً.',

            'images.array' =>
                'صيغة صور التقييم غير صحيحة.',

            'images.max' =>
                'يمكنك إضافة 4 صور كحد أقصى.',

            'images.*.image' =>
                'يجب أن يكون كل مرفق صورة صالحة.',

            'images.*.mimes' =>
                'الصور المسموحة هي JPG وPNG وWEBP فقط.',

            'images.*.max' =>
                'حجم الصورة الواحدة يجب ألا يتجاوز 5 ميغابايت.',
        ]);

        $currentImages = collect($review->images ?? [])
            ->filter(fn ($path): bool => is_string($path))
            ->values();

        $requestedRemovals = collect(
            $validated['remove_images'] ?? []
        )
            ->filter(
                fn ($path): bool =>
                    is_string($path)
                    && $currentImages->contains($path)
            )
            ->values();

        $remainingImages = $currentImages
            ->reject(
                fn (string $path): bool =>
                    $requestedRemovals->contains($path)
            )
            ->values();

        $newImages = $this->storeReviewImages($request);
        $allImages = $remainingImages
            ->concat($newImages)
            ->values();

        if ($allImages->count() > 4) {
            $this->deleteReviewImages($newImages);

            throw ValidationException::withMessages([
                'images' => [
                    'يمكنك الاحتفاظ بأربع صور كحد أقصى.',
                ],
            ]);
        }

        try {
            $review->update([
                'rating' =>
                    $validated['rating'],

                'title' =>
                    $validated['title'] ?? null,

                'comment' =>
                    $validated['comment'] ?? null,

                'images' =>
                    $allImages->isEmpty()
                        ? null
                        : $allImages->all(),

                'is_approved' =>
                    true,

                'admin_reply' =>
                    null,

                'approved_at' =>
                    now(),
            ]);
        } catch (Throwable $exception) {
            $this->deleteReviewImages($newImages);

            throw $exception;
        }

        $this->deleteReviewImages(
            $requestedRemovals->all()
        );

        $review->load([
            'product:id,name,image',
            'order:id,order_number',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'تم تعديل تقييمك ونشره بنجاح.',

            'data' => [
                'review' =>
                    $this->customerReviewData(
                        $request,
                        $review->fresh([
                            'product:id,name,image',
                            'order:id,order_number',
                        ])
                    ),
            ],
        ]);
    }

    /**
     * حذف تقييم يخص العميل المسجل.
     */
    public function destroy(
        Request $request,
        Review $review
    ): JsonResponse {
        if (
            $review->user_id
            !== $request->user()->id
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'التقييم غير موجود.',
            ], 404);
        }

        $images = $review->images ?? [];

        $review->delete();

        $this->deleteReviewImages($images);

        return response()->json([
            'success' => true,

            'message' =>
                'تم حذف التقييم بنجاح.',
        ]);
    }

    /**
     * تجهيز بيانات تقييم العميل.
     */
    private function customerReviewData(
        Request $request,
        Review $review
    ): array {
        return [
            'id' =>
                $review->id,

            'product' =>
                $review->product
                    ? [
                        'id' =>
                            $review->product->id,

                        'name' =>
                            $review->product->name,

                        'image' =>
                            $review->product->image,
                    ]
                    : null,

            'order' =>
                $review->order
                    ? [
                        'id' =>
                            $review->order->id,

                        'order_number' =>
                            $review->order->order_number,
                    ]
                    : null,

            'rating' =>
                $review->rating,

            'title' =>
                $review->title,

            'comment' =>
                $review->comment,

            'images' =>
                $review->images ?? [],

            'image_urls' =>
                $this->reviewImageUrls(
                    $request,
                    $review
                ),

            'is_approved' =>
                $review->is_approved,

            'admin_reply' =>
                $review->admin_reply,

            'approved_at' =>
                $review->approved_at
                    ?->toIso8601String(),

            'created_at' =>
                $review->created_at
                    ?->toIso8601String(),

            'updated_at' =>
                $review->updated_at
                    ?->toIso8601String(),
        ];
    }

    /**
     * حفظ صور التقييم في القرص العام.
     *
     * @return array<int, string>
     */
    private function storeReviewImages(Request $request): array
    {
        $files = $request->file('images', []);

        if (! is_array($files)) {
            $files = [$files];
        }

        return collect($files)
            ->filter()
            ->map(
                fn ($file): string =>
                    $file->store(
                        'review-images',
                        'public'
                    )
            )
            ->values()
            ->all();
    }

    /**
     * حذف صور التقييم من القرص العام.
     *
     * @param  iterable<int, mixed>  $paths
     */
    private function deleteReviewImages(iterable $paths): void
    {
        $validPaths = collect($paths)
            ->filter(
                fn ($path): bool =>
                    is_string($path)
                    && $path !== ''
                    && ! filter_var(
                        $path,
                        FILTER_VALIDATE_URL
                    )
            )
            ->values()
            ->all();

        if ($validPaths !== []) {
            Storage::disk('public')->delete($validPaths);
        }
    }

    /**
     * تجهيز روابط صور التقييم كاملة للتطبيق.
     *
     * @return array<int, string>
     */
    private function reviewImageUrls(
        Request $request,
        Review $review
    ): array {
        return collect($review->images ?? [])
            ->filter(
                fn ($path): bool =>
                    is_string($path)
                    && $path !== ''
            )
            ->map(function (string $path) use ($request): string {
                if (filter_var($path, FILTER_VALIDATE_URL)) {
                    return $path;
                }

                $storageUrl = '/storage/'.$path;

                if (filter_var($storageUrl, FILTER_VALIDATE_URL)) {
                    return $storageUrl;
                }

                return $request->getSchemeAndHttpHost()
                    .'/'.ltrim($storageUrl, '/');
            })
            ->values()
            ->all();
    }
}