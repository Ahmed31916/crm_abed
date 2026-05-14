<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'created_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
    ];

    // ========================================================================
    // العلاقات (Relationships)
    // ========================================================================

    /**
     * المنتجات المرتبطة بهذا التاج
     * عبر جدول product_tags مع pivot created_by
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tags')
            ->withPivot('created_by')
            ->withTimestamps();
    }

    /**
     * المستخدم اللي أنشأ التاج
     * القديم: store() → belongsTo(Store::class)
     * الجديد: creator() → belongsTo(User::class)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ========================================================================
    // Scopes
    // ========================================================================

    /**
     * التاجات اللي أنشأها مستخدم معين
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * التاجات المرئية لمستخدم معين (تاجاته + تاجات السوبر أدمن)
     * القديم: where('store_id', $superAdminStoreId)->orWhere('store_id', $storeId)
     * الجديد: where('created_by', $superAdminId)->orWhere('created_by', $userId)
     */
    public function scopeVisibleTo($query, $userId)
    {
        $superAdminId = getSuperAdminCompanyId();

        return $query->where(function ($q) use ($userId, $superAdminId) {
            $q->where('created_by', $userId)
              ->orWhere('created_by', $superAdminId);
        });
    }

    /**
     * البحث بالاسم
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'LIKE', "%{$term}%");
    }

    // ========================================================================
    // Auto-generate slug عند الإنشاء
    // ========================================================================

    protected static function booted(): void
    {
        static::creating(function (self $tag) {
            if (empty($tag->slug)) {
                $tag->slug = $tag->generateUniqueSlug($tag->name);
            }
        });

        static::updating(function (self $tag) {
            if ($tag->isDirty('name') && empty($tag->slug)) {
                $tag->slug = $tag->generateUniqueSlug($tag->name);
            }
        });
    }

    /**
     * توليد slug فريد
     */
    public function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (self::where('slug', $slug)
            ->where('id', '!=', $this->id ?? 0)
            ->exists()
        ) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    // ========================================================================
    // Helper Methods
    // ========================================================================

    /**
     * هل هذا التاج تابع للسوبر أدمن؟
     */
    public function isSuperAdminTag(): bool
    {
        return $this->created_by == getSuperAdminCompanyId();
    }

    /**
     * هل المستخدم الحالي يقدر يعدل هذا التاج؟
     * فقط صاحب التاج يقدر يعدله
     */
    public function canEdit(): bool
    {
        return $this->created_by == createdBy();
    }

    /**
     * عدد المنتجات المرتبطة بهذا التاج
     */
    public function getProductCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * ربط التاج بمنتج معين لمستخدم معين
     *
     * @param int $productId
     * @param int|null $userId إذا null يستخدم createdBy()
     * @return void
     */
    public function attachToProduct(int $productId, ?int $userId = null): void
    {
        $userId = $userId ?? createdBy();

        // تحقق إن التاج مش مربوط بالفعل بنفس المستخدم
        $exists = \DB::table('product_tags')
            ->where('product_id', $productId)
            ->where('tag_id', $this->id)
            ->where('created_by', $userId)
            ->exists();

        if (!$exists) {
            \DB::table('product_tags')->insert([
                'product_id' => $productId,
                'tag_id'     => $this->id,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * فصل التاج عن منتج معين لمستخدم معين
     */
    public function detachFromProduct(int $productId, ?int $userId = null): void
    {
        $userId = $userId ?? createdBy();

        \DB::table('product_tags')
            ->where('product_id', $productId)
            ->where('tag_id', $this->id)
            ->where('created_by', $userId)
            ->delete();
    }

    /**
     * مزامنة تاجات منتج معين (حذف القديم + إضافة الجديد)
     *
     * @param int $productId
     * @param array $tagIds
     * @param int|null $userId
     * @return void
     */
    public static function syncProductTags(int $productId, array $tagIds, ?int $userId = null): void
    {
        $userId = $userId ?? createdBy();

        // حذف التاجات القديمة لهذا المستخدم على هذا المنتج
        \DB::table('product_tags')
            ->where('product_id', $productId)
            ->where('created_by', $userId)
            ->delete();

        // إضافة التاجات الجديدة
        $insertData = [];
        $now = now();
        foreach ($tagIds as $tagId) {
            $insertData[] = [
                'product_id' => $productId,
                'tag_id'     => $tagId,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($insertData)) {
            \DB::table('product_tags')->insert($insertData);
        }
    }
}
