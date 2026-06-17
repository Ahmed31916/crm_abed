<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $table = 'tags';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'status',
        'company_id',   // الشركة المالكة (للـ visibility scoping)
        'created_by',   // المستخدم الذي أنشأ السجل (audit trail)
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
        static::saving(function (Tag $tag) {
            if (empty($tag->slug)) {
                $slug = Str::slug($tag->name);
                $original = $slug;
                $counter = 1;
                while (Tag::where('slug', $slug)
                    ->where('id', '!=', $tag->id ?? 0)
                    ->exists()) {
                    $slug = $original . '-' . $counter++;
                }
                $tag->slug = $slug;
            }
        });
    }

    // =========================================================================
    // =========== RELATIONSHIPS ===============================================
    // =========================================================================

    /**
     * الشركة المالكة للـ Tag
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
     * المنتجات المرتبطة بهذا الـ Tag (عبر pivot product_tags)
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tags', 'tag_id', 'product_id')
            ->withPivot('created_by')
            ->withTimestamps();
    }

    // =========================================================================
    // =========== SCOPES ======================================================
    // =========================================================================

    /**
     * Scope: Tags visible to a specific company
     * - يرجع الـ Tags اللي تملكها الشركة نفسها + الـ Tags اللي يملكها السوبر ادمن
     * - يعتمد على company_id (العمود الأساسي للـ visibility)
     * - مع fallback إلى created_by للسجلات القديمة
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
              ->orWhereNull('company_id');
        })->where('status', 'active');
    }

    /**
     * Scope: Tags owned by a specific company only (own tags, no super admin fallback)
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Active tags only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
