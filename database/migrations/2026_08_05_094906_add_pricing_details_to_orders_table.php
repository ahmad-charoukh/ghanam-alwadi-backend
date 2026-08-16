<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة تفاصيل التسعير والكوبون إلى الطلبات.
     */
    public function up(): void
    {
        Schema::table(
            'orders',
            function (Blueprint $table): void {
                $table->foreignId('coupon_id')
                    ->nullable()
                    ->constrained('coupons')
                    ->nullOnDelete();

                $table->string('coupon_code')
                    ->nullable();

                $table->decimal(
                    'tax_percentage',
                    5,
                    2
                )->default(0);

                $table->decimal(
                    'tax_amount',
                    10,
                    2
                )->default(0);

                $table->string(
                    'currency',
                    10
                )->default('SAR');

                $table->index('coupon_code');
            }
        );
    }

    /**
     * حذف تفاصيل التسعير والكوبون.
     */
    public function down(): void
    {
        Schema::table(
            'orders',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'coupon_code',
                ]);

                $table->dropConstrainedForeignId(
                    'coupon_id'
                );

                $table->dropColumn([
                    'coupon_code',
                    'tax_percentage',
                    'tax_amount',
                    'currency',
                ]);
            }
        );
    }
};