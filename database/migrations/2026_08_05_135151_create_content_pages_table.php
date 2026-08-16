<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إنشاء جدول الصفحات التعريفية والقانونية.
     */
    public function up(): void
    {
        Schema::create(
            'content_pages',
            function (Blueprint $table): void {
                $table->id();

                $table->string(
                    'title',
                    255
                );

                $table->string(
                    'slug',
                    255
                )->unique();

                $table->text('excerpt')
                    ->nullable();

                $table->longText('content');

                $table->string(
                    'meta_title',
                    255
                )->nullable();

                $table->text('meta_description')
                    ->nullable();

                $table->boolean('is_active')
                    ->default(true);

                $table->unsignedInteger('sort_order')
                    ->default(0);

                $table->timestamps();

                $table->index([
                    'is_active',
                    'sort_order',
                ]);
            }
        );
    }

    /**
     * حذف جدول الصفحات التعريفية.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_pages');
    }
};