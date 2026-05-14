<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutRequest extends Model
{
    protected $fillable = [
        'company_id',
        'amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($payoutRequest) {
            $payoutRequest->amount = max(0, $payoutRequest->amount ?? 0);
        });
        
        static::updating(function ($payoutRequest) {
            $payoutRequest->amount = max(0, $payoutRequest->amount ?? 0);
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }
}