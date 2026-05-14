<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'website',
        'account_type_id',
        'account_industry_id',
        'status',
        'created_by',
        'assigned_to',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    public function accountIndustry(): BelongsTo
    {
        return $this->belongsTo(AccountIndustry::class);
    }

    public function cases(): HasMany
    {
        return $this->hasMany(\App\Models\CaseModel::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AccountActivity::class)->with('user')->orderBy('created_at', 'desc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AccountComment::class)->with('user')->orderBy('created_at', 'desc');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}