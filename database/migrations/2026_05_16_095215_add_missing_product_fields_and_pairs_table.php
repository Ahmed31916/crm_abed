<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing product fields to the products table
 *
 * This migration adds columns that exist in the old Vital Store project
 * but are missing from the base CRM products table.
 *
 * Columns NOT added (replaced by Spatie MediaLibrary or not needed):
 * - cover_image_path / cover_image_url → replaced by Spatie MediaLibrary
 * - preview_type / preview_content → not used for health products
 * - downloadable_product → not used for health products
 * - attribute_id / product_attribute → variant system not used
 * - custom_field_status → not used
 * - shipping_id → not used for health products
 * - product_type → not needed
 * - store_id → replaced by created_by
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Content fields
            if (!Schema::hasColumn('products', 'specification')) {
                $table->text('specification')->nullable()->after('description');
            }
            if (!Schema::hasColumn('products', 'detail')) {
                $table->text('detail')->nullable()->after('specification');
            }

            // Product attributes
            if (!Schema::hasColumn('products', 'product_weight')) {
                $table->decimal('product_weight', 10, 2)->nullable()->after('stock_status');
            }

            // Tax
            if (!Schema::hasColumn('products', 'tax_status')) {
                $table->string('tax_status', 50)->default('taxable')->after('tax_id');
                // Values: taxable, none
            }

            // Frequency
            if (!Schema::hasColumn('products', 'frequency')) {
                $table->string('frequency', 100)->nullable()->after('brand_id');
            }

        });

        // Create product_pairs pivot table for "Pairs Well With" feature
        if (!Schema::hasTable('product_pairs')) {
            Schema::create('product_pairs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('paired_product_id');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('paired_product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

                $table->unique(['product_id', 'paired_product_id', 'created_by'], 'product_pairs_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columnsToDrop = [
                'specification', 'detail',
                'product_weight', 'tax_status',
                'frequency', 'slug',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('product_pairs');
    }
};
