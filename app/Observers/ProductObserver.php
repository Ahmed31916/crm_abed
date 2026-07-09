<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductEventOutbox;
use App\Models\ProductCompanyOverride;
use App\Models\HealthProduct;
use App\Models\PrimaryIndication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

 
class ProductObserver
{
    /**
     * بناء البيانات الكاملة للمنتج لإرسالها عبر RabbitMQ.
     *
     * نفس signature القديم — التوافق الخلفي محفوظ 100%.
     */
    public static function buildProductPayload(Product $product, ?int $overrideCompanyId = null): array
    {
        $superAdminCompanyId = getSuperAdminCompanyId();
        $currentCompanyId = $overrideCompanyId ?? (int) $product->created_by;

        $isOverrideMode = $overrideCompanyId !== null
            && (int) $overrideCompanyId !== (int) $product->created_by;

        $price = (double) $product->price;
        $salePrice = (double) ($product->sale_price ?? 0);
        $hasDiscount = ($salePrice > 0 && $salePrice < $price);
        $discountPercentage = $hasDiscount ? round((($price - $salePrice) / $price) * 100, 2) : 0;

        // ==================== تحميل بيانات المنتج الصحي ====================
        $healthProduct = HealthProduct::getForProduct($product->id, $currentCompanyId);

        // ==================== تحميل اسم التصنيف ====================
        $categoryName = '';
        if ($product->category_id) {
            $category = DB::table('categories')->where('id', $product->category_id)->first();
            $categoryName = $category ? $category->name : '';
        }

        // ==================== تحميل اسم المورد ====================
        $supplierName = null;
        if ($product->brand_id) {
            $brand = DB::table('brands')->where('id', $product->brand_id)->first();
            $supplierName = $brand ? $brand->name : null;
        }

        // ==================== تحميل اسم الممارس ====================
        $practitioner = null;
        $practitionerSlug = null;
        $companyUser = User::where('id', $currentCompanyId)->first();
        if ($companyUser) {
            $practitioner = $companyUser->name;
            $practitionerSlug = $companyUser->slug ?? null;
        }

        // ==================== Tags - مع دعم company priority ====================
        $tags = [];
        $companyTags = DB::table('product_tags')
            ->where('product_id', $product->id)
            ->where('created_by', $currentCompanyId)
            ->pluck('tag_id')
            ->toArray();

        if (!empty($companyTags)) {
            $tags = DB::table('tags')->whereIn('id', $companyTags)->pluck('name')->toArray();
        } else {
            $superAdminTags = DB::table('product_tags')
                ->where('product_id', $product->id)
                ->where('created_by', $superAdminCompanyId)
                ->pluck('tag_id')
                ->toArray();
            if (!empty($superAdminTags)) {
                $tags = DB::table('tags')->whereIn('id', $superAdminTags)->pluck('name')->toArray();
            }
        }

        // ═══════════════════════════════════════════════════════════════════════
        // ⚡ UPDATED: Primary Indications - مع دعم company priority
        // ═══════════════════════════════════════════════════════════════════════
        // القديم: كان يقرأ من health_products.primary_indications (JSON)
        // الجديد: يقرأ من override->primary_indications أو من العلاقة belongsToMany
        $primaryIndications = [];

        // 1) ابحث عن override للشركة
        $override = ProductCompanyOverride::where('product_id', $product->id)
            ->where('company_id', $currentCompanyId)
            ->first();

        if ($override && $override->primary_indications !== null) {
            // الـ override يخزّن array of names
            $primaryIndications = is_array($override->primary_indications)
                ? array_values($override->primary_indications)
                : (json_decode($override->primary_indications, true) ?? []);
        } else {
            // 2) fallback: اقرأ من العلاقة belongsToMany الجديدة
            //    نستخدم join مباشرة لتسريع الأداء في الـ payload building
            $primaryIndications = DB::table('product_primary_indications')
                ->join('primary_indications', 'primary_indications.id', '=', 'product_primary_indications.primary_indication_id')
                ->where('product_primary_indications.product_id', $product->id)
                ->orderBy('primary_indications.name')
                ->pluck('primary_indications.name')
                ->toArray();
        }
        // ═══════════════════════════════════════════════════════════════════════

        // ==================== Pairs Well With ====================
        $pairsWellWith = [];
        $pairedProducts = $product->pairsWellWith()->get();
        foreach ($pairedProducts as $pair) {
            $pairHealth = HealthProduct::getForProduct($pair->id, $currentCompanyId);
            $pairsWellWith[] = [
                'id'   => $pair->id,
                'name' => $pair->name,
                'sku'  => $pairHealth ? $pairHealth->sku : null,
            ];
        }

        // ==================== Dosing Schedule ====================
        $dosingSchedule = null;
        if ($healthProduct && !$healthProduct->dosing_na) {
            $tempDosing = [];
            if (!empty($healthProduct->dosing_upon_rising))      $tempDosing['upon_rising']      = $healthProduct->dosing_upon_rising;
            if (!empty($healthProduct->dosing_breakfast))        $tempDosing['breakfast']        = $healthProduct->dosing_breakfast;
            if (!empty($healthProduct->dosing_between_meals_am)) $tempDosing['between_meals_am'] = $healthProduct->dosing_between_meals_am;
            if (!empty($healthProduct->dosing_lunch))            $tempDosing['lunch']            = $healthProduct->dosing_lunch;
            if (!empty($healthProduct->dosing_between_meals_pm)) $tempDosing['between_meals_pm'] = $healthProduct->dosing_between_meals_pm;
            if (!empty($healthProduct->dosing_dinner))           $tempDosing['dinner']           = $healthProduct->dosing_dinner;
            if (!empty($healthProduct->dosing_before_sleep))     $tempDosing['before_sleep']     = $healthProduct->dosing_before_sleep;

            if (!empty($tempDosing)) {
                $dosingSchedule = $tempDosing;
            }
        }

        // ==================== Image URL ====================
        $imageUrl = !empty($healthProduct?->product_image_url)
            ? $healthProduct->product_image_url
            : $product->getMainImageUrlAttribute();

        // ==================== Build Final Payload ====================
        return [
            'id'                        => $product->id,
            'sku'                       => $healthProduct ? $healthProduct->sku : null,
            'full_name'                 => $healthProduct ? $healthProduct->full_name : null,
            'name'                      => (string) $product->name,
            'slug'                      => $product->slug ?? null,
            'practitioner'              => $practitioner,
            'practitioner_slug'         => $practitionerSlug,
            'description'               => strip_tags($product->description ?? ''),
            'category'                  => $categoryName,
            'category_id'               => $product->category_id ?? null,

            'regular_price'             => $price,
            'sale_price'                => $salePrice > 0 ? $salePrice : null,
            'price'                     => $hasDiscount ? $salePrice : $price,
            'has_discount'              => $hasDiscount,
            'discount_percentage'       => $discountPercentage,

            'image_url'                 => $imageUrl,
            'is_active'                 => ($product->status === 'active'),
            'stock_status'              => $product->stock_status ?? 'in_stock',
            'tags'                      => array_values($tags),

            'frequency'                 => $product->frequency ?? null,
            'supplier'                  => $supplierName,
            'pairs_well_with'           => $pairsWellWith,

            'ingredients'               => $healthProduct->ingredients ?? null,
            'bottle_size'               => $healthProduct->bottle_size ?? null,
            'bottle_size_unit'          => $healthProduct->bottle_size_unit ?? null,
            'product_form'              => ($healthProduct->bottle_size_unit ?? '') == 'caps' ? 'Caps' : 'Liquid',
            'primary_indications'       => $primaryIndications,
            'supports'                  => $healthProduct->supports ?? null,
            'useful_for'                => $healthProduct->useful_for ?? null,
            'contraindications'         => $healthProduct->contraindications ?? null,
            'research'                  => $healthProduct->research_links ?? null,

            'practitioner_notes'                => $override->practitioner_notes ?? null,
            'custom_primary_indications'        => $override ? ($override->custom_primary_indications ?? []) : [],
            'custom_dosing_notes'               => $override->custom_dosing_notes ?? null,

            'dosing_schedule'           => $dosingSchedule,
            'dosing_na'                 => (bool) ($healthProduct->dosing_na ?? false),
            'message_id'                => $product->message_id,

            'company_slug'              => $practitionerSlug,
            'override_mode'             => $isOverrideMode,
            'owner_company_id'          => (int) $product->created_by,
            'acting_company_id'         => (int) $currentCompanyId,
        ];
    }

