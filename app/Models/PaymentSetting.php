<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class PaymentSetting extends Model
{
    protected $fillable = ['user_id', 'key', 'value'];

    protected $casts = [
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setValueAttribute($value)
    {
        $this->attributes['value'] = is_bool($value) ? ($value ? '1' : '0') : $value;
    }

    public function getValueAttribute($value)
    {
        $booleanKeys = [
            'is_manually_enabled', 
            'is_bank_enabled', 
            'is_stripe_enabled', 
            'is_paypal_enabled', 
            'is_razorpay_enabled',
            'is_mercadopago_enabled',
            'is_paystack_enabled',
            'is_flutterwave_enabled',
            'is_paytabs_enabled',
            'is_skrill_enabled',
            'is_coingate_enabled',
            'is_payfast_enabled',
            'is_tap_enabled',
            'is_xendit_enabled',
            'is_paytr_enabled',
            'is_mollie_enabled',
            'is_toyyibpay_enabled',
            'is_paymentwall_enabled',
            'is_sspay_enabled',
            'is_benefit_enabled',
            'is_iyzipay_enabled',
            'is_aamarpay_enabled',
            'is_midtrans_enabled',
            'is_yookassa_enabled',
            'is_nepalste_enabled',
            'is_paiement_enabled',
            'is_cinetpay_enabled',
            'is_payhere_enabled',
            'is_fedapay_enabled',
            'is_authorizenet_enabled',
            'is_khalti_enabled',
            'is_easebuzz_enabled',
            'is_ozow_enabled',
            'is_cashfree_enabled'
        ];
        
        if (isset($this->key) && in_array($this->key, $booleanKeys)) {
            return $value === '1' || $value === 1 || $value === true;
        }
        
        return $value;
    }

    public static function updateOrCreateSetting($userId, $key, $value)
    {
        return self::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }

    public static function getUserSettings($userId)
    {
        if (!$userId) {
            return [];
        }
        
        return self::where('user_id', $userId)->pluck('value', 'key')->toArray();
    }
}