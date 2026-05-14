<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * TagSeeder - بيانات وهمية للتاجات
 *
 * يُنشئ:
 * 1. تاجات للسوبر أدمن (تاجات مشتركة لكل الشركات)
 * 2. تاجات خاصة بكل شركة
 * 3. ربط التاجات بالمنتجات (product_tags pivot)
 */
class TagSeeder extends Seeder
{
    /**
     * ألوان متنوعة للتاجات
     */
    private array $colors = [
        '#EF4444', // Red
        '#F97316', // Orange
        '#F59E0B', // Amber
        '#84CC16', // Lime
        '#22C55E', // Green
        '#14B8A6', // Teal
        '#06B6D4', // Cyan
        '#3B82F6', // Blue
        '#6366F1', // Indigo
        '#8B5CF6', // Violet
        '#A855F7', // Purple
        '#EC4899', // Pink
        '#6B7280', // Gray
    ];

    /**
     * تاجات السوبر أدمن (تاجات مشتركة عامة)
     */
    private array $superAdminTags = [
        // تصنيفات عامة
        ['name' => 'Featured',       'description' => 'Featured and highlighted products'],
        ['name' => 'New Arrival',    'description' => 'Recently added products'],
        ['name' => 'Best Seller',    'description' => 'Top selling products'],
        ['name' => 'On Sale',        'description' => 'Products currently on sale'],
        ['name' => 'Limited Stock',  'description' => 'Products with limited availability'],

        // فئات الاستخدام
        ['name' => 'For Home',       'description' => 'Products for home use'],
        ['name' => 'For Office',     'description' => 'Products for office use'],
        ['name' => 'Professional',   'description' => 'Professional grade products'],
        ['name' => 'Eco Friendly',   'description' => 'Environmentally friendly products'],
        ['name' => 'Premium',        'description' => 'Premium quality products'],

        // حالة التوفر
        ['name' => 'In Stock',       'description' => 'Currently available'],
        ['name' => 'Pre Order',      'description' => 'Available for pre-order'],
        ['name' => 'Coming Soon',    'description' => 'Will be available soon'],
    ];

    /**
     * تاجات خاصة بالشركات (لكل شركة تاجاتها)
     */
    private array $companyTags = [
        ['name' => 'Staff Pick',         'description' => 'Recommended by our staff'],
        ['name' => 'Customer Favorite',  'description' => 'Loved by our customers'],
        ['name' => 'Exclusive',          'description' => 'Exclusive to our store'],
        ['name' => 'Bundle Deal',        'description' => 'Available in bundle deals'],
        ['name' => 'Clearance',          'description' => 'Clearance sale items'],
        ['name' => 'Top Rated',          'description' => 'Highly rated by customers'],
        ['name' => 'Recommended',        'description' => 'Our recommendation'],
        ['name' => 'Value Pack',         'description' => 'Great value for money'],
    ];

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $superAdmin = User::where('type', 'superadmin')
            ->orWhere('type', 'super admin')
            ->first();

        if (!$superAdmin) {
            $this->command->warn('No super admin found. Skipping super admin tags.');
            return;
        }

        $superAdminId = $superAdmin->id;

        // ========================================================================
        // 1. إنشاء تاجات السوبر أدمن
        // ========================================================================
        $this->command->info('Creating super admin tags...');

        foreach ($this->superAdminTags as $index => $tagData) {
            Tag::create([
                'name'        => $tagData['name'],
                'color'       => $this->colors[$index % count($this->colors)],
                'description' => $tagData['description'],
                'created_by'  => $superAdminId,
            ]);
        }

        $this->command->info('Created ' . count($this->superAdminTags) . ' super admin tags.');

        // ========================================================================
        // 2. إنشاء تاجات لكل شركة
        // ========================================================================
        $companies = User::where('type', 'company')->get();

        foreach ($companies as $company) {
            $this->command->info("Creating tags for company: {$company->name}");

            $companyColorOffset = $company->id * 3;

            foreach ($this->companyTags as $index => $tagData) {
                Tag::create([
                    'name'        => $tagData['name'],
                    'color'       => $this->colors[($companyColorOffset + $index) % count($this->colors)],
                    'description' => $tagData['description'],
                    'created_by'  => $company->id,
                ]);
            }
        }

        $this->command->info('Created tags for ' . $companies->count() . ' companies.');

        // ========================================================================
        // 3. ربط التاجات بالمنتجات (product_tags pivot)
        // ========================================================================
        $this->command->info('Attaching tags to products...');

        // منتجات السوبر أدمن
        $superAdminProducts = Product::where('created_by', $superAdminId)->get();
        $superAdminTagIds = Tag::where('created_by', $superAdminId)->pluck('id')->toArray();

        foreach ($superAdminProducts as $product) {
            // كل منتج سوبر أدمن يربط بـ 2-4 تاجات عشوائية
            $randomTagIds = $this->getRandomElements($superAdminTagIds, rand(2, 4));

            foreach ($randomTagIds as $tagId) {
                $this->insertProductTag($product->id, $tagId, $superAdminId);
            }

            // كل شركة تربط تاجاتها الخاصة على منتجات السوبر أدمن
            foreach ($companies as $company) {
                $companyTagIds = Tag::where('created_by', $company->id)->pluck('id')->toArray();

                if (!empty($companyTagIds)) {
                    $randomCompanyTags = $this->getRandomElements($companyTagIds, rand(1, 3));

                    foreach ($randomCompanyTags as $tagId) {
                        $this->insertProductTag($product->id, $tagId, $company->id);
                    }
                }
            }
        }

        // منتجات كل شركة
        foreach ($companies as $company) {
            $companyProducts = Product::where('created_by', $company->id)->get();
            $companyTagIds = Tag::where('created_by', $company->id)->pluck('id')->toArray();

            foreach ($companyProducts as $product) {
                if (!empty($companyTagIds)) {
                    $randomTags = $this->getRandomElements($companyTagIds, rand(1, 3));

                    foreach ($randomTags as $tagId) {
                        $this->insertProductTag($product->id, $tagId, $company->id);
                    }
                }

                // ربط تاجات السوبر أدمن بمنتجات الشركة (اختياري)
                if (!empty($superAdminTagIds)) {
                    $randomSuperTags = $this->getRandomElements($superAdminTagIds, rand(0, 2));

                    foreach ($randomSuperTags as $tagId) {
                        $this->insertProductTag($product->id, $tagId, $company->id);
                    }
                }
            }
        }

        $this->command->info('Tag seeder completed successfully!');
    }

    /**
     * إدخال سجل في product_tags بدون تكرار
     */
    private function insertProductTag(int $productId, int $tagId, int $createdBy): void
    {
        \DB::table('product_tags')->insertOrIgnore([
            'product_id' => $productId,
            'tag_id'     => $tagId,
            'created_by' => $createdBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * جلب عناصر عشوائية من مصفوفة
     */
    private function getRandomElements(array $array, int $count): array
    {
        if (empty($array)) {
            return [];
        }

        $count = min($count, count($array));
        $keys = array_rand($array, $count);

        if ($count === 1) {
            return [$array[$keys]];
        }

        return array_map(fn($key) => $array[$key], (array) $keys);
    }
}
