<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add practitioner_notes column to health_products table
 *
 * ينقل حقل practitioner_notes من product_company_overrides إلى health_products
 * ليكون مرتبطاً بالمنتج نفسه وليس بـ override شركة معينة.
 *
 * Run: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_products', function (Blueprint $table) {
            if (!Schema::hasColumn('health_products', 'practitioner_notes')) {
                $table->text('practitioner_notes')->nullable()->after('useful_for');
            }
        });
    }

    public function down(): void
    {
        Schema::table('health_products', function (Blueprint $table) {
            if (Schema::hasColumn('health_products', 'practitioner_notes')) {
                $table->dropColumn('practitioner_notes');
            }
        });
    }
};
