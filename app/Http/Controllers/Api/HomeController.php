<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $banners = Banner::query()
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(
                fn (Banner $banner): array => [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'subtitle' => $banner->subtitle,
                    'image' => $banner->image,
                    'image_url' => $this->imageUrl(
                        $banner->image,
                        $request
                    ),
                    'button_text' => $banner->button_text,
                    'link_type' => $banner->link_type,
                    'link_id' => $banner->link_id,
                    'external_url' => $banner->external_url,
                    'sort_order' => $banner->sort_order,
                ]
            )
            ->values();

        $categories = Category::query()
            ->where('is_active', true)
            ->withCount([
                'products as products_count' => fn (
                    Builder $query
                ): Builder => $query->where('is_active', true),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(
                fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'image' => $category->image,
                    'image_url' => $this->imageUrl(
                        $category->image,
                        $request
                    ),
                    'products_count' => $category->products_count,
                    'sort_order' => $category->sort_order,
                ]
            )
            ->values();

        $featuredProducts = Product::query()
            ->with('productCategory:id,name,slug')
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->where('is_active', true)
            ->where('is_featured', true)
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(
                fn (Product $product): array =>
                    $this->formatProduct($product, $request)
            )
            ->values();

        $latestProducts = Product::query()
            ->with('productCategory:id,name,slug')
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->where('is_active', true)
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(
                fn (Product $product): array =>
                    $this->formatProduct($product, $request)
            )
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'تم تحميل بيانات الصفحة الرئيسية بنجاح.',
            'data' => [
                'banners' => $banners,
                'categories' => $categories,
                'featured_products' => $featuredProducts,
                'latest_products' => $latestProducts,
            ],
        ]);
    }

    private function formatProduct(
        Product $product,
        Request $request
    ): array {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->price,
            'stock' => (int) $product->stock,
            'image' => $product->image,
            'image_url' => $this->imageUrl(
                $product->image,
                $request
            ),
            'is_featured' => (bool) $product->is_featured,

            'average_rating' => round(
                (float) (
                    $product->approved_reviews_avg_rating ?? 0
                ),
                1
            ),

            'reviews_count' => (int) (
                $product->approved_reviews_count ?? 0
            ),

            'category_name' =>
                $product->productCategory?->name
                ?? $product->category
                ?? 'بدون تصنيف',

            'category' => $product->productCategory
                ? [
                    'id' => $product->productCategory->id,
                    'name' => $product->productCategory->name,
                    'slug' => $product->productCategory->slug,
                ]
                : null,

            'created_at' =>
                $product->created_at?->toIso8601String(),
        ];
    }

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

        return rtrim($request->getSchemeAndHttpHost(), '/')
            . '/'
            . ltrim($storageUrl, '/');
    }
}