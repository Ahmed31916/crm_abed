<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes the api_environment field from users table since the system
     * now uses production-only mode (no test/production toggle).
     * All API calls use VITAL_PROD_API_* credentials with RemoteManagementApiKey only.
     *
     * This migration replaces both:
     * - 2026_06_09_000001_add_api_environment_to_users_table.php (adds the column)
     * - 2026_06_10_000001_fix_api_environment_values.php (fixes empty values)
     *
     * Since we're removing the column entirely, we just drop it.
     */
    public function up(): void
    {
        // Drop the api_environment column if it exists
        if (Schema::hasColumn('users', 'api_environment')) {
            Schema::table('users', function ($table) {
                $table->dropColumn('api_environment');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function ($table) {
            $table->string('api_environment')->default('production')->after('hardware_id');
        });
    }
};
