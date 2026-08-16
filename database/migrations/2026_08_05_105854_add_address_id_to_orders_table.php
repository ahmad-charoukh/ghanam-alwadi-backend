<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة العنوان المحفوظ المرتبط بالطلب.
     */
    public function up(): void
    {
        Schema::table(
            'orders',
            function (Blueprint $table): void {
                $table->foreignId('address_id')
                    ->nullable()
                    ->constrained('addresses')
                    ->nullOnDelete();
            }
        );
    }

    /**
     * حذف ارتباط العنوان من الطلبات.
     */
    public function down(): void
    {
        Schema::table(
            'orders',
            function (Blueprint $table): void {
                $table->dropConstrainedForeignId(
                    'address_id'
                );
            }
        );
    }
};