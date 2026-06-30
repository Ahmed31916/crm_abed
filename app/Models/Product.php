<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\FacadesDB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',                 // ← جديد: URL slug
        'sku',                  // للبحث والفهرسة (مكرر من health_products)
        'description',
        'specification',        // ← جديد: من الـ migration الجديد
        'detail',               // ← جديد: من الـ migration الجديد

        // Pricing
        'price',
        'sale_price',

        // Stock
        'stock_quantity',
        'stock_status',         // in_stock, out_of_stock, on_backorder

        // Product attributes
        'product_weight',
        'tax_id',
        'tax_status',           // ← جديد: taxable, none

        // Relations
        'category_id',
        'brand_id',
        'frequency',            // ← جديد: dosing frequency

        // Flags
        'status',               // active, inactive

        // Ownership
        'created_by',
        'assigned_to',

        // Spatie MediaLibrary
        'main_image_id',
        'additional_image_ids',
        'message_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'additional_images' => 'array',
        'additional_image_ids' => 'array',
        'stock_status' => 'string',
        'sale_price'   => 'decimal:2',
        'product_weight' => 'decimal:2',
        'stock_quantity' => 'integer',
    ];

    protected $appends = ['main_image_url', 'additional_image_urls'];

    protected static function booted()
    {
        static::updating(function ($product) {
            $product->message_id = ($product->message_id ?? 0) + 1;
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function opportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class, 'opportunity_products')
            ->withPivot('quantity', 'unit_price', 'total_price')
            ->withTimestamps();
    }

    public function quotes(): BelongsToMany
    {
        return $this->belongsToMany(Quote::class, 'quote_products')
            ->withPivot('quantity', 'unit_price', 'total_price')
            ->withTimestamps();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main')
            ->singleFile();

        $this->addMediaCollection('additional');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->performOnCollections('main', 'additional')
            ->nonQueued();
    }

    public function getMainImageUrlAttribute()
    {
        if ($this->main_image_id) {
            $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($this->main_image_id);
            return $media && $media->exists() ? $media->getUrl() : $this->getDefaultImageUrl();
        }
        $media = $this->getFirstMedia('main');
        return $media && $media->exists() ? $media->getUrl() : $this->getDefaultImageUrl();
    }

    public function getDefaultImageUrl()
    {
        return $this->image ?: config('app.url') . '/storage/media/product/default.svg';
    }

    public function getAdditionalImageUrlsAttribute()
    {
        if ($this->additional_image_ids) {
            return collect($this->additional_image_ids)->map(function ($mediaId) {
                $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($mediaId);
                return $media ? [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumb_url' => $media->getUrl('thumb')
                ] : null;
            })->filter();
        }
        return $this->getMedia('additional')->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb_url' => $media->getUrl('thumb')
            ];
        });
    }

    public function toArray()
    {
        $array = parent::toArray();

        // Add media in the format expected by frontend
        $mediaArray = [];

        // Add main image - check if media still exists
        $mainMedia = $this->getFirstMedia('main');
        if ($mainMedia && $mainMedia->exists()) {
            $mediaArray[] = [
                'id' => $mainMedia->id,
                'collection_name' => 'main',
                'original_url' => $mainMedia->getUrl(),
                'thumb_url' => $mainMedia->getUrl('thumb')
            ];
        }

        // Add additional images - filter out deleted media
        foreach ($this->getMedia('additional') as $media) {
            if ($media->exists()) {
                $mediaArray[] = [
                    'id' => $media->id,
                    'collection_name' => 'additional',
                    'original_url' => $media->getUrl(),
                    'thumb_url' => $media->getUrl('thumb')
                ];
            }
        }

        $array['media'] = $mediaArray;
        $array['has_valid_image'] = !empty($mediaArray) || !empty($this->image);
        $array['display_image_url'] = $this->getMainImageUrlAttribute();

        return $array;
    }
    public function companyOverrides()
    {
        return $this->hasMany(\App\Models\ProductCompanyOverride::class);
    }

    public function getOverrideForCompany($companyId)
    {
        return \App\Models\ProductCompanyOverride::where('product_id', $this->id)
            ->where('company_id', $companyId)
            ->first();
    }

    public function outboxEvents()
    {
        return $this->hasMany(\App\Models\ProductEventOutbox::class);
    }

    public function tags()
    {
        return $this->belongsToMany(\App\Models\Tag::class, 'product_tags')
            ->withPivot('created_by')
            ->withTimestamps();
    }


    /**
     * جلب التاجات المرئية للمستخدم الحالي (للـ API)
     *
     * @param int $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVisibleTags(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        $superAdminId = getSuperAdminCompanyId();

        // تاجات الشركة
        $companyTagIds = DB::table('product_tags')
            ->where('product_id', $this->id)
            ->where('created_by', $companyId)
            ->pluck('tag_id')
            ->toArray();

        if (!empty($companyTagIds)) {
            return \App\Models\Tag::whereIn('id', $companyTagIds)->get();
        }

        // Fallback: تاجات السوبر أدمن
        $superAdminTagIds = DB::table('product_tags')
            ->where('product_id', $this->id)
            ->where('created_by', $superAdminId)
            ->pluck('tag_id')
            ->toArray();

        return \App\Models\Tag::whereIn('id', $superAdminTagIds)->get();
    }

    /**
     * البيانات الصحية للمنتج
     * واحد لواحد: كل منتج له سجل health_product واحد لكل مستخدم
     *
     * في المشروع القديم: كان healthProduct() بيشيل أول سجل فقط
     * في المشروع الجديد: بنستخدم HealthProduct::getForProduct() عشان نراعي الأولوية
     */
    public function healthProduct()
    {
        return $this->hasOne(\App\Models\HealthProduct::class);
    }

    /**
     * كل سجلات HealthProduct للمنتج (للسوبر أدمن + كل الشركات)
     */
    public function healthProducts()
    {
        return $this->hasMany(\App\Models\HealthProduct::class);
    }

    // =========================================================================
    // =========== NEW: Primary Indications Pivot (Many-to-Many) ==============
    // =========================================================================

    /**
     * العلاقة Many-to-Many مع PrimaryIndication.
     *
     * يستبدل هذا الـ JSON القديم في health_products.primary_indications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function primaryIndications(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\PrimaryIndication::class,
            'product_primary_indications',
            'product_id',
            'primary_indication_id'
        )
        ->withTimestamps()
        ->orderBy('name');
    }

    /**
     * Accessor: أسماء الـ Primary Indications كـ array من النصوص.
     *
     * يحل محل `$product->healthProduct->primary_indications` القديم.
     *
     * @return array<string>
     */
    public function getPrimaryIndicationNamesAttribute(): array
    {
        // نستخدم pluck بدل map لتقليل الاستعلامات عند استخدام eager loading
        return $this->primaryIndications()
            ->pluck('name')
            ->toArray();
    }

    /**
     * Helper: جلب أسماء الـ Primary Indications مع دعم Override.
     *
     * أولوية البيانات:
     *   1. لو الشركة عندا override على المنتج → نستخدم override->primary_indications
     *   2. لو ما في override → نستخدم العلاقة belongsToMany الجديدة
     *
     * @param int|null $companyId  معرف الشركة (للبحث عن override)
     * @return array<string>
     */
    public function getPrimaryIndicationNames(?int $companyId = null): array
    {
        $companyId = $companyId ?? createdBy();

        // 1. ابحث عن override للشركة
        $override = \App\Models\ProductCompanyOverride::where('product_id', $this->id)
            ->where('company_id', $companyId)
            ->first();

        if ($override && $override->primary_indications !== null) {
            $indications = $override->primary_indications;
            return is_array($indications) ? array_values($indications) : [];
        }

        // 2. fallback: العلاقة belongsToMany
        return $this->primaryIndications()
            ->pluck('name')
            ->toArray();
    }

    /**
     * Helper: جلب IDs الـ Primary Indications (مفيد للـ form editing).
     *
     * @return array<int>
     */
    public function getPrimaryIndicationIds(): array
    {
        return $this->primaryIndications()
            ->pluck('primary_indications.id')
            ->toArray();
    }

    /**
     * Sync Primary Indications عبر IDs.
     *
     * يستقبل array من IDs ويعمل sync على الـ pivot.
     * مفيد للـ ProductController@store و @update.
     *
     * @param array<int|string> $ids
     * @return void
     */
    public function syncPrimaryIndications(array $ids): void
    {
        $intIds = array_map('intval', array_filter($ids, fn($id) => !empty($id)));
        $this->primaryIndications()->sync($intIds);
    }

