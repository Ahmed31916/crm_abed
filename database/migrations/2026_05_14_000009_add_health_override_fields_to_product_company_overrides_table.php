<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add health override fields to product_company_overrides table
 *
 * في المشروع القديم كان ProductMerchantOverride يحتوي على حقول health override
 * (description, contraindications, research_links, dosing, practitioner_notes, etc.)
 *
 * المشروع الجديد كان عنده بس price/stock overrides
 * الآن نضيف حقول الـ health override عشان الـ API يكون مطابق تماماً
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_company_overrides', function (Blueprint $table) {
            // ========================================================================
            // Health Product Override Fields
            // نفس الحقول اللي كانت في ProductMerchantOverride القديم
            // ========================================================================

            // وصف المنتج override
            $table->text('description')->nullable()->after('is_visible');

            // الروابط والتحذيرات
            $table->text('contraindications')->nullable()->after('description');
            $table->text('research_links')->nullable()->after('contraindications');

            // التصنيف override
            $table->unsignedBigInteger('category_id')->nullable()->after('research_links');

            // ملاحظات الممارس (Practitioner Notes) - حصري للشركة
            $table->text('practitioner_notes')->nullable()->after('category_id');
            $table->json('custom_primary_indications')->nullable()->after('practitioner_notes');
            $table->text('custom_dosing_notes')->nullable()->after('custom_primary_indications');

            // الدلالات الرئيسية override
            $table->json('primary_indications')->nullable()->after('custom_dosing_notes');

            // جدول الجرعات override
            $table->string('dosing_upon_rising')->nullable()->after('primary_indications');
            $table->string('dosing_breakfast')->nullable()->after('dosing_upon_rising');
            $table->string('dosing_between_meals_am')->nullable()->after('dosing_breakfast');
            $table->string('dosing_lunch')->nullable()->after('dosing_between_meals_am');
            $table->string('dosing_between_meals_pm')->nullable()->after('dosing_lunch');
            $table->string('dosing_dinner')->nullable()->after('dosing_between_meals_pm');
            $table->string('dosing_before_sleep')->nullable()->after('dosing_dinner');
            $table->boolean('dosing_na')->nullable()->after('dosing_before_sleep');
        });
    }

    public function down(): void
    {
        Schema::table('product_company_overrides', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'contraindications',
                'research_links',
                'category_id',
                'practitioner_notes',
                'custom_primary_indications',
                'custom_dosing_notes',
                'primary_indications',
                'dosing_upon_rising',
                'dosing_breakfast',
                'dosing_between_meals_am',
                'dosing_lunch',
                'dosing_between_meals_pm',
                'dosing_dinner',
                'dosing_before_sleep',
                'dosing_na',
            ]);
        });
    }
};
