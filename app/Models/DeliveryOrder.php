<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DeliveryOrder extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'delivery_number',
        'name',
        'description',
        'sales_order_id',
        'account_id',
        'contact_id',
        'shipping_provider_type_id',
        'delivery_address',
        'delivery_city',
        'delivery_state',
        'delivery_postal_code',
        'delivery_country',
        'delivery_date',
        'expected_delivery_date',
        'status',
        'tracking_number',
        'delivery_notes',
        'total_weight',
        'shipping_cost',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'expected_delivery_date' => 'date',
        'total_weight' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
    ];

    protected $appends = ['formatted_status'];

    public function getFormattedStatusAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($deliveryOrder) {
            if (empty($deliveryOrder->delivery_number)) {
                $deliveryOrder->delivery_number = 'DO-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }

            $faker = \Faker\Factory::create();
            if (empty($deliveryOrder->tracking_number)) {
                $deliveryOrder->tracking_number = strtoupper($faker->unique()->bothify('??########'));
            }
        });
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
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

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'delivery_order_products')
            ->withPivot('quantity', 'unit_weight', 'total_weight')
            ->withTimestamps();
    }

    public function getProductCountAttribute()
    {
        return $this->products()->sum('delivery_order_products.quantity');
    }

    public function calculateTotalWeight()
    {
        $totalWeight = 0;

        foreach ($this->products as $product) {
            $totalWeight += $product->pivot->total_weight;
        }

        $this->update(['total_weight' => $totalWeight]);

        return $totalWeight;
    }
}
