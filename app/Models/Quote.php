<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\QuoteObserver;

#[ObservedBy([QuoteObserver::class])]
class Quote extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'quote_number',
        'name',
        'description',
        'opportunity_id',
        'account_id',
        'billing_contact_id',
        'shipping_contact_id',
        'shipping_provider_type_id',
        'subtotal',
        'discount_amount',
        'total_amount',
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
        'status',
        'valid_until',
        'created_by',
        'assigned_to',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'valid_until' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($quote) {
            if (empty($quote->quote_number)) {
                $quote->quote_number = 'QT-' . date('Y') . '-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'billing_contact_id');
    }

    public function billingContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'billing_contact_id');
    }

    public function shippingContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'shipping_contact_id');
    }

    public function shippingProviderType(): BelongsTo
    {
        return $this->belongsTo(ShippingProviderType::class);
    }



    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'quote_products')
            ->withPivot('quantity', 'unit_price', 'total_price', 'discount_type', 'discount_value', 'discount_amount')
            ->withTimestamps();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(QuoteActivity::class)->with('user')->orderBy('created_at', 'desc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(QuoteComment::class)->with('user')->orderBy('created_at', 'desc');
    }

    public function getProductCountAttribute()
    {
        return $this->products()->sum('quote_products.quantity');
    }

    public function calculateTotals()
    {
        $subtotal = 0;
        $taxAmount = 0;
        $totalDiscountAmount = 0;

        foreach ($this->products as $product) {
            $lineTotal = $product->pivot->total_price;
            $discountAmount = $product->pivot->discount_amount ?? 0;
            $finalLineTotal = $lineTotal - $discountAmount;

            $subtotal += $finalLineTotal;
            $totalDiscountAmount += $discountAmount;

            if ($product->tax) {
                $taxAmount += ($finalLineTotal * $product->tax->rate) / 100;
            }
        }

        $totalAmount = $subtotal + $taxAmount - $this->discount_amount;

        $this->update([
            'subtotal' => $subtotal,
            'discount_amount' => $totalDiscountAmount,
            'total_amount' => $totalAmount
        ]);

        return $totalAmount;
    }
}
