<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_endpoint_returns_only_approved_reviews(): void
    {
        $product = $this->createProduct();

        $firstUser = User::factory()->create([
            'name' => 'First Customer',
        ]);

        $secondUser = User::factory()->create();

        Review::query()->create([
            'user_id' => $firstUser->id,
            'product_id' => $product->id,
            'order_id' => null,
            'rating' => 5,
            'title' => 'Excellent',
            'comment' => 'Approved review',
            'is_approved' => true,
            'admin_reply' => 'Thank you',
            'approved_at' => now(),
        ]);

        Review::query()->create([
            'user_id' => $secondUser->id,
            'product_id' => $product->id,
            'order_id' => null,
            'rating' => 1,
            'title' => 'Pending',
            'comment' => 'Pending review',
            'is_approved' => false,
            'admin_reply' => null,
            'approved_at' => null,
        ]);

        $this->getJson(
            "/api/products/{$product->id}/reviews"
        )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.reviews'
            )
            ->assertJsonPath(
                'data.reviews.0.user_name',
                'First Customer'
            )
            ->assertJsonPath(
                'data.product.average_rating',
                5
            )
            ->assertJsonPath(
                'data.product.reviews_count',
                1
            )
            ->assertJsonPath(
                'data.pagination.total',
                1
            )
            ->assertJsonMissing([
                'title' => 'Pending',
            ]);
    }

    public function test_guest_cannot_manage_reviews(): void
    {
        $this->getJson('/api/reviews')
            ->assertUnauthorized();

        $this->postJson(
            '/api/reviews',
            []
        )->assertUnauthorized();
    }

    public function test_customer_can_review_delivered_product(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $order = $this->createOrderWithProduct(
            $user,
            $product,
            Order::STATUS_DELIVERED
        );

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/reviews', [
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 5,
            'title' => 'منتج ممتاز',
            'comment' => 'جودة ممتازة',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.review.product.id',
                $product->id
            )
            ->assertJsonPath(
                'data.review.order.id',
                $order->id
            )
            ->assertJsonPath(
                'data.review.rating',
                5
            )
            ->assertJsonPath(
                'data.review.is_approved',
                false
            );

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 5,
            'is_approved' => false,
        ]);
    }

    public function test_customer_cannot_review_product_before_delivery(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $order = $this->createOrderWithProduct(
            $user,
            $product,
            Order::STATUS_PROCESSING
        );

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/reviews', [
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 4,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'order_id',
            ]);

        $this->assertDatabaseCount(
            'reviews',
            0
        );
    }

    public function test_customer_cannot_review_same_order_product_twice(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $order = $this->createOrderWithProduct(
            $user,
            $product,
            Order::STATUS_DELIVERED
        );

        Review::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 5,
            'title' => null,
            'comment' => null,
            'is_approved' => false,
            'admin_reply' => null,
            'approved_at' => null,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/reviews', [
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 4,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'product_id',
            ]);

        $this->assertDatabaseCount(
            'reviews',
            1
        );
    }

    public function test_customer_sees_only_own_reviews(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = $this->createProduct();

        $ownReview = $this->createReview(
            $user,
            $product,
            [
                'rating' => 5,
            ]
        );

        $this->createReview(
            $otherUser,
            $product,
            [
                'rating' => 1,
            ]
        );

        Sanctum::actingAs($user, ['mobile']);

        $this->getJson('/api/reviews')
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.reviews'
            )
            ->assertJsonPath(
                'data.reviews.0.id',
                $ownReview->id
            )
            ->assertJsonPath(
                'data.pagination.total',
                1
            );
    }

    public function test_updating_review_returns_it_to_pending(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $review = $this->createReview(
            $user,
            $product,
            [
                'rating' => 5,
                'is_approved' => true,
                'admin_reply' => 'Old reply',
                'approved_at' => now(),
            ]
        );

        Sanctum::actingAs($user, ['mobile']);

        $this->putJson(
            "/api/reviews/{$review->id}",
            [
                'rating' => 4,
                'title' => 'Updated title',
                'comment' => 'Updated comment',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.review.rating',
                4
            )
            ->assertJsonPath(
                'data.review.is_approved',
                false
            )
            ->assertJsonPath(
                'data.review.admin_reply',
                null
            );

        $review->refresh();

        $this->assertFalse(
            $review->is_approved
        );

        $this->assertNull(
            $review->admin_reply
        );

        $this->assertNull(
            $review->approved_at
        );
    }

    public function test_customer_cannot_modify_another_customers_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = $this->createProduct();

        $review = $this->createReview(
            $owner,
            $product
        );

        Sanctum::actingAs(
            $otherUser,
            ['mobile']
        );

        $this->putJson(
            "/api/reviews/{$review->id}",
            [
                'rating' => 1,
            ]
        )->assertNotFound();

        $this->deleteJson(
            "/api/reviews/{$review->id}"
        )->assertNotFound();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_customer_can_delete_own_review(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $review = $this->createReview(
            $user,
            $product
        );

        Sanctum::actingAs($user, ['mobile']);

        $this->deleteJson(
            "/api/reviews/{$review->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            );

        $this->assertDatabaseMissing(
            'reviews',
            [
                'id' => $review->id,
            ]
        );
    }

    private function createProduct(): Product
    {
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

        return Product::query()->create([
            'name' => 'Product ' . $number,
            'category_id' => $category->id,
            'category' => $category->name,
            'price' => 100,
            'stock' => 10,
            'image' => null,
            'description' => null,
            'is_active' => true,
            'is_featured' => false,
        ]);
    }

    private function createOrderWithProduct(
        User $user,
        Product $product,
        string $status
    ): Order {
        $order = Order::query()->create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '0500000000',
            'city' => 'Riyadh',
            'address' => 'Test Address',
            'subtotal' => 100,
            'delivery_fee' => 0,
            'discount' => 0,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'total' => 100,
            'currency' => 'SAR',

            'payment_method' =>
                Order::PAYMENT_METHOD_CASH,

            'payment_status' =>
                Order::PAYMENT_PAID,

            'status' => $status,
            'customer_notes' => null,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_image' => $product->image,
            'unit_price' => 100,
            'quantity' => 1,
            'total' => 100,
            'notes' => null,
        ]);

        return $order;
    }

    private function createReview(
        User $user,
        Product $product,
        array $attributes = []
    ): Review {
        return Review::query()->create(
            array_merge([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'order_id' => null,
                'rating' => 5,
                'title' => 'Test Review',
                'comment' => 'Test Comment',
                'is_approved' => false,
                'admin_reply' => null,
                'approved_at' => null,
            ], $attributes)
        );
    }
}