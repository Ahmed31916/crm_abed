<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * ينشئ مجموعة من الـ Tags الصحية الأساسية للسوبر ادمن
     * لتكون متاحة لكل الشركات
     *
     * يملأ كلا العمودين:
     *   - company_id : الشركة المالكة (السوبر ادمن)
     *   - created_by : المستخدم الذي أنشأ السجل (السوبر ادمن)
     */
    public function run(): void
    {
        // الحصول على معرّف السوبر ادمن
        $superAdminId = $this->getSuperAdminId();

        if (!$superAdminId) {
            $this->command->warn('TagSeeder: No super admin user found. Skipping.');
            return;
        }

        // قائمة الـ Tags الصحية
        $tags = [
            // ========== الدماغ والأعصاب ==========
            ['name' => 'Brain Health',       'color' => '#3b82f6'],
            ['name' => 'Memory Support',     'color' => '#3b82f6'],
            ['name' => 'Focus & Concentration', 'color' => '#3b82f6'],
            ['name' => 'Mood Support',       'color' => '#8b5cf6'],
            ['name' => 'Stress Relief',      'color' => '#8b5cf6'],
            ['name' => 'Anxiety Support',    'color' => '#8b5cf6'],

            // ========== النوم ==========
            ['name' => 'Sleep Support',      'color' => '#6366f1'],
            ['name' => 'Relaxation',         'color' => '#6366f1'],

            // ========== الطاقة والمناعة ==========
            ['name' => 'Energy Boost',       'color' => '#f59e0b'],
            ['name' => 'Immune Support',     'color' => '#10b981'],
            ['name' => 'Antioxidant',        'color' => '#10b981'],
            ['name' => 'Anti-Inflammatory',  'color' => '#ef4444'],

            // ========== الجهاز الهضمي ==========
            ['name' => 'Digestive Health',   'color' => '#14b8a6'],
            ['name' => 'Gut Health',         'color' => '#14b8a6'],
            ['name' => 'Probiotic',          'color' => '#14b8a6'],
            ['name' => 'Detox Support',      'color' => '#14b8a6'],

            // ========== القلب والأوعية ==========
            ['name' => 'Heart Health',       'color' => '#dc2626'],
            ['name' => 'Circulation',        'color' => '#dc2626'],
            ['name' => 'Blood Pressure',     'color' => '#dc2626'],
            ['name' => 'Cholesterol',        'color' => '#dc2626'],

            // ========== العظام والمفاصل ==========
            ['name' => 'Bone Health',        'color' => '#92400e'],
            ['name' => 'Joint Support',      'color' => '#92400e'],
            ['name' => 'Muscle Recovery',    'color' => '#92400e'],

            // ========== النساء والرجال ==========
            ['name' => "Women's Health",     'color' => '#ec4899'],
            ['name' => "Men's Health",       'color' => '#0ea5e9'],
            ['name' => 'Hormonal Balance',   'color' => '#ec4899'],

            // ========== البشرة والشعر ==========
            ['name' => 'Skin Health',        'color' => '#f97316'],
            ['name' => 'Hair Health',        'color' => '#f97316'],
            ['name' => 'Anti-Aging',         'color' => '#f97316'],

            // ========== التمثيل الغذائي ==========
            ['name' => 'Weight Management',  'color' => '#84cc16'],
            ['name' => 'Blood Sugar',        'color' => '#84cc16'],
            ['name' => 'Metabolism',         'color' => '#84cc16'],

            // ========== الفيتامينات والمعادن ==========
            ['name' => 'Vitamin C',          'color' => '#f59e0b'],
            ['name' => 'Vitamin D',          'color' => '#f59e0b'],
            ['name' => 'Vitamin B Complex',  'color' => '#f59e0b'],
            ['name' => 'Magnesium',          'color' => '#f59e0b'],
            ['name' => 'Zinc',               'color' => '#f59e0b'],
            ['name' => 'Iron',               'color' => '#f59e0b'],
            ['name' => 'Omega-3',            'color' => '#06b6d4'],
            ['name' => 'Calcium',            'color' => '#f59e0b'],
        ];

        $inserted = 0;
        foreach ($tags as $tag) {
            // firstOrCreate لتجنب التكرار
            $created = Tag::firstOrCreate(
                [
                    'name'       => $tag['name'],
                    'company_id' => $superAdminId,
                ],
                [
                    'slug'       => Str::slug($tag['name']),
                    'color'      => $tag['color'],
                    'status'     => 'active',
                    'created_by' => $superAdminId,   // audit trail
                ]
            );

            if ($created->wasRecentlyCreated) {
                $inserted++;
            }
        }

        $this->command->info("TagSeeder: Inserted {$inserted} new tags (total: " . count($tags) . ").");
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
