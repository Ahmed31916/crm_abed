<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create health_products table
 *
 * جدول البيانات الصحية للمنتجات (HealthProduct)
 *
 * التعديلات عن المشروع القديم:
 * ──────────────────────────────────────────────────────────────────────
 * | القديم                     | الجديد
 * | store_id                   | created_by (نفس منطق المنتجات)
 * | foreign key → stores       | foreign key → users
 * ──────────────────────────────────────────────────────────────────────
 *
 * ملاحظة عن الحقول المكررة:
 * - sku: مكرر على products لكن موجود هنا للبحث السريع بدون JOIN
 * - created_by: مكرر على products لكن موجود هنا للتناسق
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_products', function (Blueprint $table) {
            $table->id();

            // ========================================================================
            // العلاقات
            // ========================================================================
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            // القديم: store_id → الجديد: created_by
            $table->unsignedBigInteger('created_by')->index()->comment('User ID who owns this health record');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            // ========================================================================
            // General Product Info
            // ========================================================================
            $table->string('sku')->nullable()->index()->comment('Duplicate from products for fast search');
            $table->string('product_form')->nullable()->comment('e.g., Caps, Liquid, Tablet, Powder');
            $table->string('bottle_size')->nullable();
            $table->string('bottle_size_unit')->nullable()->comment('e.g., caps, ml, oz, g');
            $table->text('product_image_url')->nullable()->comment('External image URL');

            // ========================================================================
            // Content & Descriptions
            // ========================================================================
            $table->json('primary_indications')->nullable()->comment('Array of primary indication names');
            $table->text('ingredients')->nullable();
            $table->text('contraindications')->nullable();
            $table->text('research_links')->nullable();

            // ========================================================================
            // Additional Info
            // ========================================================================
            $table->text('supports')->nullable()->comment('Body systems supported');
            $table->text('useful_for')->nullable()->comment('Conditions it is useful for');

            // ========================================================================
            // Dosing Schedule
            // ========================================================================
            $table->string('dosing_upon_rising')->nullable();
            $table->string('dosing_breakfast')->nullable();
            $table->string('dosing_between_meals_am')->nullable();
            $table->string('dosing_lunch')->nullable();
            $table->string('dosing_between_meals_pm')->nullable();
            $table->string('dosing_dinner')->nullable();
            $table->string('dosing_before_sleep')->nullable();
            $table->boolean('dosing_na')->default(false)->comment('If true, no dosing schedule applies');

            // ========================================================================
            // Full Name (more descriptive than product name)
            // ========================================================================
            $table->string('full_name')->nullable();

            $table->timestamps();

            // ========================================================================
            // Indexes
            // ========================================================================
            $table->index(['product_id', 'created_by']);
            $table->unique(['product_id', 'created_by'], 'product_creator_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_products');
    }
};
