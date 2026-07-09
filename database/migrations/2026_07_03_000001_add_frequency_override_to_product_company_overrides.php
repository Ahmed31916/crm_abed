<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add frequency_override column to product_company_overrides table
 *
 * يسمح للشركة بعمل override على حقل frequency لمنتجات السوبر ادمن.
 *
 * Run: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_company_overrides', function (Blueprint $table) {
            if (!Schema::hasColumn('product_company_overrides', 'frequency_override')) {
                $table->string('frequency_override')->nullable()->after('sale_price_override');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_company_overrides', function (Blueprint $table) {
            if (Schema::hasColumn('product_company_overrides', 'frequency_override')) {
                $table->dropColumn('frequency_override');
            }
        });
    }
};
