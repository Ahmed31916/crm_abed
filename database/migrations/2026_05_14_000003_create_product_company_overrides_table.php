<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_company_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained('users')->onDelete('cascade');
            $table->decimal('price_override', 10, 2)->nullable();
            $table->decimal('sale_price_override', 10, 2)->nullable();
            $table->integer('stock_quantity_override')->nullable();
            $table->string('stock_status_override', 50)->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->unique(['product_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_company_overrides');
    }
};
