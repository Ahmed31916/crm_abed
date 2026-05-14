<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Mollie\Api\MollieApiClient;

class InvoiceMolliePaymentController extends Controller
{
    private function getMollieCredentials($companyId)
    {
        $settings = $this->getInvoicePaymentSettings($companyId);

        return [
            'api_key' => $settings['payment_settings']['mollie_api_key'] ?? null,
            'currency' => $settings['general_settings']['defaultCurrency'] ?? 'EUR'
        ];
    }

    public function processPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request);

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $credentials = $this->getMollieCredentials($invoice->created_by);

            if (!$credentials['api_key']) {
                return response()->json(['success' => false, 'message' => __('Mollie not configured.')], 400);
            }

            $validation = $invoice->validatePaymentAmount($validated['amount'], $validated['payment_type']);
            if (!$validation['valid']) {
                return response()->json(['success' => false, 'message' => $validation['message']], 400);
            }

            $mollie = new MollieApiClient();
            $mollie->setApiKey($credentials['api_key']);

            // Create temporary payment ID for redirect
            $tempPaymentId = 'temp_' . time();

            $paymentData = [
                'amount' => [
                    'currency' => $credentials['currency'],
                    'value' => number_format($validated['amount'], 2, '.', '')
                ],
                'description' => "Invoice #{$invoice->invoice_number} - " . ucfirst($validated['payment_type']) . ' payment',
                'redirectUrl' => route('invoice.mollie.success') . '?invoice_id=' . $invoice->id . '&amount=' . $validated['amount'] . '&payment_type=' . $validated['payment_type'] . '&temp_id=' . $tempPaymentId,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'temp_id' => $tempPaymentId
                ]
            ];

            if (!str_contains(config('app.url'), 'localhost')) {
                $paymentData['webhookUrl'] = route('invoice.mollie.callback');
            }

            $payment = $mollie->payments->create($paymentData);

            \Log::info('Mollie payment created', [
                'invoice_id' => $invoice->id,
                'mollie_payment_id' => $payment->id,
                'temp_id' => $tempPaymentId
            ]);

            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'checkout_url' => $payment->getCheckoutUrl()
            ]);

        } catch (\Exception $e) {
            \Log::error('Mollie invoice payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => __('Payment processing failed.')], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $invoiceId = $request->input('invoice_id');
            $amount = $request->input('amount');
            $paymentType = $request->input('payment_type');
            $tempId = $request->input('temp_id');

            \Log::info('Mollie invoice success callback', [
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'temp_id' => $tempId,
                'all_params' => $request->all()
            ]);

            if (!$invoiceId || !$amount || !$paymentType) {
                \Log::error('Mollie invoice success: Missing parameters');
                return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Invalid payment parameters'));
            }

            $invoice = Invoice::findOrFail($invoiceId);
            $credentials = $this->getMollieCredentials($invoice->created_by);

            if (!$credentials['api_key']) {
                return redirect()->route('invoices.public', ['invoice' => encrypt($invoiceId)])->with('error', __('Payment configuration error.'));
            }

            // Find the Mollie payment by temp_id in metadata
            $mollie = new MollieApiClient();
            $mollie->setApiKey($credentials['api_key']);
            
            // List recent payments and find ours by temp_id
            $payments = $mollie->payments->page(null, 10);
            $molliePayment = null;
            
            foreach ($payments as $payment) {
                if (isset($payment->metadata->temp_id) && $payment->metadata->temp_id === $tempId) {
                    $molliePayment = $payment;
                    break;
                }
            }

            if (!$molliePayment) {
                \Log::warning('Mollie payment not found, waiting for webhook', ['temp_id' => $tempId]);
                return redirect()->route('invoices.public', ['invoice' => encrypt($invoiceId)])->with('info', __('Payment is being processed. Your invoice will be updated shortly.'));
            }

            if ($molliePayment->isPaid()) {
                $existingPayment = InvoicePayment::where('payment_id', $molliePayment->id)->first();

                if (!$existingPayment) {
                    InvoicePayment::storePayment([
                        'invoice_id' => $invoiceId,
                        'amount' => $amount,
                        'payment_type' => $paymentType,
                        'payment_method' => 'mollie',
                        'payment_id' => $molliePayment->id,
                    ]);

                    \Log::info('Mollie invoice payment successful', [
                        'invoice_id' => $invoiceId,
                        'amount' => $amount,
                        'payment_id' => $molliePayment->id
                    ]);
                }

                return redirect()->route('invoices.public', ['invoice' => encrypt($invoiceId)])->with('success', __('Payment completed successfully!'));
            } elseif ($molliePayment->status === 'pending' || $molliePayment->status === 'open') {
                return redirect()->route('invoices.public', ['invoice' => encrypt($invoiceId)])->with('info', __('Payment is being processed. Your invoice will be updated shortly.'));
            } else {
                return redirect()->route('invoices.public', ['invoice' => encrypt($invoiceId)])->with('error', __('Payment failed. Please try again.'));
            }

        } catch (\Exception $e) {
            \Log::error('Mollie invoice success callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            // Try to redirect to invoice if we have the ID
            $invoiceId = $request->input('invoice_id');
            if ($invoiceId) {
                return redirect()->route('invoices.public', ['invoice' => encrypt($invoiceId)])->with('error', __('Payment verification failed.'));
            }
            
            return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Payment verification failed.'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $paymentId = $request->input('id');

            $mollie = new MollieApiClient();

            // We need to get the invoice from payment metadata to get credentials
            // First try with a default/admin API key to fetch payment details
            $settings = PaymentSetting::getUserSettings(1); // Try admin settings first
            $apiKey = $settings['mollie_api_key'] ?? null;

            if (!$apiKey) {
                \Log::error('Mollie invoice callback: No API key found');
                return response('ERROR', 500);
            }

            $mollie->setApiKey($apiKey);
            $payment = $mollie->payments->get($paymentId);

            if ($payment->isPaid() && isset($payment->metadata->invoice_id)) {
                $invoiceId = $payment->metadata->invoice_id;
                $amount = $payment->metadata->amount;
                $paymentType = $payment->metadata->payment_type;

                // Check if payment already stored to avoid duplicates
                $existingPayment = InvoicePayment::where('payment_id', $paymentId)->first();

                if (!$existingPayment) {
                    InvoicePayment::storePayment([
                        'invoice_id' => $invoiceId,
                        'amount' => $amount,
                        'payment_type' => $paymentType,
                        'payment_method' => 'mollie',
                        'payment_id' => $paymentId,
                    ]);

                    \Log::info('Mollie invoice payment successful via webhook', [
                        'invoice_id' => $invoiceId,
                        'amount' => $amount,
                        'payment_type' => $paymentType,
                        'payment_id' => $paymentId
                    ]);
                }
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            \Log::error('Mollie invoice callback error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response('ERROR', 500);
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
}
