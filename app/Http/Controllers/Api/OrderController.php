<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\OrderCreatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * عرض طلبات العميل.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(
            max($request->integer('per_page', 15), 1),
            50
        );

        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->withCount('items')
            ->latest('id')
            ->paginate($perPage);

        $items = $orders->getCollection()
            ->map(
                fn (Order $order): array => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'items_count' => $order->items_count,
                    'subtotal' => (float) $order->subtotal,
                    'delivery_fee' =>
                        (float) $order->delivery_fee,
                    'discount' => (float) $order->discount,
                    'tax_amount' =>
                        (float) $order->tax_amount,
                    'total' => (float) $order->total,
                    'currency' => $order->currency,
                    'created_at' => $order->created_at
                        ?->toIso8601String(),
                ]
            )
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب طلباتك بنجاح.',
            'data' => [
                'orders' => $items,
                'pagination' => [
                    'current_page' =>
                        $orders->currentPage(),
                    'last_page' =>
                        $orders->lastPage(),
                    'per_page' =>
                        $orders->perPage(),
                    'total' =>
                        $orders->total(),
                ],
            ],
        ]);
    }

    /**
     * معاينة تكلفة الطلب قبل إنشائه.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coupon_code' => [
                'nullable',
                'string',
                'max:50',
            ],
        ], [
            'coupon_code.max' =>
                'رمز الكوبون طويل جداً.',
        ]);

        /** @var User $user */
        $user = $request->user();

        $settings = Setting::current();

        [$cartProducts, $subtotal] =
            $this->prepareCart(
                $user->id,
                false
            );

        $pricing = $this->calculatePricing(
            user: $user,
            subtotal: $subtotal,
            settings: $settings,
            couponCode:
                $validated['coupon_code'] ?? null,
            lockCoupon: false,
        );

        return response()->json([
            'success' => true,
            'message' => 'تم حساب ملخص الطلب.',
            'data' => [
                'pricing' => [
                    'subtotal' =>
                        $pricing['subtotal'],
                    'discount' =>
                        $pricing['discount'],
                    'delivery_fee' =>
                        $pricing['delivery_fee'],
                    'tax_percentage' =>
                        $pricing['tax_percentage'],
                    'tax_amount' =>
                        $pricing['tax_amount'],
                    'total' =>
                        $pricing['total'],
                    'currency' =>
                        $pricing['currency'],
                ],

                'shipping' => [
                    'is_free' =>
                        $pricing['delivery_fee'] === 0.0,

                    'free_shipping_amount' =>
                        $pricing[
                            'free_shipping_amount'
                        ],

                    'remaining_for_free_shipping' =>
                        $pricing[
                            'remaining_for_free_shipping'
                        ],
                ],

                'coupon' => $pricing['coupon']
                    ? [
                        'code' =>
                            $pricing['coupon']->code,

                        'discount_type' =>
                            $pricing['coupon']
                                ->discount_type,

                        'discount_value' =>
                            (float) $pricing['coupon']
                                ->discount_value,

                        'discount_amount' =>
                            $pricing['discount'],
                    ]
                    : null,

                'items' => collect($cartProducts)
                    ->map(
                        fn (array $item): array => [
                            'product_id' =>
                                $item['product']->id,

                            'product_name' =>
                                $item['product']->name,

                            'unit_price' =>
                                (float) $item['product']
                                    ->price,

                            'quantity' =>
                                $item['cart_item']
                                    ->quantity,

                            'total' =>
                                $item['line_total'],
                        ]
                    )
                    ->values(),
            ],
        ]);
    }

    /**
     * إنشاء طلب جديد من سلة العميل.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'address_id' => [
                'required',
                'integer',
                Rule::exists('addresses', 'id')
                    ->where(
                        fn ($query) => $query->where(
                            'user_id',
                            $user->id
                        )
                    ),
            ],

            'customer_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'payment_method' => [
                'required',
                Rule::in([
                    Order::PAYMENT_METHOD_CASH,
                ]),
            ],

            'coupon_code' => [
                'nullable',
                'string',
                'max:50',
            ],

            'customer_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ], [
            'address_id.required' =>
                'يرجى اختيار عنوان التوصيل.',

            'address_id.integer' =>
                'عنوان التوصيل المحدد غير صحيح.',

            'address_id.exists' =>
                'عنوان التوصيل غير موجود أو لا يخص حسابك.',

            'customer_email.email' =>
                'البريد الإلكتروني غير صحيح.',

            'payment_method.required' =>
                'طريقة الدفع مطلوبة.',

            'payment_method.in' =>
                'طريقة الدفع غير متاحة.',

            'coupon_code.max' =>
                'رمز الكوبون طويل جداً.',

            'customer_notes.max' =>
                'ملاحظات الطلب طويلة جداً.',
        ]);

        $settings = Setting::current();

        $order = DB::transaction(
            function () use (
                $validated,
                $user,
                $settings
            ): Order {
                $deliveryAddress = Address::query()
                    ->whereKey($validated['address_id'])
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if (! $deliveryAddress) {
                    throw ValidationException::withMessages([
                        'address_id' => [
                            'عنوان التوصيل غير موجود أو لا يخص حسابك.',
                        ],
                    ]);
                }

                [$cartProducts, $subtotal] =
                    $this->prepareCart(
                        $user->id,
                        true
                    );

                $pricing = $this->calculatePricing(
                    user: $user,
                    subtotal: $subtotal,
                    settings: $settings,
                    couponCode:
                        $validated['coupon_code'] ?? null,
                    lockCoupon: true,
                );

                /** @var Coupon|null $coupon */
                $coupon = $pricing['coupon'];

                $order = Order::query()->create([
                    'user_id' =>
                        $user->id,

                    'address_id' =>
                        $deliveryAddress->id,

                    'coupon_id' =>
                        $coupon?->id,

                    'coupon_code' =>
                        $coupon?->code,

                    'customer_name' =>
                        $deliveryAddress->recipient_name,

                    'customer_email' =>
                        $validated['customer_email']
                        ?? $user->email,

                    'customer_phone' =>
                        $deliveryAddress->phone,

                    'city' =>
                        $deliveryAddress->city,

                    'address' =>
                        $this->formatAddress(
                            $deliveryAddress
                        ),

                    'subtotal' =>
                        $pricing['subtotal'],

                    'delivery_fee' =>
                        $pricing['delivery_fee'],

                    'discount' =>
                        $pricing['discount'],

                    'tax_percentage' =>
                        $pricing['tax_percentage'],

                    'tax_amount' =>
                        $pricing['tax_amount'],

                    'total' =>
                        $pricing['total'],

                    'currency' =>
                        $pricing['currency'],

                    'payment_method' =>
                        $validated['payment_method'],

                    'payment_status' =>
                        Order::PAYMENT_PENDING,

                    'status' =>
                        Order::STATUS_NEW,

                    'customer_notes' =>
                        $validated['customer_notes']
                        ?? null,

                    'admin_notes' =>
                        null,
                ]);

                foreach ($cartProducts as $item) {
                    /** @var CartItem $cartItem */
                    $cartItem = $item['cart_item'];

                    /** @var Product $product */
                    $product = $item['product'];

                    $order->items()->create([
                        'product_id' =>
                            $product->id,

                        'product_name' =>
                            $product->name,

                        'product_image' =>
                            $product->image,

                        'unit_price' =>
                            $product->price,

                        'quantity' =>
                            $cartItem->quantity,

                        'total' =>
                            $item['line_total'],

                        'notes' =>
                            null,
                    ]);

                    $product->decrement(
                        'stock',
                        $cartItem->quantity
                    );
                }

                if ($coupon) {
                    $coupon->increment('used_count');
                }

                CartItem::query()
                    ->where('user_id', $user->id)
                    ->delete();

                return $order;
            }
        );

        $order->load([
            'items.product',
        ]);

        $user->notify(
            new OrderCreatedNotification($order)
        );

        return response()->json([
            'success' => true,
            'message' =>
                'تم إنشاء طلبك بنجاح.',
            'data' => [
                'order' => $this->orderData(
                    $order,
                    $request
                ),
            ],
        ], 201);
    }

    /**
     * عرض طلب واحد يخص العميل.
     */
    public function show(
        Request $request,
        string $orderNumber
    ): JsonResponse {
        $order = Order::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->where(
                'order_number',
                $orderNumber
            )
            ->with([
                'items.product',
            ])
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' =>
                    'لم يتم العثور على الطلب.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' =>
                'تم جلب تفاصيل الطلب.',
            'data' => [
                'order' => $this->orderData(
                    $order,
                    $request
                ),
            ],
        ]);
    }

    /**
     * إلغاء الطلب وإعادة المنتجات إلى المخزون.
     */
    public function cancel(
        Request $request,
        string $orderNumber
    ): JsonResponse {
        $order = DB::transaction(
            function () use (
                $request,
                $orderNumber
            ): Order {
                $order = Order::query()
                    ->where(
                        'user_id',
                        $request->user()->id
                    )
                    ->where(
                        'order_number',
                        $orderNumber
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $order) {
                    throw ValidationException::withMessages([
                        'order' => [
                            'لم يتم العثور على الطلب.',
                        ],
                    ]);
                }

                if (
                    ! in_array(
                        $order->status,
                        [
                            Order::STATUS_NEW,
                            Order::STATUS_CONFIRMED,
                        ],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'order' => [
                            'لا يمكن إلغاء الطلب بعد بدء تجهيزه.',
                        ],
                    ]);
                }

                $order->load('items');

                foreach ($order->items as $item) {
                    if (! $item->product_id) {
                        continue;
                    }

                    $product = Product::query()
                        ->whereKey($item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($product) {
                        $product->increment(
                            'stock',
                            $item->quantity
                        );
                    }
                }

                if ($order->coupon_id) {
                    $coupon = Coupon::query()
                        ->whereKey($order->coupon_id)
                        ->lockForUpdate()
                        ->first();

                    if (
                        $coupon
                        && $coupon->used_count > 0
                    ) {
                        $coupon->decrement(
                            'used_count'
                        );
                    }
                }

                $order->update([
                    'status' =>
                        Order::STATUS_CANCELLED,

                    'cancelled_at' =>
                        now(),
                ]);

                return $order;
            }
        );

        $order->load([
            'items.product',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'تم إلغاء الطلب وإعادة الكميات إلى المخزون.',
            'data' => [
                'order' => $this->orderData(
                    $order,
                    $request
                ),
            ],
        ]);
    }

    /**
     * جلب منتجات السلة وفحص المخزون.
     *
     * @return array{
     *     0: array<int, array{
     *         cart_item: CartItem,
     *         product: Product,
     *         line_total: float
     *     }>,
     *     1: float
     * }
     */
    private function prepareCart(
        int $userId,
        bool $lock
    ): array {
        $cartQuery = CartItem::query()
            ->where('user_id', $userId)
            ->orderBy('product_id');

        if ($lock) {
            $cartQuery->lockForUpdate();
        }

        $cartItems = $cartQuery->get();

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => [
                    'سلة المشتريات فارغة.',
                ],
            ]);
        }

        $items = [];
        $subtotal = 0.0;

        foreach ($cartItems as $cartItem) {
            $productQuery = Product::query()
                ->whereKey($cartItem->product_id);

            if ($lock) {
                $productQuery->lockForUpdate();
            }

            $product = $productQuery->first();

            if (! $product || ! $product->is_active) {
                throw ValidationException::withMessages([
                    'cart' => [
                        'أحد منتجات السلة لم يعد متاحاً.',
                    ],
                ]);
            }

            if (
                $cartItem->quantity
                > $product->stock
            ) {
                throw ValidationException::withMessages([
                    'cart' => [
                        'الكمية المتوفرة من المنتج «'
                        . $product->name
                        . '» هي '
                        . $product->stock
                        . ' فقط.',
                    ],
                ]);
            }

            $lineTotal = round(
                (float) $product->price
                * $cartItem->quantity,
                2
            );

            $subtotal += $lineTotal;

            $items[] = [
                'cart_item' => $cartItem,
                'product' => $product,
                'line_total' => $lineTotal,
            ];
        }

        return [
            $items,
            round($subtotal, 2),
        ];
    }

    /**
     * حساب الخصم والشحن والضريبة والإجمالي.
     *
     * @return array<string, mixed>
     */
    private function calculatePricing(
        User $user,
        float $subtotal,
        Setting $settings,
        ?string $couponCode,
        bool $lockCoupon
    ): array {
        $coupon = null;
        $discount = 0.0;

        if (filled($couponCode)) {
            $normalizedCode = Str::lower(
                trim($couponCode)
            );

            $couponQuery = Coupon::query()
                ->whereRaw(
                    'LOWER(code) = ?',
                    [$normalizedCode]
                );

            if ($lockCoupon) {
                $couponQuery->lockForUpdate();
            }

            $coupon = $couponQuery->first();

            if (! $coupon) {
                throw ValidationException::withMessages([
                    'coupon_code' => [
                        'رمز الكوبون غير صحيح.',
                    ],
                ]);
            }

            if (
                ! $coupon->canBeUsedBy(
                    $user,
                    $subtotal
                )
            ) {
                throw ValidationException::withMessages([
                    'coupon_code' => [
                        'الكوبون غير صالح أو لا يمكن استخدامه لهذا الطلب.',
                    ],
                ]);
            }

            $discount = $coupon
                ->calculateDiscount($subtotal);
        }

        $shippingCost = round(
            (float) $settings->shipping_cost,
            2
        );

        $freeShippingAmount = round(
            (float) (
                $settings->free_shipping_amount
                ?? 0
            ),
            2
        );

        $deliveryFee =
            $freeShippingAmount > 0
            && $subtotal >= $freeShippingAmount
                ? 0.0
                : $shippingCost;

        $taxPercentage = round(
            (float) $settings->tax_percentage,
            2
        );

        $taxableAmount = max(
            0,
            $subtotal
            - $discount
            + $deliveryFee
        );

        $taxAmount = round(
            $taxableAmount
            * ($taxPercentage / 100),
            2
        );

        $total = round(
            $taxableAmount + $taxAmount,
            2
        );

        $remainingForFreeShipping =
            $freeShippingAmount > 0
                ? round(
                    max(
                        0,
                        $freeShippingAmount
                        - $subtotal
                    ),
                    2
                )
                : 0.0;

        return [
            'subtotal' =>
                round($subtotal, 2),

            'discount' =>
                round($discount, 2),

            'delivery_fee' =>
                round($deliveryFee, 2),

            'tax_percentage' =>
                $taxPercentage,

            'tax_amount' =>
                $taxAmount,

            'total' =>
                $total,

            'currency' =>
                $settings->currency ?: 'SAR',

            'coupon' =>
                $coupon,

            'free_shipping_amount' =>
                $freeShippingAmount,

            'remaining_for_free_shipping' =>
                $remainingForFreeShipping,
        ];
    }

    /**
     * تجهيز عنوان التوصيل كنص.
     */
    private function formatAddress(
        Address $address
    ): string {
        $parts = array_filter([
            $address->country,
            $address->city,
            $address->district,
            $address->street,
            $address->building_number
                ? 'مبنى ' . $address->building_number
                : null,
            $address->apartment_number
                ? 'شقة ' . $address->apartment_number
                : null,
            $address->postal_code
                ? 'الرمز البريدي '
                    . $address->postal_code
                : null,
            $address->additional_details,
        ], fn ($value): bool => filled($value));

        return implode('، ', $parts);
    }

    /**
     * تجهيز بيانات الطلب للـAPI.
     */
    private function orderData(
        Order $order,
        Request $request
    ): array {
        return [
            'id' => $order->id,

            'order_number' =>
                $order->order_number,

            'customer' => [
                'address_id' =>
                    $order->address_id,

                'name' =>
                    $order->customer_name,

                'email' =>
                    $order->customer_email,

                'phone' =>
                    $order->customer_phone,

                'city' =>
                    $order->city,

                'address' =>
                    $order->address,
            ],

            'pricing' => [
                'subtotal' =>
                    (float) $order->subtotal,

                'delivery_fee' =>
                    (float) $order->delivery_fee,

                'discount' =>
                    (float) $order->discount,

                'tax_percentage' =>
                    (float) $order->tax_percentage,

                'tax_amount' =>
                    (float) $order->tax_amount,

                'total' =>
                    (float) $order->total,

                'currency' =>
                    $order->currency,

                'coupon_code' =>
                    $order->coupon_code,
            ],

            'payment' => [
                'method' =>
                    $order->payment_method,

                'status' =>
                    $order->payment_status,
            ],

            'status' =>
                $order->status,

            'customer_notes' =>
                $order->customer_notes,

            'items' => $order->items
                ->map(
                    fn ($item): array => [
                        'id' =>
                            $item->id,

                        'product_id' =>
                            $item->product_id,

                        'product_name' =>
                            $item->product_name,

                        'product_image_url' =>
                            $this->imageUrl(
                                $item->product_image,
                                $request
                            ),

                        'unit_price' =>
                            (float) $item->unit_price,

                        'quantity' =>
                            (int) $item->quantity,

                        'total' =>
                            (float) $item->total,

                        'notes' =>
                            $item->notes,
                    ]
                )
                ->values(),

            'confirmed_at' =>
                $order->confirmed_at
                    ?->toIso8601String(),

            'shipped_at' =>
                $order->shipped_at
                    ?->toIso8601String(),

            'delivered_at' =>
                $order->delivered_at
                    ?->toIso8601String(),

            'cancelled_at' =>
                $order->cancelled_at
                    ?->toIso8601String(),

            'created_at' =>
                $order->created_at
                    ?->toIso8601String(),

            'updated_at' =>
                $order->updated_at
                    ?->toIso8601String(),
        ];
    }

    /**
     * إنشاء رابط صورة المنتج.
     */
    private function imageUrl(
        ?string $path,
        Request $request
    ): ?string {
        if (blank($path)) {
            return null;
        }

        if (
            filter_var(
                $path,
                FILTER_VALIDATE_URL
            )
        ) {
            return $path;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        $storageUrl = $disk->url($path);

        if (
            filter_var(
                $storageUrl,
                FILTER_VALIDATE_URL
            )
        ) {
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