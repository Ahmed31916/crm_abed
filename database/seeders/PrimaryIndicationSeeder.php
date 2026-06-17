<?php

namespace Database\Seeders;

use App\Models\PrimaryIndication;
use App\Models\User;
use Illuminate\Database\Seeder;

class PrimaryIndicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * ينشئ مجموعة من المؤشرات الرئيسية الصحية للسوبر ادمن
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
            $this->command->warn('PrimaryIndicationSeeder: No super admin user found. Skipping.');
            return;
        }

        // قائمة المؤشرات الرئيسية الصحية الشائعة
        $indications = [
            // ========== الدماغ والأعصاب ==========
            'Cognitive Support',
            'Memory Enhancement',
            'Focus & Mental Clarity',
            'Mood Balance',
            'Stress Management',
            'Anxiety Relief',
            'Nervous System Support',

            // ========== النوم ==========
            'Sleep Quality',
            'Sleep Onset',
            'Restful Sleep',
            'Circadian Rhythm Support',

            // ========== الطاقة ==========
            'Energy Production',
            'Fatigue Reduction',
            'Athletic Performance',
            'Endurance Support',
            'Recovery Support',

            // ========== المناعة ==========
            'Immune System Support',
            'Antioxidant Defense',
            'Inflammatory Response',
            'Infection Defense',
            'Cold & Flu Prevention',

            // ========== الجهاز الهضمي ==========
            'Digestive Support',
            'Gut Microbiome',
            'Bowel Regularity',
            'Nutrient Absorption',
            'Liver Support',
            'Detoxification',

            // ========== القلب والأوعية ==========
            'Cardiovascular Health',
            'Blood Circulation',
            'Blood Pressure Support',
            'Cholesterol Management',
            'Vascular Integrity',

            // ========== العظام والمفاصل ==========
            'Bone Density',
            'Joint Mobility',
            'Cartilage Support',
            'Muscle Function',
            'Connective Tissue Health',

            // ========== التمثيل الغذائي ==========
            'Metabolic Support',
            'Blood Sugar Balance',
            'Weight Management',
            'Thyroid Support',
            'Insulin Sensitivity',

            // ========== الهرمونات ==========
            'Hormonal Balance',
            'Adrenal Support',
            'Menopause Support',
            'PMS Support',
            'Testosterone Support',

            // ========== البشرة والشعر ==========
            'Skin Health',
            'Collagen Production',
            'Wound Healing',
            'Hair Growth',
            'Nail Strength',

            // ========== التنفس ==========
            'Respiratory Health',
            'Sinus Support',
            'Lung Function',
            'Bronchial Support',

            // ========== العيون ==========
            'Eye Health',
            'Vision Support',
            'Macular Health',

            // ========== المسالك البولية ==========
            'Urinary Tract Health',
            'Kidney Support',
            'Bladder Health',

            // ========== عام ==========
            'Overall Wellness',
            'Anti-Aging',
            'Cellular Health',
            'Mitochondrial Support',
            'Hydration Support',
        ];

        $inserted = 0;
        foreach ($indications as $name) {
            $created = PrimaryIndication::firstOrCreate(
                [
                    'name'       => $name,
                    'company_id' => $superAdminId,
                ],
                [
                    'created_by' => $superAdminId,   // audit trail
                ]
            );

            if ($created->wasRecentlyCreated) {
                $inserted++;
            }
        }

        $this->command->info("PrimaryIndicationSeeder: Inserted {$inserted} new indications (total: " . count($indications) . ").");
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
