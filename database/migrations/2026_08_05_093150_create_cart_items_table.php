<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إنشاء جدول عناصر سلة المشتريات.
     */
    public function up(): void
    {
        Schema::create(
            'cart_items',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->foreignId('product_id')
                    ->constrained('products')
                    ->cascadeOnDelete();

                $table->unsignedInteger('quantity')
                    ->default(1);

                $table->timestamps();

                /*
                 * منع تكرار المنتج نفسه داخل سلة العميل.
                 * عند إضافته مرة ثانية سنزيد الكمية.
                 */
                $table->unique([
                    'user_id',
                    'product_id',
                ]);
            }
        );
    }

    /**
     * حذف جدول السلة.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};