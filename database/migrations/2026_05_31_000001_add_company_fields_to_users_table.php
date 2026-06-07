<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->unsignedBigInteger('country_id')->nullable()->after('phone');
            $table->string('hardware_id')->nullable()->after('country_id');

            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropColumn(['company_name', 'phone', 'country_id', 'hardware_id', 'license_key']);
        });
    }
};