<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * ينشئ التصنيفات الصحية الأساسية للسوبر ادمن
     * لتكون متاحة لكل الشركات
     *
     * يملأ كلا العمودين:
     *   - company_id : الشركة المالكة (السوبر ادمن) — مضاف حديثاً
     *   - created_by : المستخدم الذي أنشأ السجل (السوبر ادمن) — موجود مسبقاً
     */
    public function run(): void
    {
        // الحصول على معرّف السوبر ادمن
        $superAdminId = $this->getSuperAdminId();

        if (!$superAdminId) {
            $this->command->warn('CategorySeeder: No super admin user found. Skipping.');
            return;
        }

        // قائمة التصنيفات الصحية
        $categories = [
            // ========== تصنيفات رئيسية ==========
            'Brain & Cognitive',
            'Sleep & Relaxation',
            'Energy & Vitality',
            'Immune Support',
            'Digestive Health',
            'Heart & Cardiovascular',
            'Bone & Joint',
            'Hormonal Balance',
            'Skin, Hair & Nails',
            'Weight Management',
            'Respiratory Health',
            'Eye Health',
            'Urinary Health',
            'Vitamins & Minerals',
            'Amino Acids',
            'Fatty Acids & Oils',
            'Herbal Supplements',
            'Probiotics',
            'Antioxidants',
            'Anti-Aging',
            'Detox & Cleanse',
            'Sports Nutrition',
            "Women's Health",
            "Men's Health",
            "Children's Health",
            'Senior Health',
            'Homeopathy',
            'Essential Oils',
            'Tinctures',
            'Topical Solutions',
        ];

        $inserted = 0;
        foreach ($categories as $name) {
            $slug = Str::slug($name);

            // ضمان عدم تكرار الـ slug
            $originalSlug = $slug;
            $counter = 1;
            while (Category::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            $created = Category::firstOrCreate(
                [
                    'name'       => $name,
                    'created_by' => $superAdminId,
                ],
                [
                    'slug'       => $slug,
                    'status'     => 'active',
                    'company_id' => $superAdminId,   // مضاف حديثاً
                ]
            );

            if ($created->wasRecentlyCreated) {
                $inserted++;
            } else {
                // للسجلات الموجودة مسبقاً: تأكد إن company_id مضبوط
                if (empty($created->company_id)) {
                    $created->company_id = $superAdminId;
                    $created->save();
                }
            }
        }

        $this->command->info("CategorySeeder: Inserted {$inserted} new categories (total: " . count($categories) . ").");
    }

    /**
     * الحصول على معرّف السوبر ادمن
     * يبحث عن user حيث type = 'superadmin' أو 'super admin'
     */
    private function getSuperAdminId(): ?int
    {
        // إذا كانت الدالة getSuperAdminCompanyId() موجودة (من helpers)
        if (function_exists('getSuperAdminCompanyId')) {
            $id = getSuperAdminCompanyId();
            if ($id) {
                return $id;
            }
        }

        // وإلا، ابحث في جدول users
        $superAdmin = User::whereIn('type', ['superadmin', 'super admin'])->first();
        return $superAdmin?->id;
    }
}
