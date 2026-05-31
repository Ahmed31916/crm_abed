<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('license_key')->nullable()->after('plan_is_active');
            $table->uuid('license_id')->nullable()->after('license_key');
            $table->string('subscription_type')->default('free')->after('license_id'); // 'free' or 'subscription'
            $table->string('subscription_duration')->nullable()->after('subscription_type'); // 'monthly' or 'yearly'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['license_key', 'license_id', 'subscription_type', 'subscription_duration']);
        });
    }
};
