<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Category Model — Updated with company_id
 *
 * جداول categories الآن يحتوي على كلا العمودين:
 *   - company_id : الشركة المالكة (للـ visibility scoping) — مضاف حديثاً
 *   - created_by : المستخدم الذي أنشأ السجل (audit trail) — موجود مسبقاً
 *
 * كلاهما يشير إلى users.id حيث type = 'company' أو 'superadmin' أو 'staff'
 *
 * ملاحظة: إذا كان لديك ملف Category.php بمحتوى مختلف، ادمج التغييرات التالية فقط:
 *   1. أضف 'company_id' إلى $fillable
 *   2. أضف علاقة company()
 *   3. حدّث scopeVisibleTo لتستخدم company_id (مع fallback لـ created_by)
 *   4. أضف scopeForCompany()
 */
class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
        'status',
        'company_id',   // مضاف حديثاً — الشركة المالكة
        'created_by',   // موجود مسبقاً — المستخدم الذي أنشأ السجل
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // =========================================================================
    // =========== BOOT ========================================================
    // =========================================================================

    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from name if not provided
        static::saving(function (Category $category) {
            if (empty($category->slug)) {
                $slug = Str::slug($category->name);
                $original = $slug;
                $counter = 1;
                while (Category::where('slug', $slug)
                    ->where('id', '!=', $category->id ?? 0)
                    ->exists()) {
                    $slug = $original . '-' . $counter++;
                }
                $category->slug = $slug;
            }
        });
    }

    // =========================================================================
    // =========== RELATIONSHIPS ===============================================
    // =========================================================================

    /**
     * الشركة المالكة للتصنيف
     */
    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    /**
     * المستخدم الذي أنشأ السجل (audit)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * المنتجات المرتبطة بهذا التصنيف
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    // =========================================================================
    // =========== SCOPES ======================================================
    // =========================================================================

    /**
     * Scope: Categories visible to a specific company
     * - يرجع التصنيفات اللي تملكها الشركة نفسها + التصنيفات اللي يملكها السوبر ادمن
     * - يعتمد على company_id أولاً، مع fallback إلى created_by
     *   للسجلات القديمة (قبل إضافة company_id)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $companyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisibleTo(Builder $query, int $companyId): Builder
    {
        $superAdminId = getSuperAdminCompanyId();

        return $query->where(function ($q) use ($companyId, $superAdminId) {
            $q->where('company_id', $companyId)
              ->orWhere('company_id', $superAdminId)
              // Fallback للسجلات القديمة بدون company_id
              ->orWhere(function ($q2) use ($companyId, $superAdminId) {
                  $q2->whereNull('company_id')
                     ->where(function ($q3) use ($companyId, $superAdminId) {
                         $q3->where('created_by', $companyId)
                            ->orWhere('created_by', $superAdminId);
                     });
              });
        });
    }

    /**
     * Scope: Categories owned by a specific company only (no super admin fallback)
     * يستخدم company_id أولاً، مع fallback إلى created_by
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->orWhere(function ($q2) use ($companyId) {
                  $q2->whereNull('company_id')
                     ->where('created_by', $companyId);
              });
        });
    }

    /**
     * Scope: Active categories only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
