<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('reviews') &&
            ! Schema::hasColumn('reviews', 'images')
        ) {
            Schema::table('reviews', function (Blueprint $table): void {
                $table->json('images')
                    ->nullable()
                    ->after('comment');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('reviews') &&
            Schema::hasColumn('reviews', 'images')
        ) {
            Schema::table('reviews', function (Blueprint $table): void {
                $table->dropColumn('images');
            });
        }
    }
};