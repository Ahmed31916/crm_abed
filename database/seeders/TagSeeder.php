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
     * ينشئ قائمة التاجز الصحية النهائية (Final Tag Names)
     * للسوبر ادمن، لتكون متاحة لكل الشركات.
     *
     * - company_id : السوبر ادمن (المالك)
     * - created_by : السوبر ادمن (منشئ السجل)
     *
     * آمن للتشغيل المتكرر: يستخدم firstOrCreate لتجنب التكرار.
     */
    public function run(): void
    {
        $superAdminId = $this->getSuperAdminId();

        if (!$superAdminId) {
            $this->command->warn('TagSeeder: No super admin user found. Skipping.');
            return;
        }

        // ============================================================
        // قائمة التاجز النهائية (Final Tag Names)
        // ============================================================
        $tags = [
            // ========== أساسي / Metabolic ==========
            ['name' => 'Blood Sugar',           'color' => '#84cc16'],
            ['name' => 'Adrenal',               'color' => '#f59e0b'],
            ['name' => 'Hormone',               'color' => '#ec4899'],
            ['name' => 'Thyroid',               'color' => '#0ea5e9'],
            ['name' => 'Energy',                'color' => '#f59e0b'],
            ['name' => 'Mitochondrial',         'color' => '#f59e0b'],

            // ========== المناعة والالتهاب ==========
            ['name' => 'Immune',                'color' => '#10b981'],
            ['name' => 'Antioxidant',           'color' => '#10b981'],
            ['name' => 'Anti-Inflammatory',     'color' => '#ef4444'],
            ['name' => 'Anti-Aging',            'color' => '#f97316'],
            ['name' => 'Bacterial',             'color' => '#10b981'],
            ['name' => 'Parasites',             'color' => '#10b981'],
            ['name' => 'Lyme',                  'color' => '#10b981'],
            ['name' => 'Mold and Fungal',       'color' => '#10b981'],

            // ========== الجهاز الهضمي والكبد ==========
            ['name' => 'Gut and Digestion',     'color' => '#14b8a6'],
            ['name' => 'Liver and Detox',       'color' => '#14b8a6'],
            ['name' => 'Detox Support',         'color' => '#14b8a6'],
            ['name' => 'Food Allergies',        'color' => '#14b8a6'],

            // ========== القلب والأوعية ==========
            ['name' => 'Blood Pressure',        'color' => '#dc2626'],
            ['name' => 'Circulation',           'color' => '#dc2626'],

            // ========== العظام والعضلات ==========
            ['name' => 'Bone and Joints',       'color' => '#92400e'],
            ['name' => 'Musculoskeletal',       'color' => '#92400e'],
            ['name' => 'Pain and Inflammation', 'color' => '#92400e'],

            // ========== النساء والرجال ==========
            ['name' => 'Female Reproductive',   'color' => '#ec4899'],
            ['name' => 'Male Reproductive',     'color' => '#0ea5e9'],

            // ========== الدماغ والأعصاب ==========
            ['name' => 'Nervous System',        'color' => '#8b5cf6'],
            ['name' => 'Anxiety Support',       'color' => '#8b5cf6'],
            ['name' => 'Emotional',             'color' => '#8b5cf6'],
            ['name' => 'Sleep and Relaxation',  'color' => '#6366f1'],

            // ========== العيون والفم ==========
            ['name' => 'Eye Health',            'color' => '#3b82f6'],
            ['name' => 'Oral Health',           'color' => '#3b82f6'],

            // ========== السموم والمعادن ==========
            ['name' => 'Environmental Toxins',  'color' => '#14b8a6'],
            ['name' => 'Heavy Metals',          'color' => '#14b8a6'],

            // ========== الجهاز التنفسي والليمفاوي ==========
            ['name' => 'Respiratory',           'color' => '#06b6d4'],
            ['name' => 'Sinus',                 'color' => '#06b6d4'],
            ['name' => 'Lymphatic',             'color' => '#06b6d4'],

            // ========== البشرة ==========
            ['name' => 'Skin',                  'color' => '#f97316'],

            // ========== الخلايا ==========
            ['name' => 'Cellular Health',       'color' => '#3b82f6'],
        ];

        $inserted = 0;
        $skipped  = 0;

        foreach ($tags as $tag) {
            $created = Tag::firstOrCreate(
                [
                    'name'       => $tag['name'],
                    'company_id' => $superAdminId,
                ],
                [
                    'slug'       => Str::slug($tag['name']),
                    'color'      => $tag['color'],
                    'status'     => 'active',
                    'created_by' => $superAdminId,
                ]
            );

            if ($created->wasRecentlyCreated) {
                $inserted++;
            } else {
                $skipped++;
            }
        }

        $this->command->info(
            "TagSeeder: Inserted {$inserted} new tags, skipped {$skipped} existing (total: " . count($tags) . ")."
        );
    }

    /**
     * الحصول على معرّف السوبر ادمن
     */
    private function getSuperAdminId(): ?int
    {
        if (function_exists('getSuperAdminCompanyId')) {
            $id = getSuperAdminCompanyId();
            if ($id) {
                return $id;
            }
        }

        $superAdmin = User::whereIn('type', ['superadmin', 'super admin'])->first();
        return $superAdmin?->id;
    }
}