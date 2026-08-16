<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('app_name')
                ->default('غنم الوادي');

            $table->string('logo')
                ->nullable();

            $table->string('phone')
                ->nullable();

            $table->string('whatsapp')
                ->nullable();

            $table->string('email')
                ->nullable();

            $table->text('address')
                ->nullable();

            $table->text('about')
                ->nullable();

            $table->string('facebook_url')
                ->nullable();

            $table->string('instagram_url')
                ->nullable();

            $table->string('tiktok_url')
                ->nullable();

            $table->string('telegram_url')
                ->nullable();

            $table->string('x_url')
                ->nullable();

            $table->decimal('tax_percentage', 5, 2)
                ->default(0);

            $table->decimal('shipping_cost', 10, 2)
                ->default(0);

            $table->decimal('free_shipping_amount', 10, 2)
                ->nullable();

            $table->string('currency')
                ->default('SAR');

            $table->boolean('maintenance_mode')
                ->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'app_name',
                'logo',
                'phone',
                'whatsapp',
                'email',
                'address',
                'about',
                'facebook_url',
                'instagram_url',
                'tiktok_url',
                'telegram_url',
                'x_url',
                'tax_percentage',
                'shipping_cost',
                'free_shipping_amount',
                'currency',
                'maintenance_mode',
            ]);
        });
    }
};