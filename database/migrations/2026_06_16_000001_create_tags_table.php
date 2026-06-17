<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * جدول الـ Tags (الوسوم)
     * يحتوي على كلا العمودين:
     *   - company_id : الشركة المالكة للـ Tag (للـ visibility scoping)
     *   - created_by : المستخدم الذي أنشأ السجل (audit trail)
     *
     * كلاهما يشير إلى users.id حيث type = 'company' أو 'superadmin' أو 'staff'
     */
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active');

            // company_id — الشركة المالكة (للـ visibility scoping)
            
            $table->foreignId('company_id')->nullable()->constrained('users')->cascadeOnDelete();

            // مفتاح فريد لمنع تكرار نفس الاسم لنفس الشركة
            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'status']);
 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
