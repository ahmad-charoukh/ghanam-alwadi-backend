<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();

            $table->string('ticket_number')->unique();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('subject');

            $table->string('category')
                ->default('general');

            $table->string('priority')
                ->default('normal');

            $table->text('message');

            $table->string('attachment')
                ->nullable();

            $table->string('status')
                ->default('new');

            $table->text('admin_reply')
                ->nullable();

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('replied_at')
                ->nullable();

            $table->dateTime('closed_at')
                ->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['priority', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};