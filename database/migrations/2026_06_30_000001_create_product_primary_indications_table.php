<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create product_primary_indications pivot table
 *
 * يحوّل علاقة Primary Indications من JSON/Array داخل health_products
 * إلى علاقة Many-to-Many عبر Pivot Table.
 *
 * Run: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_primary_indications')) {
            Schema::create('product_primary_indications', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('primary_indication_id');

                // Foreign keys
                $table->foreign('product_id')
                      ->references('id')
                      ->on('products')
                      ->onDelete('cascade');

                $table->foreign('primary_indication_id')
                      ->references('id')
                      ->on('primary_indications')
                      ->onDelete('cascade');

                // Unique combo — يمنع تكرار نفس الربط مرتين
                $table->unique(['product_id', 'primary_indication_id'], 'prod_prim_ind_unique');

                $table->timestamps();

                // Indexes for fast lookups
                $table->index('product_id');
                $table->index('primary_indication_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_primary_indications');
    }
};
