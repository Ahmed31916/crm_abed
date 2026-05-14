<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_type',
        'payment_method',
        'payment_id',
        'status',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public static function storePayment(array $data)
    {
        $invoice = Invoice::findOrFail($data['invoice_id']);

        static::create([
            'invoice_id' => $data['invoice_id'],
            'amount' => $data['amount'],
            'payment_type' => $data['payment_type'],
            'payment_method' => $data['payment_method'],
            'payment_id' => $data['payment_id'],
            'status' => 'completed',
            'processed_at' => now(),
            'notes' => "Invoice #{$invoice->invoice_number} - {$data['payment_type']} payment",
        ]);

        $invoice->updatePaymentStatus();
    }
}