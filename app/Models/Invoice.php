<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'name',
        'description',
        'sales_order_id',
        'quote_id',
        'opportunity_id',
        'account_id',
        'contact_id',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'notes',
        'terms',
        'payment_method',
        'created_by',
        'assigned_to',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = 'INV-' . date('Y') . '-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
        });

        static::updating(function ($invoice) {
            if ($invoice->isDirty('status')) {
                $original = $invoice->getOriginal();
                if ($original['status'] !== 'cancelled') {
                    static::restoreInventory($invoice);
                }
                if ($invoice->status !== 'cancelled') {
                    static::updateInventory($invoice);
                }
            }
        });
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
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
        return $this->belongsTo(Contact::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getInvoiceTemplate()
    {
        return (int) getSetting('invoiceTemplate', 1, $this->created_by);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'invoice_products')
            ->withPivot('quantity', 'unit_price', 'total_price', 'discount_type', 'discount_value', 'discount_amount')
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(InvoiceActivity::class)->with('user')->orderBy('created_at', 'desc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(InvoiceComment::class)->orderBy('created_at', 'desc');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(InvoiceReminder::class)->orderBy('created_at', 'desc');
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

        $totalAmount = $subtotal + $taxAmount;

        $this->update([
            'subtotal' => $subtotal,
            'discount_amount' => $totalDiscountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount
        ]);

        return $totalAmount;
    }

    public function getTotalPaidAmount()
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function getRemainingAmount()
    {
        return max(0, $this->total_amount - $this->getTotalPaidAmount());
    }

    public function isFullyPaid()
    {
        return $this->getTotalPaidAmount() >= $this->total_amount;
    }

    public function isPartiallyPaid()
    {
        $totalPaid = $this->getTotalPaidAmount();
        return $totalPaid > 0 && $totalPaid < $this->total_amount;
    }

    public function validatePaymentAmount($amount, $paymentType)
    {
        $remainingAmount = $this->getRemainingAmount();

        if ($amount <= 0) {
            return ['valid' => false, 'message' => 'Payment amount must be greater than zero'];
        }

        if ($amount > $remainingAmount) {
            return ['valid' => false, 'message' => 'Payment amount cannot exceed remaining balance'];
        }

        if ($paymentType === 'full' && $amount != $remainingAmount) {
            return ['valid' => false, 'message' => 'Full payment must equal the remaining balance'];
        }

        if ($paymentType === 'partial' && $amount > $remainingAmount) {
            return ['valid' => false, 'message' => 'Partial payment cannot exceed remaining balance'];
        }

        return ['valid' => true, 'message' => 'Payment amount is valid'];
    }

    public function updatePaymentStatus()
    {
        if ($this->isFullyPaid()) {
            $this->update(['status' => 'paid']);
        } elseif ($this->isPartiallyPaid()) {
            $this->update(['status' => 'partially_paid']);
        }
    }

    public static function updateInventory($invoice)
    {
        \Log::info("Updating inventory for Invoice ID: " . $invoice->id);
        foreach ($invoice->products as $product) {
            $quantity = $product->pivot->quantity;
            $product->decrement('stock_quantity', $quantity);
            \Log::info("Product ID: " . $product->id . " Stock Quantity: " . $product->stock_quantity);
        }
    }

    public static function restoreInventory($invoice)
    {
        foreach ($invoice->products as $product) {
            $quantity = $product->pivot->quantity;
            $product->increment('stock_quantity', $quantity);
        }
    }
}
