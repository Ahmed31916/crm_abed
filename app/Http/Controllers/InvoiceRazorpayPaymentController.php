<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Razorpay\Api\Api;

class InvoiceRazorpayPaymentController extends Controller
{
    public function createOrder(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request);

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $companyId = $invoice->created_by;
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (!isset($settings['payment_settings']['razorpay_key']) || !isset($settings['payment_settings']['razorpay_secret'])) {
                return response()->json(['error' => __('Razorpay not configured')], 400);
            }

            $api = new Api($settings['payment_settings']['razorpay_key'], $settings['payment_settings']['razorpay_secret']);
            $currency = $settings['general_settings']['defaultCurrency'] ?? 'INR';
            // Convert to smallest unit (paise) and ensure it's an integer
            $amount = floatval($validated['amount']);
            $amountInSmallestUnit = round($amount * 100);
            
            // Check Razorpay amount limits (minimum 1 INR, maximum 15,00,000 INR)
            if ($amountInSmallestUnit < 100) {
                return response()->json(['error' => __('Minimum payment amount is ₹1')], 400);
            }
            if ($amountInSmallestUnit > 15000000) {
                return response()->json(['error' => __('Maximum payment amount is ₹1,50,000')], 400);
            }

            $orderData = [
                'receipt' => 'invoice_' . $invoice->id . '_' . time(),
                'amount' => $amountInSmallestUnit,
                'currency' => $currency,
                'notes' => [
                    'invoice_id' => $invoice->id,
                    'payment_type' => $validated['payment_type'],
                ]
            ];

            $razorpayOrder = $api->order->create($orderData);

            return response()->json([
                'order_id' => $razorpayOrder->id,
                'amount' => (int)$amountInSmallestUnit,
            ]);
        } catch (\Exception $e) {
            \Log::error('Razorpay order creation failed', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function processPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request, [
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);

            // Auto-correct payment type if partial payment equals remaining balance
            $remainingAmount = $invoice->getRemainingAmount();
            if ($validated['payment_type'] === 'partial' && $validated['amount'] == $remainingAmount) {
                $validated['payment_type'] = 'full';
            }

            // Validate payment amount and type
            $validation = $invoice->validatePaymentAmount($validated['amount'], $validated['payment_type']);
            if (!$validation['valid']) {
                return back()->withErrors(['error' => $validation['message']]);
            }

            $companyId = $invoice->created_by;
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (!isset($settings['payment_settings']['razorpay_key']) || !isset($settings['payment_settings']['razorpay_secret'])) {
                \Log::error('Razorpay payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return back()->withErrors(['error' => __('Razorpay not configured')]);
            }

            $api = new Api($settings['payment_settings']['razorpay_key'], $settings['payment_settings']['razorpay_secret']);

            // Verify payment signature
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $validated['razorpay_order_id'],
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
                'razorpay_signature' => $validated['razorpay_signature']
            ]);

            // Store invoice payment using common method
            InvoicePayment::storePayment([
                'invoice_id' => $validated['invoice_id'],
                'amount' => $validated['amount'],
                'payment_type' => $validated['payment_type'],
                'payment_method' => 'razorpay',
                'payment_id' => $validated['razorpay_payment_id'],
            ]);

            \Log::info('Razorpay payment successful', [
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
                'payment_type' => $validated['payment_type'],
                'payment_id' => $validated['razorpay_payment_id']
            ]);

            return back()->with('success', __('Payment successful'));

        } catch (\Exception $e) {
            \Log::error('Razorpay payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleInvoicePaymentError($e, 'razorpay');
        }
    }

    private function validateInvoicePaymentRequest($request, $additionalRules = [])
    {
        $baseRules = [
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_type' => 'required|in:full,partial',
        ];

        return $request->validate(array_merge($baseRules, $additionalRules));
    }

    private function getInvoicePaymentSettings($companyId)
    {
        return [
            'payment_settings' => PaymentSetting::getUserSettings($companyId),
            'general_settings' => \App\Models\Setting::getUserSettings($companyId),
        ];
    }

    private function handleInvoicePaymentError($e, $method = 'razorpay')
    {
        return back()->withErrors(['error' => __('Payment processing failed. Please try again or contact support.')]);
    }
}
