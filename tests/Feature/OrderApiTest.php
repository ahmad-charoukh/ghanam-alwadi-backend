<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_orders(): void
    {
        $this->getJson('/api/orders')
            ->assertUnauthorized();

        $this->postJson(
            '/api/orders/preview'
        )->assertUnauthorized();
    }

    public function test_order_preview_rejects_empty_cart(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/orders/preview')
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'cart',
            ]);
    }

    public function test_order_preview_calculates_pricing_correctly(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct([
            'price' => 100,
            'stock' => 10,
        ]);

        $this->addToCart(
            $user,
            $product,
            2
        );

        $this->configureSettings([
            'shipping_cost' => 20,
            'free_shipping_amount' => 500,
            'tax_percentage' => 15,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/orders/preview')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.pricing.subtotal',
                fn (mixed $value): bool =>
                    (float) $value === 200.0
            )
            ->assertJsonPath(
                'data.pricing.delivery_fee',
                fn (mixed $value): bool =>
                    (float) $value === 20.0
            )
            ->assertJsonPath(
                'data.pricing.tax_amount',
                fn (mixed $value): bool =>
                    (float) $value === 33.0
            )
            ->assertJsonPath(
                'data.pricing.total',
                fn (mixed $value): bool =>
                    (float) $value === 253.0
            )
            ->assertJsonPath(
                'data.shipping.is_free',
                false
            );
    }

    public function test_percentage_coupon_respects_maximum_discount(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct([
            'price' => 100,
            'stock' => 10,
        ]);

        $this->addToCart(
            $user,
            $product,
            2
        );

        $this->configureSettings([
            'shipping_cost' => 20,
            'free_shipping_amount' => 500,
            'tax_percentage' => 15,
        ]);

        $coupon = $this->createCoupon([
            'code' => 'WADI10',
            'discount_type' =>
                Coupon::TYPE_PERCENTAGE,
            'discount_value' => 10,
            'maximum_discount_amount' => 15,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson(
            '/api/orders/preview',
            [
                'coupon_code' => 'wadi10',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.coupon.code',
                $coupon->code
            )
            ->assertJsonPath(
                'data.pricing.discount',
                fn (mixed $value): bool =>
                    (float) $value === 15.0
            )
            ->assertJsonPath(
                'data.pricing.tax_amount',
                fn (mixed $value): bool =>
                    (float) $value === 30.75
            )
            ->assertJsonPath(
                'data.pricing.total',
                fn (mixed $value): bool =>
                    (float) $value === 235.75
            );
    }

    public function test_customer_can_create_order_from_cart(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
        ]);

        $address = $this->createAddress($user);

        $product = $this->createProduct([
            'name' => 'Test Lamb',
            'price' => 100,
            'stock' => 10,
        ]);

        $this->addToCart(
            $user,
            $product,
            2
        );

        $this->configureSettings([
            'shipping_cost' => 20,
            'free_shipping_amount' => 500,
            'tax_percentage' => 0,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $response = $this->postJson(
            '/api/orders',
            [
                'address_id' => $address->id,

                'payment_method' =>
                    Order::PAYMENT_METHOD_CASH,

                'customer_notes' =>
                    'Test order notes',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.order.status',
                Order::STATUS_NEW
            )
            ->assertJsonPath(
                'data.order.customer.address_id',
                $address->id
            )
            ->assertJsonPath(
                'data.order.pricing.total',
                fn (mixed $value): bool =>
                    (float) $value === 220.0
            )
            ->assertJsonCount(
                1,
                'data.order.items'
            )
            ->assertJsonPath(
                'data.order.items.0.product_name',
                'Test Lamb'
            )
            ->assertJsonPath(
                'data.order.items.0.quantity',
                2
            );

        $order = Order::query()->firstOrFail();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
            'address_id' => $address->id,
            'status' => Order::STATUS_NEW,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertSame(
            8,
            $product->fresh()->stock
        );

        $this->assertDatabaseMissing(
            'cart_items',
            [
                'user_id' => $user->id,
            ]
        );

        $this->assertDatabaseCount(
            'notifications',
            1
        );
    }

    public function test_customer_cannot_use_another_customers_address(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherAddress =
            $this->createAddress($otherUser);

        $product = $this->createProduct();

        $this->addToCart(
            $user,
            $product,
            1
        );

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/orders', [
            'address_id' => $otherAddress->id,

            'payment_method' =>
                Order::PAYMENT_METHOD_CASH,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'address_id',
            ]);

        $this->assertDatabaseCount(
            'orders',
            0
        );
    }

    public function test_customer_can_view_only_own_order(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $address =
            $this->createAddress($owner);

        $product = $this->createProduct();

        $this->addToCart(
            $owner,
            $product,
            1
        );

        Sanctum::actingAs($owner, ['mobile']);

        $this->postJson('/api/orders', [
            'address_id' => $address->id,

            'payment_method' =>
                Order::PAYMENT_METHOD_CASH,
        ])->assertCreated();

        $order = Order::query()->firstOrFail();

        $this->getJson(
            "/api/orders/{$order->order_number}"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.order.id',
                $order->id
            );

        Sanctum::actingAs(
            $otherUser,
            ['mobile']
        );

        $this->getJson(
            "/api/orders/{$order->order_number}"
        )->assertNotFound();

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonPath(
                'data.pagination.total',
                0
            );
    }

    public function test_cancelling_order_restores_stock_and_coupon_usage(): void
    {
        $user = User::factory()->create();

        $address =
            $this->createAddress($user);

        $product = $this->createProduct([
            'price' => 100,
            'stock' => 10,
        ]);

        $coupon = $this->createCoupon([
            'code' => 'WADI10',

            'discount_type' =>
                Coupon::TYPE_PERCENTAGE,

            'discount_value' => 10,
        ]);

        $this->addToCart(
            $user,
            $product,
            2
        );

        $this->configureSettings([
            'shipping_cost' => 0,
            'free_shipping_amount' => 0,
            'tax_percentage' => 0,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/orders', [
            'address_id' => $address->id,

            'payment_method' =>
                Order::PAYMENT_METHOD_CASH,

            'coupon_code' => 'wadi10',
        ])
            ->assertCreated()
            ->assertJsonPath(
                'data.order.pricing.discount',
                fn (mixed $value): bool =>
                    (float) $value === 20.0
            );

        $order = Order::query()->firstOrFail();

        $this->assertSame(
            8,
            $product->fresh()->stock
        );

        $this->assertSame(
            1,
            $coupon->fresh()->used_count
        );

        $this->postJson(
            "/api/orders/{$order->order_number}/cancel"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.order.status',
                Order::STATUS_CANCELLED
            );

        $this->assertSame(
            10,
            $product->fresh()->stock
        );

        $this->assertSame(
            0,
            $coupon->fresh()->used_count
        );

        $this->assertNotNull(
            $order->fresh()->cancelled_at
        );
    }

    public function test_processing_order_cannot_be_cancelled(): void
    {
        $user = User::factory()->create();

        $address =
            $this->createAddress($user);

        $product = $this->createProduct([
            'stock' => 10,
        ]);

        $this->addToCart(
            $user,
            $product,
            2
        );

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/orders', [
            'address_id' => $address->id,

            'payment_method' =>
                Order::PAYMENT_METHOD_CASH,
        ])->assertCreated();

        $order = Order::query()->firstOrFail();

        $order->update([
            'status' =>
                Order::STATUS_PROCESSING,
        ]);

        $this->postJson(
            "/api/orders/{$order->order_number}/cancel"
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'order',
            ]);

        $this->assertSame(
            Order::STATUS_PROCESSING,
            $order->fresh()->status
        );

        $this->assertSame(
            8,
            $product->fresh()->stock
        );
    }

    private function configureSettings(
        array $attributes = []
    ): Setting {
        $settings = Setting::current();

        $settings->update(
            array_merge([
                'shipping_cost' => 0,
                'free_shipping_amount' => 0,
                'tax_percentage' => 0,
                'currency' => 'SAR',
            ], $attributes)
        );

        return $settings->fresh();
    }

    private function createAddress(
        User $user
    ): Address {
        return $user->addresses()->create([
            'label' => 'المنزل',
            'recipient_name' =>
                'Test Customer',
            'phone' => '0500000000',
            'country' => 'السعودية',
            'city' => 'Riyadh',
            'district' => 'Test District',
            'street' => 'Test Street',
            'building_number' => '10',
            'apartment_number' => null,
            'postal_code' => '12345',
            'additional_details' => null,
            'latitude' => null,
            'longitude' => null,
            'is_default' => true,
        ]);
    }

    private function createProduct(
        array $attributes = []
    ): Product {
        static $number = 0;

        $number++;

        $category = Category::query()->create([
            'name' => 'Category ' . $number,
            'slug' => 'category-' . $number,
            'description' => null,
            'image' => null,
            'is_active' => true,
            'sort_order' => $number,
        ]);

        return Product::query()->create(
            array_merge([
                'name' => 'Product ' . $number,
                'category_id' => $category->id,
                'category' => $category->name,
                'price' => 100,
                'stock' => 10,
                'image' => null,
                'description' => null,
                'is_active' => true,
                'is_featured' => false,
            ], $attributes)
        );
    }

    private function addToCart(
        User $user,
        Product $product,
        int $quantity
    ): CartItem {
        return CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    private function createCoupon(
        array $attributes = []
    ): Coupon {
        return Coupon::query()->create(
            array_merge([
                'code' => 'TEST10',

                'discount_type' =>
                    Coupon::TYPE_PERCENTAGE,

                'discount_value' => 10,
                'minimum_order_amount' => 0,

                'maximum_discount_amount' =>
                    null,

                'usage_limit' => null,

                'usage_limit_per_user' =>
                    1,

                'used_count' => 0,

                'starts_at' =>
                    now()->subDay(),

                'expires_at' =>
                    now()->addDay(),

                'is_active' => true,
                'description' => null,
            ], $attributes)
        );
    }
}