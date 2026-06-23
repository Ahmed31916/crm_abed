<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductEventOutbox;
use App\Models\ProductCompanyOverride;
use App\Models\HealthProduct;
use App\Models\User;
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
     * ════════════════════════════════════════════════════════════════════════
     * ⚠️  ما الجديد (v2)
     * ════════════════════════════════════════════════════════════════════════
     * أضفنا معاملاً اختيارياً $overrideCompanyId يسمح للكنترولر بتمرير
     * ID الشركة التي قامت بالتعديل عند تحديث override على منتج سوبر ادمن.
     *
     * لماذا؟
     *   - في الأصل, buildProductPayload تستخدم $product->created_by لتحديد
     *     $currentCompanyId الذي يحدد practitioner, tags, override lookup.
     *   - لكن عندما تُعدّل شركة منتج سوبر ادمن, يجب أن يحتوي الـ payload على
     *     بيانات الشركة (practitioner, override, tags) وليس بيانات السوبر ادمن.
     *   - تمرير $overrideCompanyId يحل هذه المشكلة بأن يُبنى الـ payload
     *     من منظور الشركة المُعدِّلة.
     *
     * @param Product   $product
     * @param int|null  $overrideCompanyId  ID الشركة المُعدِّلة (للـ override mode)
     * @return array
     */
    public static function buildProductPayload(Product $product, ?int $overrideCompanyId = null): array
    {
        $superAdminCompanyId = getSuperAdminCompanyId();
        // FIX: في وضع override نستخدم ID الشركة المُعدِّلة بدلاً من created_by
        $currentCompanyId = $overrideCompanyId ?? (int) $product->created_by;

        // FIX: علامة تخبر الـ desktop app أن هذه رسالة "override" وليست تحديثاً أصلياً
        $isOverrideMode = $overrideCompanyId !== null
            && (int) $overrideCompanyId !== (int) $product->created_by;

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
        // FIX: في وضع override, هذا سيكون اسم الشركة المُعدِّلة تلقائياً
        $practitioner = null;
        $practitionerSlug = null;
        $companyUser = User::where('id', $currentCompanyId)->first();
        if ($companyUser) {
            $practitioner = $companyUser->name;
            $practitionerSlug = $companyUser->slug ?? null;
        }

        // ==================== Tags - مع دعم company priority ====================
        // القديم: product_tags.store_id | الجديد: product_tags.created_by
        // FIX: في وضع override, ستبحث عن tags الشركة المُعدِّلة أولاً
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
        // FIX: في وضع override, سيبحث عن override الشركة المُعدِّلة (الصحيح)
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

            // ════════════════════════════════════════════════════════════════════
            // ⚠️  حقول جديدة (v2) — لتوجيه الرسالة في الـ desktop app
            // ════════════════════════════════════════════════════════════════════
            // company_slug: slug الشركة صاحبة التعديل (التي يجب أن تستقبل الرسالة)
            // override_mode: true = هذه رسالة "override" على منتج سوبر ادمن,
            //                false = تحديث أصلي على منتج الشركة نفسها
            'company_slug'              => $practitionerSlug,
            'override_mode'             => $isOverrideMode,
            'owner_company_id'          => (int) $product->created_by,
            'acting_company_id'         => (int) $currentCompanyId,
        ];
    }

    /**
     * إرسال حدث المنتج إلى الـ Outbox يدوياً
     *
     * يُستدعى من الكنترولر بعد حفظ جميع البيانات المرتبطة بالمنتج.
     * يستخدم DB::afterCommit() لضمان تنفيذ الـ dispatch بعد commit الـ transaction.
     * هذا يضمن أن الـ payload يحتوي على بيانات كاملة ومحدثة.
     *
     * ════════════════════════════════════════════════════════════════════════
     * ⚠️  ما الجديد (v2)
     * ════════════════════════════════════════════════════════════════════════
     * أضفنا معاملاً ثالثاً اختيارياً $extra = [] يسمح للكنترولر بتمرير:
     *
     *   - 'company_slug'        : string|null   slug الشركة المُعدِّلة
     *   - 'override_company_id' : int|null      ID الشركة المُعدِّلة (يُمرَّر
     *                                            إلى buildProductPayload لبناء
     *                                            الـ payload من منظور الشركة)
     *   - 'override_mode'       : bool          true = override على منتج سوبر ادمن
     *
     * التوافق الخلفي:
     *   - المكالمات القديمة dispatchProductEvent($id, $action) تستمر في العمل
     *   - في هذه الحالة, الـ payload يُبنى من منظور created_by (السلوك الأصلي)
     *   - company_slug = practitioner_slug, override_mode = false
     *
     * @param int    $productId
     * @param string $action  (created|updated|deleted)
     * @param array  $extra   {
     *     @var string|null $company_slug
     *     @var int|null    $override_company_id
     *     @var bool        $override_mode
     * }
     * @return void
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

            // Increment message_id for idempotency / ordering
            $product->increment('message_id');
            $product->refresh();

            // ════════════════════════════════════════════════════════════════════
            // FIX: إذا تم تمرير override_company_id, نبني الـ payload من منظور
            // الشركة المُعدِّلة بدلاً من منظور created_by. هذا يجعل practitioner,
            // tags, override, dosing_schedule كلها تأتي من بيانات الشركة.
            // ════════════════════════════════════════════════════════════════════
            $overrideCompanyId = $extra['override_company_id'] ?? null;
            $payload = self::buildProductPayload($product, $overrideCompanyId);
            $payload['action'] = $action;

            // إذا مرر الكنترولر company_slug صريحاً, نستخدمه (يفضّل ذلك لأن
            // الكنترولر يعرف السياق بشكل أفضل). وإلا نعتمد على ما أعادته
            // buildProductPayload (practitioner_slug).
            if (array_key_exists('company_slug', $extra) && $extra['company_slug'] !== null) {
                $payload['company_slug'] = $extra['company_slug'];
            }

            // إذا مرر الكنترولر override_mode صريحاً, نستخدمه. وإلا نعتمد على
            // القيمة المحسوبة من override_company_id.
            if (array_key_exists('override_mode', $extra)) {
                $payload['override_mode'] = (bool) $extra['override_mode'];
            }

            // تسجيل الـ JSON payload في اللوج للفحص
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
     *
     * ملاحظة: حدث deleted() في Laravel Observer لا يقبل معاملات إضافية,
     * لذا لا يمكن تمرير $extra له. إذا احتجت لتمرير company_slug في
     * رسالة الحذف, استدعِ يدوياً:
     *
     *   ProductObserver::dispatchProductEvent($product->id, 'deleted', [
     *       'company_slug' => ...,
     *       'override_company_id' => ...,
     *   ]);
     *
     * قبل تنفيذ $product->delete().
     */
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
