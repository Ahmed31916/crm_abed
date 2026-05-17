<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * جدول مقارنة المنتجات - يخزن الفروقات بين البيانات المحلية وبيانات المزود
     * يستخدم من السوبر ادمن فقط
     */
    public function up(): void
    {
        Schema::create('product_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->index();
            $table->string('product_name');
            $table->string('field_name');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending')->index();
            $table->timestamps();

            // Index for faster queries
            $table->index(['product_id', 'status']);
            $table->index('field_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_comparisons');
    }
};
