<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إنشاء جدول عناوين العملاء.
     */
    public function up(): void
    {
        Schema::create(
            'addresses',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->string(
                    'label',
                    50
                )->default('المنزل');

                $table->string(
                    'recipient_name',
                    150
                );

                $table->string(
                    'phone',
                    30
                );

                $table->string(
                    'country',
                    100
                )->default('السعودية');

                $table->string(
                    'city',
                    100
                );

                $table->string(
                    'district',
                    150
                )->nullable();

                $table->string(
                    'street',
                    200
                )->nullable();

                $table->string(
                    'building_number',
                    50
                )->nullable();

                $table->string(
                    'apartment_number',
                    50
                )->nullable();

                $table->string(
                    'postal_code',
                    30
                )->nullable();

                $table->text(
                    'additional_details'
                )->nullable();

                $table->decimal(
                    'latitude',
                    10,
                    7
                )->nullable();

                $table->decimal(
                    'longitude',
                    10,
                    7
                )->nullable();

                $table->boolean(
                    'is_default'
                )->default(false);

                $table->timestamps();

                $table->index([
                    'user_id',
                    'is_default',
                ]);

                $table->index('city');
            }
        );
    }

    /**
     * حذف جدول العناوين.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};