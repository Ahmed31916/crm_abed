<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\Log;

class InvoicePaymentService
{
    /**
     * Process a payment for an invoice
     */
    public function processPayment(array $paymentData)
    {
        $invoice = Invoice::findOrFail($paymentData['invoice_id']);
        
        // Validate payment amount and type
        $validation = $invoice->validatePaymentAmount($paymentData['amount'], $paymentData['payment_type']);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException($validation['message']);
        }
        
        // Create payment record
        $payment = InvoicePayment::create([
            'invoice_id' => $paymentData['invoice_id'],
            'amount' => $paymentData['amount'],
            'payment_type' => $paymentData['payment_type'],
            'payment_method' => $paymentData['payment_method'],
            'payment_id' => $paymentData['payment_id'],
            'status' => $paymentData['status'] ?? 'completed',
            'processed_at' => $paymentData['status'] === 'completed' ? now() : null,
            'notes' => $paymentData['notes'] ?? "Invoice #{$invoice->invoice_number} - {$paymentData['payment_type']} payment",
        ]);
        
        // Update invoice status if payment is completed
        if ($payment->status === 'completed') {
            $invoice->updatePaymentStatus();
        }
        
        // Log payment activity
        Log::info('Invoice payment processed', [
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'payment_type' => $payment->payment_type,
            'payment_method' => $payment->payment_method,
            'status' => $payment->status
        ]);
        
        return $payment;
    }
    
    /**
     * Approve a pending payment
     */
    public function approvePayment($paymentId, $notes = null)
    {
        $payment = InvoicePayment::where('payment_id', $paymentId)
            ->where('status', 'pending')
            ->firstOrFail();
            
        $payment->update([
            'status' => 'completed',
            'processed_at' => now(),
            'notes' => $payment->notes . ($notes ? ' | Approved: ' . $notes : ' | Approved')
        ]);
        
        // Update invoice status
        $payment->invoice->updatePaymentStatus();
        
        Log::info('Payment approved', [
            'payment_id' => $paymentId,
            'invoice_id' => $payment->invoice_id,
            'amount' => $payment->amount
        ]);
        
        return $payment;
    }
    
    /**
     * Reject a pending payment
     */
    public function rejectPayment($paymentId, $reason = null)
    {
        $payment = InvoicePayment::where('payment_id', $paymentId)
            ->where('status', 'pending')
            ->firstOrFail();
            
        $payment->update([
            'status' => 'failed',
            'notes' => $payment->notes . ($reason ? ' | Rejected: ' . $reason : ' | Rejected')
        ]);
        
        Log::info('Payment rejected', [
            'payment_id' => $paymentId,
            'invoice_id' => $payment->invoice_id,
            'reason' => $reason
        ]);
        
        return $payment;
    }
    
    /**
     * Get payment summary for an invoice
     */
    public function getPaymentSummary($invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        
        return [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'total_amount' => $invoice->total_amount,
            'total_paid' => $invoice->getTotalPaidAmount(),
            'remaining_amount' => $invoice->getRemainingAmount(),
            'is_fully_paid' => $invoice->isFullyPaid(),
            'is_partially_paid' => $invoice->isPartiallyPaid(),
            'payment_status' => $invoice->status,
            'payments' => $invoice->payments()->orderBy('created_at', 'desc')->get()
        ];
    }
    
    /**
     * Validate payment method configuration
     */
    public function validatePaymentMethodConfig($paymentMethod, $companyId)
    {
        $settings = \App\Models\PaymentSetting::getUserSettings($companyId);
        switch ($paymentMethod) {
            case 'stripe':
                return isset($settings['stripe_secret']) && isset($settings['stripe_key']) 
                    && !empty($settings['stripe_secret']) && !empty($settings['stripe_key']);
                    
            case 'paypal':
                return isset($settings['paypal_client_id']) && isset($settings['paypal_client_secret'])
                    && !empty($settings['paypal_client_id']) && !empty($settings['paypal_client_secret']);
                    
            case 'bank':
                return isset($settings['bank_detail']) && !empty($settings['bank_detail']);
                
            case 'skrill':
                return isset($settings['skrill_merchant_id']) && !empty($settings['skrill_merchant_id']);
                
            case 'razorpay':
                return isset($settings['razorpay_key']) && isset($settings['razorpay_secret'])
                    && !empty($settings['razorpay_key']) && !empty($settings['razorpay_secret']);
                    
            case 'mercadopago':
                return isset($settings['mercadopago_access_token']) && !empty($settings['mercadopago_access_token']);
                
            case 'paystack':
                return isset($settings['paystack_public_key']) && isset($settings['paystack_secret_key'])
                    && !empty($settings['paystack_public_key']) && !empty($settings['paystack_secret_key']);
                    
            case 'flutterwave':
                return isset($settings['flutterwave_public_key']) && isset($settings['flutterwave_secret_key'])
                    && !empty($settings['flutterwave_public_key']) && !empty($settings['flutterwave_secret_key']);
                    
            case 'paytabs':
                return isset($settings['paytabs_server_key']) && isset($settings['paytabs_profile_id'])
                    && !empty($settings['paytabs_server_key']) && !empty($settings['paytabs_profile_id']);
                    
            case 'coingate':
                return isset($settings['coingate_api_token']) && !empty($settings['coingate_api_token']);
                
            case 'payfast':
                return isset($settings['payfast_merchant_id']) && isset($settings['payfast_merchant_key'])
                    && !empty($settings['payfast_merchant_id']) && !empty($settings['payfast_merchant_key']);
                
            case 'tap':
                return isset($settings['tap_secret_key']) && !empty($settings['tap_secret_key']);
                
            case 'xendit':
                return isset($settings['xendit_api_key']) && !empty($settings['xendit_api_key']);
                
            case 'paytr':
                return isset($settings['paytr_merchant_id']) && isset($settings['paytr_merchant_key']) && isset($settings['paytr_merchant_salt'])
                    && !empty($settings['paytr_merchant_id']) && !empty($settings['paytr_merchant_key']) && !empty($settings['paytr_merchant_salt']);
                
            case 'mollie':
                return isset($settings['mollie_api_key']) && !empty($settings['mollie_api_key']);
                
            case 'toyyibpay':
                return isset($settings['toyyibpay_secret_key']) && isset($settings['toyyibpay_category_code'])
                    && !empty($settings['toyyibpay_secret_key']) && !empty($settings['toyyibpay_category_code']);
                
            case 'paymentwall':
                return isset($settings['paymentwall_public_key']) && isset($settings['paymentwall_private_key'])
                    && !empty($settings['paymentwall_public_key']) && !empty($settings['paymentwall_private_key']);
                
            case 'sspay':
                return isset($settings['sspay_secret_key']) && isset($settings['sspay_category_code'])
                    && !empty($settings['sspay_secret_key']) && !empty($settings['sspay_category_code']);
                
            case 'iyzipay':
                return isset($settings['iyzipay_public_key']) && isset($settings['iyzipay_secret_key'])
                    && !empty($settings['iyzipay_public_key']) && !empty($settings['iyzipay_secret_key']);
                
            case 'aamarpay':
                return isset($settings['aamarpay_store_id']) && isset($settings['aamarpay_signature'])
                    && !empty($settings['aamarpay_store_id']) && !empty($settings['aamarpay_signature']);
                
            case 'midtrans':
                return isset($settings['midtrans_secret_key']) && !empty($settings['midtrans_secret_key']);
                
            default:
                return false;
        }
    }
}