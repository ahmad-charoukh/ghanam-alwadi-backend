<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * منع العميل من تقييم المنتج نفسه
     * أكثر من مرة ضمن الطلب نفسه.
     */
    public function up(): void
    {
        Schema::table(
            'reviews',
            function (Blueprint $table): void {
                $table->unique(
                    [
                        'user_id',
                        'product_id',
                        'order_id',
                    ],
                    'reviews_user_product_order_unique'
                );
            }
        );
    }

    /**
     * حذف قيد منع التكرار.
     */
    public function down(): void
    {
        Schema::table(
            'reviews',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'reviews_user_product_order_unique'
                );
            }
        );
    }
};