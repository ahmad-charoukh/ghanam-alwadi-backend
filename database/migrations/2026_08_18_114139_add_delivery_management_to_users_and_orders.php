<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_delivery_driver')
                ->default(false);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('delivery_driver_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('assigned_at')
                ->nullable();

            $table->text('delivery_notes')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId(
                'delivery_driver_id'
            );

            $table->dropColumn([
                'assigned_at',
                'delivery_notes',
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_delivery_driver');
        });
    }
};