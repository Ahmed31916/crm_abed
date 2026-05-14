<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReceiptOrder extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'name',
        'description',
        'purchase_order_id',
        'account_id',
        'return_order_id',
        'contact_id',
        'receipt_date',
        'expected_date',
        'status',
        'notes',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'expected_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($receiptOrder) {
            if (empty($receiptOrder->receipt_number)) {
                $receiptOrder->receipt_number = 'RO-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function returnOrder(): BelongsTo
    {
        return $this->belongsTo(ReturnOrder::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
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
        return $this->products()->sum('receipt_order_products.quantity');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'receipt_order_products')
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
}
