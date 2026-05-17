<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductEventOutbox;
use App\Models\ProductCompanyOverride;
use App\Models\HealthProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

 
class ProductObserver
{
    /**
     * بناء البيانات الكاملة للمنتج لإرسالها عبر RabbitMQ
     *
     * هذه الدالة عامة (public static) بحيث يمكن استدعاؤها من الكنترولر
     * بعد حفظ كل البيانات المرتبطة (health_product, tags, indications, etc.)
     *
     * @param Product $product
     * @return array
     */
    public static function buildProductPayload(Product $product): array
    {
        $superAdminCompanyId = getSuperAdminCompanyId();
        $currentCompanyId = $product->created_by;

        $price = (double) $product->price;
        $salePrice = (double) ($product->sale_price ?? 0);
        $hasDiscount = ($salePrice > 0 && $salePrice < $price);
        $discountPercentage = $hasDiscount ? round((($price - $salePrice) / $price) * 100, 2) : 0;

        // ==================== تحميل بيانات المنتج الصحي ====================
        // القديم: DB::table('health_products')->where('product_id', ...)->first()
        // الجديد: HealthProduct::getForProduct() مع دعم أولوية الشركة
        $healthProduct = HealthProduct::getForProduct($product->id, $currentCompanyId);

        // ==================== تحميل اسم التصنيف ====================
        // القديم والجديد: نفس الجدول (categories)
        $categoryName = '';
        if ($product->category_id) {
            $category = DB::table('categories')->where('id', $product->category_id)->first();
            $categoryName = $category ? $category->name : '';
        }

        // ==================== تحميل اسم المورد (Supplier/Brand) ====================
        // القديم: DB::table('product_brands') | الجديد: DB::table('brands')
        $supplierName = null;
        if ($product->brand_id) {
            $brand = DB::table('brands')->where('id', $product->brand_id)->first();
            $supplierName = $brand ? $brand->name : null;
        }

        // ==================== تحميل اسم الممارس (practitioner) ====================
        // القديم: DB::table('stores')->where('id', $currentStoreId) | الجديد: User model
        $practitioner = null;
        $practitionerSlug = null;
        $companyUser = \App\Models\User::where('id', $currentCompanyId)->first();
        if ($companyUser) {
            $practitioner = $companyUser->name;
            $practitionerSlug = $companyUser->slug ?? null;
        }

        // ==================== Tags - مع دعم company priority ====================
        // القديم: product_tags.store_id | الجديد: product_tags.created_by
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

        // ==================== Primary Indications - مع دعم company priority ====================
        // القديم: جدول product_primary_indications منفصل مع store_id
        // الجديد: primary_indications كـ JSON في health_products
        $primaryIndications = [];
        if ($healthProduct && !empty($healthProduct->primary_indications)) {
            $primaryIndications = is_array($healthProduct->primary_indications)
                ? $healthProduct->primary_indications
                : json_decode($healthProduct->primary_indications, true) ?? [];
        }

        // ==================== Pairs Well With - ككائنات مع id, name, sku ====================
        $pairsWellWith = [];
        $pairedProducts = $product->pairsWellWith()->get();
        foreach ($pairedProducts as $pair) {
            // القديم: DB::table('health_products')->where('product_id', $pair->id)->first()
            // الجديد: HealthProduct::getForProduct() مع أولوية الشركة
            $pairHealth = HealthProduct::getForProduct($pair->id, $currentCompanyId);
            $pairsWellWith[] = [
                'id'   => $pair->id,
                'name' => $pair->name,
                'sku'  => $pairHealth ? $pairHealth->sku : null,
            ];
        }

        // ==================== Dosing Schedule - snake_case keys ====================
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

        // ==================== Company Override ====================
        // القديم: DB::table('product_merchant_overrides')->where('store_id', ...)
        // الجديد: ProductCompanyOverride::where('company_id', ...)
        $override = ProductCompanyOverride::where('product_id', $product->id)
            ->where('company_id', $currentCompanyId)
            ->first();

        // ==================== Image URL ====================
        // القديم: asset($product->cover_image_url) / asset($product->cover_image_path)
        // الجديد: Spatie MediaLibrary + product_image_url من health_products
        $imageUrl = !empty($healthProduct->product_image_url)
            ? $healthProduct->product_image_url
            : $product->getFirstMediaUrl('main');

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

            // التسعير
            'regular_price'             => $price,
            'sale_price'                => $salePrice > 0 ? $salePrice : null,
            'price'                     => $hasDiscount ? $salePrice : $price,
            'has_discount'              => $hasDiscount,
            'discount_percentage'       => $discountPercentage,

            // الصورة
            'image_url'                 => $imageUrl,

            // الحالة
            // القديم: $product->status == 1 | الجديد: $product->status === 'active'
            'is_active'                 => ($product->status === 'active'),
            'stock_status'              => $product->stock_status ?? 'in_stock',

            // التاجات
            'tags'                      => array_values($tags),

            // معلومات إضافية
            'frequency'                 => $product->frequency ?? null,
            'supplier'                  => $supplierName,
            'pairs_well_with'           => $pairsWellWith,

            // بيانات المنتج الصحي
            'ingredients'               => $healthProduct->ingredients ?? null,
            'bottle_size'               => $healthProduct->bottle_size ?? null,
            'bottle_size_unit'          => $healthProduct->bottle_size_unit ?? null,
            'product_form'              => ($healthProduct->bottle_size_unit ?? '') == 'caps' ? 'Caps' : 'Liquid',
            'primary_indications'       => $primaryIndications,
            'supports'                  => $healthProduct->supports ?? null,
            'useful_for'                => $healthProduct->useful_for ?? null,
            'contraindications'         => $healthProduct->contraindications ?? null,
            'research'                  => $healthProduct->research_links ?? null,

            // ملاحظات الممارس
            // القديم: override->practitioner_notes ?? healthProduct->practitioner_notes
            // الجديد: فقط من override لأن health_products ما عندش practitioner_notes
            'practitioner_notes'                => $override->practitioner_notes ?? null,
            'custom_primary_indications'        => $override ? ($override->custom_primary_indications ?? []) : [],
            'custom_dosing_notes'               => $override->custom_dosing_notes ?? null,

            // جدول الجرعات
            'dosing_schedule'           => $dosingSchedule,
            'dosing_na'                 => (bool) ($healthProduct->dosing_na ?? false),

            // معرف الرسالة
            'message_id'                => $product->message_id,
        ];
    }

    /**
     * إرسال حدث المنتج إلى الـ Outbox يدوياً
     *
     * يُستدعى من الكنترولر بعد حفظ جميع البيانات المرتبطة بالمنتج.
     * يستخدم DB::afterCommit() لضمان تنفيذ الـ dispatch بعد commit الـ transaction.
     * هذا يضمن أن الـ payload يحتوي على بيانات كاملة ومحدثة.
     *
     * @param int $productId
     * @param string $action (created|updated|deleted)
     * @return void
     */
    public static function dispatchProductEvent(int $productId, string $action): void
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

            // Increment message_id for idempotency / ordering
            $product->increment('message_id');
            $product->refresh();

            $payload = self::buildProductPayload($product);
            $payload['action'] = $action;

            // تسجيل الـ JSON payload في اللوج للفحص
            Log::info("ProductObserver: Payload built for RabbitMQ", [
                'product_id' => $productId,
                'action'     => $action,
                'payload'    => $payload,
            ]);

            $outbox = ProductEventOutbox::createAndDispatch($product->id, $action, $payload);

            Log::info("ProductObserver: Event dispatched to outbox", [
                'product_id' => $productId,
                'action'     => $action,
                'outbox_id'  => $outbox->id,
                'status'     => $outbox->status,
            ]);

        } catch (\Exception $e) {
            Log::error("ProductObserver::dispatchProductEvent: Failed", [
                'product_id' => $productId,
                'action'     => $action,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | أحداث الـ Observer
    |--------------------------------------------------------------------------
    |
    | ملاحظة مهمة: تم إزالة created() و updated() من الـ Observer لأنهما
    | كانا يُنفّذان قبل حفظ البيانات المرتبطة (health_product, tags, etc.)
    | مما يؤدي إلى بيانات ناقصة في الـ payload.
    |
    | الآن يتم استدعاء dispatchProductEvent() يدوياً من الكنترولر
    | بعد حفظ جميع البيانات المرتبطة.
    |
    */

    /**
     * عند حذف منتج - هذا يعمل بشكل صحيح لأن كل البيانات لسا موجودة
     */
    public function deleted(Product $product): void
    {
        try {
            $payload = self::buildProductPayload($product);
            $payload['action'] = 'deleted';

            Log::info("ProductObserver: Payload built for RabbitMQ [DELETED]", [
                'product_id' => $product->id,
                'action'     => 'deleted',
                'payload'    => $payload,
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