// ========================================================================
// 5. Accessor لحساب السعر النهائي
// ========================================================================

    /**
     * Get the final price (sale price if discount exists, otherwise regular price)
     */
    public function getFinalPriceAttribute(): float
    {
        $salePrice = (float) ($this->sale_price ?? 0);
        $price = (float) $this->price;

        if ($salePrice > 0 && $salePrice < $price) {
            return $salePrice;
        }

        return $price;
    }

    /**
     * Check if product has a discount
     */
    public function getHasDiscountAttribute(): bool
    {
        $salePrice = (float) ($this->sale_price ?? 0);
        $price = (float) $this->price;

        return $salePrice > 0 && $salePrice < $price;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentageAttribute(): float
    {
        if (!$this->has_discount) {
            return 0;
        }

        $price = (float) $this->price;
        $salePrice = (float) $this->sale_price;

        return round((($price - $salePrice) / $price) * 100, 2);
    }

    public static function slugs($data)
    {
        $slug = '';

        $slug = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $data); // Remove special chars
        // $slug = transliterator_transliterate('Any-Latin; Latin-ASCII', $slug); // Transliterate to Latin
        $slug = strtolower(trim($slug)); // Convert to lowercase and trim
        $slug = preg_replace('/\s+/', '-', $slug); // Replace spaces with hyphens
        $slug = preg_replace('/-+/', '-', $slug); // Replace multiple hyphens with single hyphen

        $table = with(new Product)->getTable();

        $allSlugs = self::getRelatedSlugs($table, $slug, $id = 0);

        if (!$allSlugs->contains('slug', $slug)) {
            return $slug;
        }
        for ($i = 1; $i <= 100; $i++) {
            $newSlug = $slug . '-' . $i;
            if (!$allSlugs->contains('slug', $newSlug)) {
                return $newSlug;
            }
        }
    }

    protected static function getRelatedSlugs($table, $slug, $id = 0)
    {
        return DB::table($table)->select()->where('slug', 'like', $slug . '%')->where('id', '<>', $id)->get();
    }


    public function getTagNames(?int $companyId = null): array
    {
        $companyId = $companyId ?? createdBy();
        $superAdminId = getSuperAdminCompanyId();

        // الأولوية: تاجات الشركة
        $companyTagIds = DB::table('product_tags')
            ->where('product_id', $this->id)
            ->where('created_by', $companyId)
            ->pluck('tag_id')
            ->toArray();

        if (!empty($companyTagIds)) {
            return \App\Models\Tag::whereIn('id', $companyTagIds)->pluck('name')->toArray();
        }

        // Fallback: تاجات السوبر أدمن
        $superAdminTagIds = DB::table('product_tags')
            ->where('product_id', $this->id)
            ->where('created_by', $superAdminId)
            ->pluck('tag_id')
            ->toArray();

        if (!empty($superAdminTagIds)) {
            return \App\Models\Tag::whereIn('id', $superAdminTagIds)->pluck('name')->toArray();
        }

        return [];
    }

    /**
     * Products that pair well with this product
     */
    public function pairsWellWith()
    {
        return $this->belongsToMany(\App\Models\Product::class, 'product_pairs', 'product_id', 'paired_product_id')
            ->withPivot('created_by')
            ->withTimestamps();
    }

    /**
     * Products this product is paired with (inverse)
     */
    public function pairedWith()
    {
        return $this->belongsToMany(\App\Models\Product::class, 'product_pairs', 'paired_product_id', 'product_id')
            ->withPivot('created_by')
            ->withTimestamps();
    }

// ========================================================================
// 4. Scopes
// ========================================================================

    /**
     * Products visible to a specific company (own + super admin)
     */
    public function scopeVisibleTo($query, $companyId)
    {
        $superAdminId = getSuperAdminCompanyId();
        return $query->where(function ($q) use ($companyId, $superAdminId) {
            $q->where('created_by', $companyId)
                ->orWhere('created_by', $superAdminId);
        });
    }

    /**
     * Active products only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }


    // ========================================================================
    // 8. Route key binding (optional - use slug in URLs)
    // ========================================================================

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}