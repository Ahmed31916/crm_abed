<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'price',
        'stock_quantity',
        'image',
        'main_image',
        'main_image_id',
        'additional_images',
        'additional_image_ids',
        'category_id',
        'brand_id',
        'tax_id',
        'status',
        'created_by',
        'assigned_to',
        'stock_status',
        'sale_price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'additional_images' => 'array',
        'additional_image_ids' => 'array',
        'stock_status' => 'string',
        'sale_price'   => 'decimal:2',
    ];

    protected $appends = ['main_image_url', 'additional_image_urls'];

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
     * جلب أسماء التاجات للمنتج مع أولوية الشركة
     * الشركة تشوف تاجاتها أولاً، إذا ما عندش يرجع لتاجات السوبر أدمن
     *
     * @param int|null $companyId إذا null يستخدم createdBy()
     * @return array
     */
    public function getTagNames(?int $companyId = null): array
    {
        $companyId = $companyId ?? createdBy();
        $superAdminId = getSuperAdminCompanyId();

        // الأولوية: تاجات الشركة
        $companyTagIds = \DB::table('product_tags')
            ->where('product_id', $this->id)
            ->where('created_by', $companyId)
            ->pluck('tag_id')
            ->toArray();

        if (!empty($companyTagIds)) {
            return \App\Models\Tag::whereIn('id', $companyTagIds)->pluck('name')->toArray();
        }

        // Fallback: تاجات السوبر أدمن
        $superAdminTagIds = \DB::table('product_tags')
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
     * جلب التاجات المرئية للمستخدم الحالي (للـ API)
     *
     * @param int $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVisibleTags(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        $superAdminId = getSuperAdminCompanyId();

        // تاجات الشركة
        $companyTagIds = \DB::table('product_tags')
            ->where('product_id', $this->id)
            ->where('created_by', $companyId)
            ->pluck('tag_id')
            ->toArray();

        if (!empty($companyTagIds)) {
            return \App\Models\Tag::whereIn('id', $companyTagIds)->get();
        }

        // Fallback: تاجات السوبر أدمن
        $superAdminTagIds = \DB::table('product_tags')
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

    // public function registerMediaCollections(): void
    // {
    //     $this->addMediaCollection('main')
    //         ->singleFile();

    //     $this->addMediaCollection('additional');
    // }


    /**
     * Get the main image URL
     */
    // public function getMainImageUrlAttribute(): ?string
    // {
    //     return $this->getFirstMediaUrl('main') ?: null;
    // }

    /**
     * Get additional image URLs
     */
    // public function getAdditionalImageUrlsAttribute(): array
    // {
    //     return $this->getMedia('additional')->map(fn($media) => $media->getUrl())->toArray();
    // }
}
