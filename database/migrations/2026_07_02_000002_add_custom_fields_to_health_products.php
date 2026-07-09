<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add custom_primary_indications and custom_dosing_notes
 *            to health_products table
 *
 * ينقل حقلَي custom_primary_indications و custom_dosing_notes
 * من product_company_overrides إلى health_products.
 *
 * Run: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_products', function (Blueprint $table) {
            if (!Schema::hasColumn('health_products', 'custom_primary_indications')) {
                $table->json('custom_primary_indications')->nullable()->after('practitioner_notes');
            }
            if (!Schema::hasColumn('health_products', 'custom_dosing_notes')) {
                $table->text('custom_dosing_notes')->nullable()->after('custom_primary_indications');
            }
        });
    }

    public function down(): void
    {
        Schema::table('health_products', function (Blueprint $table) {
            if (Schema::hasColumn('health_products', 'custom_dosing_notes')) {
                $table->dropColumn('custom_dosing_notes');
            }
            if (Schema::hasColumn('health_products', 'custom_primary_indications')) {
                $table->dropColumn('custom_primary_indications');
            }
        });
    }
};
