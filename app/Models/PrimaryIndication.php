<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PrimaryIndication extends Model
{
    use HasFactory;

    protected $table = 'primary_indications';

    protected $fillable = [
        'name',
        'company_id',   // الشركة المالكة (للـ visibility scoping)
        'created_by',   // المستخدم الذي أنشأ السجل (audit trail)
    ];

    // =========================================================================
    // =========== RELATIONSHIPS ===============================================
    // =========================================================================

    /**
     * الشركة المالكة للمؤشر الرئيسي
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

    // =========================================================================
    // =========== SCOPES ======================================================
    // =========================================================================

    /**
     * Scope: Primary indications visible to a specific company
     * - يرجع المؤشرات اللي تملكها الشركة نفسها + المؤشرات اللي يملكها السوبر ادمن
     * - يعتمد على company_id (العمود الأساسي للـ visibility)
     * - مع fallback للسجلات القديمة بدون company_id
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
              ->orWhereNull('company_id');
        });
    }

    /**
     * Scope: Primary indications owned by a specific company only
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
