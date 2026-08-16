<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();

            $table->string('title');

            $table->string('subtitle')
                ->nullable();

            $table->string('image');

            $table->string('button_text')
                ->nullable();

            $table->string('link_type')
                ->default('none');
            // none = بدون رابط
            // product = فتح منتج
            // category = فتح تصنيف
            // external = رابط خارجي

            $table->unsignedBigInteger('link_id')
                ->nullable();
            // رقم المنتج أو التصنيف

            $table->string('external_url')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->dateTime('starts_at')
                ->nullable();

            $table->dateTime('expires_at')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};