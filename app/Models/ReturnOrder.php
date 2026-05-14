<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_number',
        'name',
        'description',
        'sales_order_id',
        'account_id',
        'contact_id',
        'shipping_provider_type_id',
        'tracking_number',
        'status',
        'reason',
        'reason_description',
        'return_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'notes',
        'created_by',
        'assigned_to',
    ];

    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($returnOrder) {
            if (empty($returnOrder->return_number)) {
                $returnOrder->return_number = 'RET-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
            $faker = \Faker\Factory::create();
            if (empty($returnOrder->tracking_number)) {
                $returnOrder->tracking_number = strtoupper($faker->unique()->bothify('RET########'));
            }
        });
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function shippingProviderType()
    {
        return $this->belongsTo(ShippingProviderType::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'return_order_product')
                    ->withPivot('quantity', 'unit_price', 'total_price')
                    ->withTimestamps();
    }

    public function calculateTotals()
    {
        $subtotal = $this->products->sum(function ($product) {
            return $product->pivot->total_price;
        });

        $taxAmount = $this->products->sum(function ($product) {
            if ($product->tax) {
                return ($product->pivot->total_price * $product->tax->rate) / 100;
            }
            return 0;
        });

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount,
        ]);
    }
}
