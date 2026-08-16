<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('support_ticket_id')
                ->constrained('support_tickets')
                ->cascadeOnDelete();

            $table->string('sender_type')
                ->default('customer');

            $table->foreignId('sender_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('message');

            $table->string('attachment')
                ->nullable();

            $table->boolean('is_read')
                ->default(false);

            $table->dateTime('read_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'support_ticket_id',
                'created_at',
            ]);

            $table->index([
                'support_ticket_id',
                'is_read',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};