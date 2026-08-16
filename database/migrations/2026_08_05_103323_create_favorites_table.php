<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إنشاء جدول المنتجات المفضلة.
     */
    public function up(): void
    {
        Schema::create(
            'favorites',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->foreignId('product_id')
                    ->constrained('products')
                    ->cascadeOnDelete();

                $table->timestamps();

                /*
                 * منع إضافة المنتج نفسه مرتين
                 * إلى مفضلة العميل.
                 */
                $table->unique(
                    [
                        'user_id',
                        'product_id',
                    ],
                    'favorites_user_product_unique'
                );
            }
        );
    }

    /**
     * حذف جدول المفضلة.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};