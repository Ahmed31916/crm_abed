<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'amount',
        'close_date',
        'notes',
        'status',
        'account_id',
        'contact_id',
        'opportunity_stage_id',
        'opportunity_source_id',
        'created_by',
        'assigned_to',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'close_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function opportunityStage(): BelongsTo
    {
        return $this->belongsTo(OpportunityStage::class);
    }

    public function opportunitySource(): BelongsTo
    {
        return $this->belongsTo(OpportunitySource::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'opportunity_products')
            ->withPivot('quantity', 'unit_price', 'total_price')
            ->withTimestamps();
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OpportunityActivity::class)->with('user')->orderBy('created_at', 'desc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(OpportunityComment::class)->with('user')->orderBy('created_at', 'desc');
    }

    public function calculateTotals()
    {
        $subtotal = 0;
        $totalTax = 0;

        foreach ($this->products as $product) {
            $quantity = $product->pivot->quantity ?? 1;
            $unitPrice = $product->pivot->unit_price ?? $product->price ?? 0;
            $lineTotal = $quantity * $unitPrice;
            
            $subtotal += $lineTotal;
            
            if ($product->tax) {
                $totalTax += ($lineTotal * $product->tax->rate) / 100;
            }
        }

        $this->updateQuietly(['amount' => $subtotal + $totalTax]);
    }
}