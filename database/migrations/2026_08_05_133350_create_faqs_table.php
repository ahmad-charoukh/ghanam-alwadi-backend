<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إنشاء جدول الأسئلة الشائعة.
     */
    public function up(): void
    {
        Schema::create(
            'faqs',
            function (Blueprint $table): void {
                $table->id();

                $table->string(
                    'question',
                    500
                );

                $table->text('answer');

                $table->string(
                    'category',
                    100
                )->default('general');

                $table->boolean('is_active')
                    ->default(true);

                $table->unsignedInteger('sort_order')
                    ->default(0);

                $table->timestamps();

                $table->index([
                    'category',
                    'is_active',
                ]);

                $table->index('sort_order');
            }
        );
    }

    /**
     * حذف جدول الأسئلة الشائعة.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};