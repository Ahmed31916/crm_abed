<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('google_calendar_event_id')->nullable()->after('assigned_to');
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->string('google_calendar_event_id')->nullable()->after('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('google_calendar_event_id');
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn('google_calendar_event_id');
        });
    }
};
