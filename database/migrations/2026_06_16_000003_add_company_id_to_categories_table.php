<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * إضافة عمود company_id إلى جدول categories الموجود مسبقاً
     *
     * جدول categories حالياً يحتوي على created_by فقط.
     * نضيف company_id ليكون له نفس النمط المتبع في tags و primary_indications:
     *   - company_id : الشركة المالكة (للـ visibility scoping)
     *   - created_by : المستخدم الذي أنشأ السجل (audit trail)
     *
     * كلاهما يشير إلى users.id حيث type = 'company' أو 'superadmin' أو 'staff'
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // التحقق إن company_id غير موجود مسبقاً لتجنب الأخطاء
            if (!Schema::hasColumn('categories', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('created_by');
                $table->foreign('company_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->index(['company_id', 'status']);
            }
        });

        // نسخ قيمة created_by إلى company_id للسجلات الموجودة (backward compatibility)
        // هذا يضمن أن السجلات الحالية تحصل على قيمة company_id صحيحة
        \Illuminate\Support\Facades\DB::statement('UPDATE categories SET company_id = created_by WHERE company_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'company_id')) {
                // إزالة foreign key أولاً
                $table->dropForeign(['company_id']);
                // ثم إزالة الـ index لو موجود
                $table->dropIndex(['company_id', 'status']);
                // ثم إزالة العمود
                $table->dropColumn('company_id');
            }
        });
    }
};
