<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContentPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(
            function (ContentPage $page): void {
                if (filled($page->slug)) {
                    $page->slug = Str::slug(
                        $page->slug
                    );

                    return;
                }

                $page->slug =
                    self::generateUniqueSlug(
                        $page->title
                    );
            }
        );

        static::updating(
            function (ContentPage $page): void {
                if ($page->isDirty('slug')) {
                    $page->slug = Str::slug(
                        $page->slug
                    );
                }
            }
        );
    }

    /**
     * الصفحات المنشورة فقط.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            'is_active',
            true
        );
    }

    /**
     * ترتيب الصفحات حسب ترتيب الإدارة.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * استخدام slug بدل الرقم في رابط الصفحة.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * إنشاء slug فريد تلقائيًا.
     */
    private static function generateUniqueSlug(
        string $title
    ): string {
        $baseSlug = Str::slug($title);

        if (blank($baseSlug)) {
            $baseSlug = 'page';
        }

        $slug = $baseSlug;
        $counter = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug
                . '-'
                . $counter;

            $counter++;
        }

        return $slug;
    }
}