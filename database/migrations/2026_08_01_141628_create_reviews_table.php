<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            $table->unsignedTinyInteger('rating');

            $table->string('title')
                ->nullable();

            $table->text('comment')
                ->nullable();

            $table->boolean('is_approved')
                ->default(false);

            $table->text('admin_reply')
                ->nullable();

            $table->dateTime('approved_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'product_id',
                'is_approved',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};