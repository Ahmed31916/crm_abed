<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds api_environment field to users table to track whether the user
     * registered from a test environment (desktop app sends env=test)
     * or production environment (no env parameter).
     * This determines which Vital API credentials to use for license operations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('api_environment')->default('production')->after('hardware_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('api_environment');
        });
    }
};
