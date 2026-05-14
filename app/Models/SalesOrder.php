<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\SalesOrderObserver;

#[ObservedBy([SalesOrderObserver::class])]
class SalesOrder extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'name',
        'description',
        'quote_id',
        'account_id',
        'contact_id',
        'billing_contact_id',
        'shipping_contact_id',
        'shipping_provider_type_id',
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
        'order_date',
        'delivery_date',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($salesOrder) {
            if (empty($salesOrder->order_number)) {
                $salesOrder->order_number = 'SO-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
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

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getProductCountAttribute()
    {
        return $this->products()->sum('sales_order_products.quantity');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'sales_order_products')
            ->withPivot('quantity', 'unit_price', 'total_price', 'discount_type', 'discount_value', 'discount_amount')
            ->withTimestamps();
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

        $totalAmount = $subtotal + $taxAmount + ($this->shipping_amount ?? 0);

        $this->update([
            'subtotal' => $subtotal,
            'discount_amount' => $totalDiscountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount
        ]);

        return $totalAmount;
    }

    public function activities(): HasMany
    {
        return $this->hasMany(SalesOrderActivity::class)->with('user')->orderBy('created_at', 'desc');
    }
}
