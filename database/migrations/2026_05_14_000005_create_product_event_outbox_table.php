<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_event_outbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('action'); // created, updated, deleted
            $table->json('payload');
            $table->enum('status', ['pending', 'processing', 'sent', 'failed'])->default('pending');
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(5);
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            // فهارس لتحسين الأداء
            $table->index('status');
            $table->index('next_retry_at');
            $table->index(['status', 'next_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_event_outbox');
    }
};
