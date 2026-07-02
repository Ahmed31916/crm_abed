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
            'Nervous System',
            'Liver / Kidney / Spleen',
            'Ear / Nose / Throat',
            'Gut / Brain Axis'
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
