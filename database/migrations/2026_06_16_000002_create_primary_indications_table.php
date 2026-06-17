<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * جدول الـ Primary Indications (المؤشرات الرئيسية)
     * يحتوي على كلا العمودين:
     *   - company_id : الشركة المالكة (للـ visibility scoping)
     *   - created_by : المستخدم الذي أنشأ السجل (audit trail)
     *
     * كلاهما يشير إلى users.id حيث type = 'company' أو 'superadmin' أو 'staff'
     */
    public function up(): void
    {
        Schema::create('primary_indications', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // company_id — الشركة المالكة (للـ visibility scoping)
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // created_by — المستخدم الذي أنشأ السجل (audit trail)
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->timestamps();

            // مفتاح فريد لمنع تكرار نفس الاسم لنفس الشركة
            $table->unique(['name', 'company_id']);
            $table->index('company_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('primary_indications');
    }
};
