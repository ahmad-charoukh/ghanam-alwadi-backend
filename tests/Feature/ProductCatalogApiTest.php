<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * تعرض واجهة التصنيفات التصنيفات النشطة فقط.
     */
    public function test_categories_index_returns_only_active_categories(): void
    {
        $secondCategory = $this->createCategory([
            'name' => 'اللحوم',
            'slug' => 'meat',
            'sort_order' => 2,
        ]);

        $firstCategory = $this->createCategory([
            'name' => 'الأغنام',
            'slug' => 'sheep',
            'sort_order' => 1,
        ]);

        $this->createCategory([
            'name' => 'تصنيف مخفي',
            'slug' => 'hidden-category',
            'is_active' => false,
        ]);

        $this->createProduct(
            $secondCategory,
            [
                'name' => 'لحم طازج',
            ]
        );

        $this->createProduct(
            $firstCategory,
            [
                'name' => 'خروف كامل',
            ]
        );

        $this->createProduct(
            $firstCategory,
            [
                'name' => 'منتج غير فعال',
                'is_active' => false,
            ]
        );

        $response = $this->getJson(
            '/api/categories'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonCount(
                2,
                'data.categories'
            )
            ->assertJsonPath(
                'data.categories.0.slug',
                'sheep'
            )
            ->assertJsonPath(
                'data.categories.0.products_count',
                1
            )
            ->assertJsonPath(
                'data.pagination.total',
                2
            )
            ->assertJsonMissing([
                'slug' => 'hidden-category',
            ]);
    }

    /**
     * يمكن البحث ضمن التصنيفات.
     */
    public function test_categories_can_be_searched(): void
    {
        $this->createCategory([
            'name' => 'الأغنام',
            'slug' => 'sheep',
        ]);

        $this->createCategory([
            'name' => 'اللحوم',
            'slug' => 'meat',
        ]);

        $response = $this->getJson(
            '/api/categories?search=الأغنام'
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.categories'
            )
            ->assertJsonPath(
                'data.categories.0.slug',
                'sheep'
            );
    }

    /**
     * يمكن عرض تصنيف نشط.
     */
    public function test_active_category_can_be_shown(): void
    {
        $category = $this->createCategory([
            'name' => 'الأغنام',
            'slug' => 'sheep',
        ]);

        $this->createProduct(
            $category,
            [
                'name' => 'خروف كامل',
            ]
        );

        $this->getJson(
            "/api/categories/{$category->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.category.id',
                $category->id
            )
            ->assertJsonPath(
                'data.category.slug',
                'sheep'
            )
            ->assertJsonPath(
                'data.category.products_count',
                1
            );
    }

    /**
     * لا يمكن عرض تصنيف غير فعال.
     */
    public function test_inactive_category_cannot_be_shown(): void
    {
        $category = $this->createCategory([
            'is_active' => false,
        ]);

        $this->getJson(
            "/api/categories/{$category->id}"
        )
            ->assertNotFound()
            ->assertJsonPath(
                'success',
                false
            );
    }

    /**
     * تعرض واجهة المنتجات المنتجات المتاحة فقط.
     */
    public function test_products_index_returns_only_available_products(): void
    {
        $activeCategory =
            $this->createCategory([
                'name' => 'الأغنام',
                'slug' => 'sheep',
            ]);

        $inactiveCategory =
            $this->createCategory([
                'name' => 'تصنيف مخفي',
                'slug' => 'hidden-category',
                'is_active' => false,
            ]);

        $visibleProduct =
            $this->createProduct(
                $activeCategory,
                [
                    'name' => 'خروف متوفر',
                ]
            );

        $this->createProduct(
            $activeCategory,
            [
                'name' => 'منتج مخفي',
                'is_active' => false,
            ]
        );

        $this->createProduct(
            $inactiveCategory,
            [
                'name' =>
                    'منتج ضمن تصنيف مخفي',
            ]
        );

        $response = $this->getJson(
            '/api/products'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.pagination.total',
                1
            )
            ->assertJsonPath(
                'data.products.0.id',
                $visibleProduct->id
            )
            ->assertJsonPath(
                'data.products.0.name',
                'خروف متوفر'
            )
            ->assertJsonMissing([
                'name' => 'منتج مخفي',
            ])
            ->assertJsonMissing([
                'name' =>
                    'منتج ضمن تصنيف مخفي',
            ]);
    }

    /**
     * تعمل فلاتر المنتجات معًا.
     */
    public function test_products_can_be_filtered(): void
    {
        $category = $this->createCategory([
            'name' => 'الأغنام',
            'slug' => 'sheep',
        ]);

        $matchedProduct =
            $this->createProduct(
                $category,
                [
                    'name' => 'Premium Lamb',
                    'price' => 500,
                    'stock' => 5,
                    'is_featured' => true,
                ]
            );

        $this->createProduct(
            $category,
            [
                'name' => 'Fresh Beef',
                'price' => 200,
                'stock' => 0,
                'is_featured' => false,
            ]
        );

        $url = '/api/products'
            . '?search=Lamb'
            . '&category_id='
            . $category->id
            . '&featured=1'
            . '&in_stock=1'
            . '&min_price=400'
            . '&max_price=600';

        $response = $this->getJson($url);

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.pagination.total',
                1
            )
            ->assertJsonPath(
                'data.products.0.id',
                $matchedProduct->id
            )
            ->assertJsonPath(
                'data.products.0.name',
                'Premium Lamb'
            )
            ->assertJsonPath(
                'data.products.0.is_in_stock',
                true
            )
            ->assertJsonPath(
                'data.products.0.is_featured',
                true
            );
    }

    /**
     * تعمل خيارات الترتيب وتقسيم الصفحات.
     */
    public function test_products_can_be_sorted_and_paginated(): void
    {
        $category = $this->createCategory();

        $this->createProduct(
            $category,
            [
                'name' => 'Expensive Product',
                'price' => 700,
            ]
        );

        $cheapestProduct =
            $this->createProduct(
                $category,
                [
                    'name' => 'Cheap Product',
                    'price' => 100,
                ]
            );

        $response = $this->getJson(
            '/api/products'
            . '?sort=price_asc'
            . '&per_page=1'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.products.0.id',
                $cheapestProduct->id
            )
            ->assertJsonPath(
                'data.products.0.name',
                'Cheap Product'
            )
            ->assertJsonPath(
                'data.pagination.current_page',
                1
            )
            ->assertJsonPath(
                'data.pagination.last_page',
                2
            )
            ->assertJsonPath(
                'data.pagination.per_page',
                1
            )
            ->assertJsonPath(
                'data.pagination.total',
                2
            );
    }

    /**
     * يمكن عرض منتج نشط واحد.
     */
    public function test_active_product_can_be_shown(): void
    {
        $category = $this->createCategory();

        $product = $this->createProduct(
            $category,
            [
                'name' => 'خروف الوادي',
                'price' => 850,
                'stock' => 3,
            ]
        );

        $this->getJson(
            "/api/products/{$product->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.product.id',
                $product->id
            )
            ->assertJsonPath(
                'data.product.name',
                'خروف الوادي'
            )
            ->assertJsonPath(
                'data.product.category.id',
                $category->id
            )
            ->assertJsonPath(
                'data.product.rating.average',
                0
            )
            ->assertJsonPath(
                'data.product.rating.count',
                0
            );
    }

    /**
     * لا يمكن عرض منتج غير فعال.
     */
    public function test_inactive_product_cannot_be_shown(): void
    {
        $category = $this->createCategory();

        $product = $this->createProduct(
            $category,
            [
                'is_active' => false,
            ]
        );

        $this->getJson(
            "/api/products/{$product->id}"
        )
            ->assertNotFound()
            ->assertJsonPath(
                'success',
                false
            );
    }

    /**
     * يتم رفض فلاتر المنتجات غير الصحيحة.
     */
    public function test_invalid_product_filters_are_rejected(): void
    {
        $this->getJson(
            '/api/products'
            . '?min_price=500'
            . '&max_price=100'
            . '&sort=invalid'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'max_price',
                'sort',
            ]);
    }

    private function createCategory(
        array $attributes = []
    ): Category {
        static $categoryNumber = 0;

        $categoryNumber++;

        return Category::query()->create(
            array_merge([
                'name' =>
                    'Test Category '
                    . $categoryNumber,

                'slug' =>
                    'test-category-'
                    . $categoryNumber,

                'description' =>
                    'Test category description',

                'image' => null,
                'is_active' => true,
                'sort_order' =>
                    $categoryNumber,
            ], $attributes)
        );
    }

    private function createProduct(
        Category $category,
        array $attributes = []
    ): Product {
        static $productNumber = 0;

        $productNumber++;

        return Product::query()->create(
            array_merge([
                'name' =>
                    'Test Product '
                    . $productNumber,

                'category_id' =>
                    $category->id,

                'category' =>
                    $category->name,

                'price' => 250,
                'stock' => 10,
                'image' => null,

                'description' =>
                    'Test product description',

                'is_active' => true,
                'is_featured' => false,
            ], $attributes)
        );
    }
}