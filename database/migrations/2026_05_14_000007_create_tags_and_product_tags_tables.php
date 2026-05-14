<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create tags table + product_tags pivot table
 *
 * تصميم الجداول:
 * ──────────────────────────────────────────────────────────────────────
 * | tags: جدول التاجات الأساسي
 * |   - id, name, slug, color (للعرض), created_by, timestamps
 * |   - created_by = معرف المستخدم اللي أنشأ التاج (سوبر أدمن أو شركة)
 * |   - كل تاج يتبع لصاحبه (سوبر أدمن أو شركة)
 * |
 * | product_tags: جدول pivot بين المنتجات والتاجات
 * |   - id, product_id, tag_id, created_by, timestamps
 * |   - created_by = مين ربط التاج بالمنتج
 * |   - هذا يسمح بنفس المنتج يكون له تاجات مختلفة لكل شركة
 * |   - الشركة تقدر تضيف تاجاتها الخاصة على منتجات السوبر أدمن
 * ──────────────────────────────────────────────────────────────────────
 */
return new class extends Migration
{
    public function up(): void
    {
        // ========================================================================
        // 1. جدول التاجات
        // ========================================================================
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('color', 7)->default('#6B7280')->comment('Hex color for UI display');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->index()->comment('User ID who created this tag');
            $table->timestamps();

            // كل مستخدم ما يقدر يعمل تاج بنفس الاسم مرتين
            $table->unique(['name', 'created_by']);

            // Foreign key
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // ========================================================================
        // 2. جدول pivot بين المنتجات والتاجات
        // ========================================================================
        Schema::create('product_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('tag_id');
            $table->unsignedBigInteger('created_by')->index()->comment('User ID who assigned this tag to the product');
            $table->timestamps();

            // منع التكرار: نفس التاج لا يُربط بنفس المنتج من نفس المستخدم مرتين
            $table->unique(['product_id', 'tag_id', 'created_by'], 'product_tag_user_unique');

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes لتحسين الأداء
            $table->index(['product_id', 'created_by']);
            $table->index(['tag_id', 'created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tags');
        Schema::dropIfExists('tags');
    }
};
