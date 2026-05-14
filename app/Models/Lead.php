<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'account_name',
        'account_industry_id',
        'website',
        'position',
        'address',
        'notes',
        'value',
        'status',
        'is_converted',
        'lead_status_id',
        'lead_source_id',
        'created_by',
        'assigned_to',
        'campaign_id',
    ];

    protected $casts = [
        'is_converted' => 'boolean',
        'value' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function leadStatus(): BelongsTo
    {
        return $this->belongsTo(LeadStatus::class);
    }

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function accountIndustry(): BelongsTo
    {
        return $this->belongsTo(AccountIndustry::class);
    }



    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function activities()
    {
        return $this->hasMany(LeadActivity::class)->with('user')->orderBy('created_at', 'desc');
    }

    public function comments()
    {
        return $this->hasMany(LeadComment::class)->with('user')->orderBy('created_at', 'desc');
    }
}