    /**
     * إرسال حدث المنتج إلى الـ Outbox يدوياً.
     * نفس signature القديم — التوافق الخلفي محفوظ.
     */
    public static function dispatchProductEvent(int $productId, string $action, array $extra = []): void
    {
        try {
            $product = Product::find($productId);
            if (!$product) {
                Log::error("ProductObserver::dispatchProductEvent: Product not found", [
                    'product_id' => $productId,
                    'action'     => $action,
                ]);
                return;
            }

            $product->increment('message_id');
            $product->refresh();

            $overrideCompanyId = $extra['override_company_id'] ?? null;
            $payload = self::buildProductPayload($product, $overrideCompanyId);
            $payload['action'] = $action;

            if (array_key_exists('company_slug', $extra) && $extra['company_slug'] !== null) {
                $payload['company_slug'] = $extra['company_slug'];
            }
            if (array_key_exists('override_mode', $extra)) {
                $payload['override_mode'] = (bool) $extra['override_mode'];
            }

            Log::info("ProductObserver: Payload built for RabbitMQ", [
                'product_id'    => $productId,
                'action'        => $action,
                'company_slug'  => $payload['company_slug'] ?? null,
                'override_mode' => $payload['override_mode'] ?? false,
                'payload'       => $payload,
            ]);

            $outbox = ProductEventOutbox::createAndDispatch($product->id, $action, $payload);

            Log::info("ProductObserver: Event dispatched to outbox", [
                'product_id'    => $productId,
                'action'        => $action,
                'company_slug'  => $payload['company_slug'] ?? null,
                'override_mode' => $payload['override_mode'] ?? false,
                'outbox_id'     => $outbox->id,
                'status'        => $outbox->status,
            ]);

        } catch (\Exception $e) {
            Log::error("ProductObserver::dispatchProductEvent: Failed", [
                'product_id' => $productId,
                'action'     => $action,
                'extra'      => $extra,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }

    public function deleted(Product $product): void
    {
        try {
            $payload = self::buildProductPayload($product);
            $payload['action'] = 'deleted';

            Log::info("ProductObserver: Payload built for RabbitMQ [DELETED]", [
                'product_id'   => $product->id,
                'action'       => 'deleted',
                'company_slug' => $payload['company_slug'] ?? null,
                'payload'      => $payload,
            ]);

            ProductEventOutbox::createAndDispatch($product->id, 'deleted', $payload);

        } catch (\Exception $e) {
            Log::error("ProductObserver::deleted: Failed", [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
