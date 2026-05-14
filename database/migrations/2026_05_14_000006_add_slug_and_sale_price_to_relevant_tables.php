<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add slug to users table + sale_price to products table
 *
 * التعديلات المطلوبة لتشغيل الـ API العام:
 *
 * 1. إضافة حقل slug لجدول users (للتعرف على الشركة عبر URL)
 *    - القديم: جدول stores كان فيه slug
 *    - الجديد: جدول users يحتاج slug للشركات
 *
 * 2. إضافة حقل sale_price لجدول products (لدعم الخصومات)
 *    - القديم: كان موجود على products table
 *    - الجديد: لازم نضيفه لأن migration الأصلي ما فيه
 */
return new class extends Migration
{
    public function up(): void
    {
        // ========================================================================
        // 1. إضافة slug لجدول users
        // ========================================================================
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        // توليد slug للشركات الموجودة
        $companies = \App\Models\User::where('type', 'company')->get();
        foreach ($companies as $company) {
            $slug = \Illuminate\Support\Str::slug($company->name);
            $originalSlug = $slug;
            $count = 1;

            while (\App\Models\User::where('slug', $slug)->where('id', '!=', $company->id)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }

            $company->slug = $slug;
            $company->save();
        }

        // توليد slug للسوبر أدمن
        $superAdmins = \App\Models\User::where('type', 'superadmin')
            ->orWhere('type', 'super admin')
            ->get();
        foreach ($superAdmins as $admin) {
            $slug = \Illuminate\Support\Str::slug($admin->name);
            $originalSlug = $slug;
            $count = 1;

            while (\App\Models\User::where('slug', $slug)->where('id', '!=', $admin->id)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }

            $admin->slug = $slug;
            $admin->save();
        }

        // ========================================================================
        // 2. إضافة sale_price لجدول products
        // ========================================================================
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('sale_price', 15, 2)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sale_price');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
