<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CartController extends Controller
{
    /**
     * عرض سلة العميل.
     */
    public function index(Request $request): JsonResponse
    {
        $cartItems = CartItem::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->with([
                'product.productCategory',
            ])
            ->oldest('id')
            ->get();

        $subtotal = $cartItems->sum(
            fn (CartItem $item): float =>
                round(
                    (float) $item->product->price
                    * $item->quantity,
                    2
                )
        );

        $totalQuantity = $cartItems->sum('quantity');

        $checkoutReady = $cartItems->isNotEmpty()
            && $cartItems->every(
                fn (CartItem $item): bool =>
                    $item->product->is_active
                    && $item->product->stock >= $item->quantity
            );

        return response()->json([
            'success' => true,

            'message' =>
                'تم جلب سلة المشتريات.',

            'data' => [
                'items' =>
                    $cartItems
                        ->map(
                            fn (CartItem $item): array =>
                                $this->cartItemData(
                                    $item,
                                    $request
                                )
                        )
                        ->values(),

                'summary' => [
                    'items_count' =>
                        $cartItems->count(),

                    'total_quantity' =>
                        (int) $totalQuantity,

                    'subtotal' =>
                        round((float) $subtotal, 2),

                    'currency' =>
                        'SAR',

                    'checkout_ready' =>
                        $checkoutReady,
                ],
            ],
        ]);
    }

    /**
     * إضافة منتج إلى السلة.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
        ], [
            'product_id.required' =>
                'المنتج مطلوب.',

            'product_id.exists' =>
                'المنتج المحدد غير موجود.',

            'quantity.required' =>
                'الكمية مطلوبة.',

            'quantity.integer' =>
                'الكمية يجب أن تكون رقماً صحيحاً.',

            'quantity.min' =>
                'يجب ألا تقل الكمية عن واحد.',

            'quantity.max' =>
                'الكمية المطلوبة كبيرة جداً.',
        ]);

        $product = Product::query()
            ->findOrFail($validated['product_id']);

        if (! $product->is_active) {
            return response()->json([
                'success' => false,

                'message' =>
                    'هذا المنتج غير متاح حالياً.',
            ], 422);
        }

        $cartItem = CartItem::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->where(
                'product_id',
                $product->id
            )
            ->first();

        $newQuantity =
            ($cartItem?->quantity ?? 0)
            + $validated['quantity'];

        if ($newQuantity > $product->stock) {
            return response()->json([
                'success' => false,

                'message' =>
                    'الكمية المطلوبة غير متوفرة في المخزون.',

                'data' => [
                    'available_stock' =>
                        $product->stock,

                    'requested_quantity' =>
                        $newQuantity,
                ],
            ], 422);
        }

        if ($cartItem) {
            $cartItem->update([
                'quantity' =>
                    $newQuantity,
            ]);

            $message =
                'تم تحديث كمية المنتج في السلة.';
        } else {
            $cartItem = CartItem::query()->create([
                'user_id' =>
                    $request->user()->id,

                'product_id' =>
                    $product->id,

                'quantity' =>
                    $validated['quantity'],
            ]);

            $message =
                'تم إضافة المنتج إلى السلة.';
        }

        $cartItem->load([
            'product.productCategory',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                $message,

            'data' => [
                'item' =>
                    $this->cartItemData(
                        $cartItem,
                        $request
                    ),
            ],
        ], 201);
    }

    /**
     * تعديل كمية عنصر داخل السلة.
     */
    public function update(
        Request $request,
        CartItem $cartItem
    ): JsonResponse {
        if (
            $cartItem->user_id
            !== $request->user()->id
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'عنصر السلة غير موجود.',
            ], 404);
        }

        $validated = $request->validate([
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
        ], [
            'quantity.required' =>
                'الكمية مطلوبة.',

            'quantity.integer' =>
                'الكمية يجب أن تكون رقماً صحيحاً.',

            'quantity.min' =>
                'يجب ألا تقل الكمية عن واحد.',

            'quantity.max' =>
                'الكمية المطلوبة كبيرة جداً.',
        ]);

        $product = $cartItem->product;

        if (! $product->is_active) {
            return response()->json([
                'success' => false,

                'message' =>
                    'هذا المنتج غير متاح حالياً.',
            ], 422);
        }

        if ($validated['quantity'] > $product->stock) {
            return response()->json([
                'success' => false,

                'message' =>
                    'الكمية المطلوبة غير متوفرة في المخزون.',

                'data' => [
                    'available_stock' =>
                        $product->stock,
                ],
            ], 422);
        }

        $cartItem->update([
            'quantity' =>
                $validated['quantity'],
        ]);

        $cartItem->load([
            'product.productCategory',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'تم تعديل كمية المنتج.',

            'data' => [
                'item' =>
                    $this->cartItemData(
                        $cartItem,
                        $request
                    ),
            ],
        ]);
    }

    /**
     * حذف عنصر من السلة.
     */
    public function destroy(
        Request $request,
        CartItem $cartItem
    ): JsonResponse {
        if (
            $cartItem->user_id
            !== $request->user()->id
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'عنصر السلة غير موجود.',
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,

            'message' =>
                'تم حذف المنتج من السلة.',
        ]);
    }

    /**
     * تفريغ سلة العميل بالكامل.
     */
    public function clear(Request $request): JsonResponse
    {
        $deletedCount = CartItem::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->delete();

        return response()->json([
            'success' => true,

            'message' =>
                'تم تفريغ سلة المشتريات.',

            'data' => [
                'deleted_items_count' =>
                    $deletedCount,
            ],
        ]);
    }

    /**
     * تجهيز بيانات عنصر السلة.
     */
    private function cartItemData(
        CartItem $cartItem,
        Request $request
    ): array {
        $product = $cartItem->product;

        $subtotal = round(
            (float) $product->price
            * $cartItem->quantity,
            2
        );

        return [
            'id' =>
                $cartItem->id,

            'quantity' =>
                $cartItem->quantity,

            'unit_price' =>
                (float) $product->price,

            'subtotal' =>
                $subtotal,

            'currency' =>
                'SAR',

            'is_available' =>
                $product->is_active
                && $product->stock >= $cartItem->quantity,

            'product' => [
                'id' =>
                    $product->id,

                'name' =>
                    $product->name,

                'price' =>
                    (float) $product->price,

                'stock' =>
                    $product->stock,

                'is_active' =>
                    $product->is_active,

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
                        ]
                        : null,
            ],

            'created_at' =>
                $cartItem->created_at
                    ?->toIso8601String(),

            'updated_at' =>
                $cartItem->updated_at
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