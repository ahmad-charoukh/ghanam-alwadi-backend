<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_cart(): void
    {
        $this->getJson('/api/cart')
            ->assertUnauthorized();

        $this->postJson('/api/cart', [
            'product_id' => 1,
            'quantity' => 1,
        ])->assertUnauthorized();
    }

    public function test_customer_can_view_empty_cart(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['mobile']);

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data.items')
            ->assertJsonPath(
                'data.summary.items_count',
                0
            )
            ->assertJsonPath(
                'data.summary.total_quantity',
                0
            )
            ->assertJsonPath(
                'data.summary.checkout_ready',
                false
            );
    }

    public function test_customer_can_add_product_to_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct([
            'stock' => 10,
            'price' => 250,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.item.quantity',
                2
            )
            ->assertJsonPath(
                'data.item.product.id',
                $product->id
            )
            ->assertJsonPath(
                'data.item.is_available',
                true
            );

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_adding_same_product_increases_existing_quantity(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct([
            'stock' => 10,
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity' => 3,
        ])
            ->assertCreated()
            ->assertJsonPath(
                'data.item.quantity',
                5
            );

        $this->assertDatabaseCount(
            'cart_items',
            1
        );

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_inactive_product_cannot_be_added_to_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct([
            'is_active' => false,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount(
            'cart_items',
            0
        );
    }

    public function test_quantity_cannot_exceed_product_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct([
            'stock' => 3,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity' => 4,
        ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'data.available_stock',
                3
            )
            ->assertJsonPath(
                'data.requested_quantity',
                4
            );

        $this->assertDatabaseCount(
            'cart_items',
            0
        );
    }

    public function test_customer_can_update_own_cart_item(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct([
            'stock' => 10,
        ]);

        $cartItem = CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->putJson(
            "/api/cart/{$cartItem->id}",
            [
                'quantity' => 4,
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.item.quantity',
                4
            );

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 4,
        ]);
    }

    public function test_customer_cannot_modify_another_customers_cart_item(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = $this->createProduct();

        $cartItem = CartItem::query()->create([
            'user_id' => $owner->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        Sanctum::actingAs(
            $otherUser,
            ['mobile']
        );

        $this->putJson(
            "/api/cart/{$cartItem->id}",
            [
                'quantity' => 5,
            ]
        )->assertNotFound();

        $this->deleteJson(
            "/api/cart/{$cartItem->id}"
        )->assertNotFound();

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 2,
        ]);
    }

    public function test_cart_summary_is_calculated_correctly(): void
    {
        $user = User::factory()->create();

        $firstProduct = $this->createProduct([
            'price' => 100,
            'stock' => 10,
        ]);

        $secondProduct = $this->createProduct([
            'price' => 50,
            'stock' => 10,
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $firstProduct->id,
            'quantity' => 2,
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $secondProduct->id,
            'quantity' => 3,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $response = $this->getJson('/api/cart');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath(
                'data.summary.items_count',
                2
            )
            ->assertJsonPath(
                'data.summary.total_quantity',
                5
            )
            ->assertJsonPath(
                'data.summary.checkout_ready',
                true
            )
            ->assertJsonPath(
                'data.summary.subtotal',
                fn (mixed $value): bool =>
                    (float) $value === 350.0
            );
    }

    public function test_customer_can_delete_and_clear_own_cart(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $firstProduct =
            $this->createProduct();

        $secondProduct =
            $this->createProduct();

        $thirdProduct =
            $this->createProduct();

        $firstItem = CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $firstProduct->id,
            'quantity' => 1,
        ]);

        CartItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $secondProduct->id,
            'quantity' => 1,
        ]);

        $otherItem = CartItem::query()->create([
            'user_id' => $otherUser->id,
            'product_id' => $thirdProduct->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->deleteJson(
            "/api/cart/{$firstItem->id}"
        )
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->deleteJson('/api/cart/clear')
            ->assertOk()
            ->assertJsonPath(
                'data.deleted_items_count',
                1
            );

        $this->assertDatabaseMissing(
            'cart_items',
            [
                'user_id' => $user->id,
            ]
        );

        $this->assertDatabaseHas(
            'cart_items',
            [
                'id' => $otherItem->id,
                'user_id' => $otherUser->id,
            ]
        );
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
}