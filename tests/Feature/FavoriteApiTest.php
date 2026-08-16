<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FavoriteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_favorites(): void
    {
        $this->getJson('/api/favorites')
            ->assertUnauthorized();

        $this->postJson('/api/favorites', [
            'product_id' => 1,
        ])->assertUnauthorized();
    }

    public function test_customer_can_add_product_to_favorites(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/favorites', [
            'product_id' => $product->id,
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.favorite.product.id',
                $product->id
            );

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_adding_same_product_does_not_duplicate_favorite(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/favorites', [
            'product_id' => $product->id,
        ])->assertCreated();

        $this->postJson('/api/favorites', [
            'product_id' => $product->id,
        ])->assertOk();

        $this->assertDatabaseCount(
            'favorites',
            1
        );
    }

    public function test_inactive_product_cannot_be_favorited(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct([
            'is_active' => false,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson('/api/favorites', [
            'product_id' => $product->id,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount(
            'favorites',
            0
        );
    }

    public function test_customer_sees_only_own_active_favorites(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $activeProduct = $this->createProduct();

        $inactiveProduct = $this->createProduct([
            'is_active' => false,
        ]);

        $otherProduct = $this->createProduct();

        Favorite::query()->create([
            'user_id' => $user->id,
            'product_id' => $activeProduct->id,
        ]);

        Favorite::query()->create([
            'user_id' => $user->id,
            'product_id' => $inactiveProduct->id,
        ]);

        Favorite::query()->create([
            'user_id' => $otherUser->id,
            'product_id' => $otherProduct->id,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->getJson('/api/favorites')
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.favorites'
            )
            ->assertJsonPath(
                'data.favorites.0.product.id',
                $activeProduct->id
            )
            ->assertJsonPath(
                'data.pagination.total',
                1
            )
            ->assertJsonMissing([
                'id' => $otherProduct->id,
                'name' => $otherProduct->name,
            ]);
    }

    public function test_customer_can_check_favorite_status(): void
    {
        $user = User::factory()->create();

        $favoriteProduct =
            $this->createProduct();

        $normalProduct =
            $this->createProduct();

        $favorite = Favorite::query()->create([
            'user_id' => $user->id,
            'product_id' => $favoriteProduct->id,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->getJson(
            "/api/favorites/{$favoriteProduct->id}/check"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.is_favorite',
                true
            )
            ->assertJsonPath(
                'data.favorite_id',
                $favorite->id
            );

        $this->getJson(
            "/api/favorites/{$normalProduct->id}/check"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.is_favorite',
                false
            )
            ->assertJsonPath(
                'data.favorite_id',
                null
            );
    }

    public function test_customer_can_remove_product_from_favorites(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        Favorite::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->deleteJson(
            "/api/favorites/{$product->id}"
        )
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->deleteJson(
            "/api/favorites/{$product->id}"
        )->assertNotFound();
    }

    public function test_clear_removes_only_current_customers_favorites(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $firstProduct =
            $this->createProduct();

        $secondProduct =
            $this->createProduct();

        $otherProduct =
            $this->createProduct();

        Favorite::query()->create([
            'user_id' => $user->id,
            'product_id' => $firstProduct->id,
        ]);

        Favorite::query()->create([
            'user_id' => $user->id,
            'product_id' => $secondProduct->id,
        ]);

        $otherFavorite =
            Favorite::query()->create([
                'user_id' => $otherUser->id,
                'product_id' => $otherProduct->id,
            ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->deleteJson('/api/favorites/clear')
            ->assertOk()
            ->assertJsonPath(
                'data.deleted_items_count',
                2
            );

        $this->assertDatabaseMissing(
            'favorites',
            [
                'user_id' => $user->id,
            ]
        );

        $this->assertDatabaseHas(
            'favorites',
            [
                'id' => $otherFavorite->id,
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