<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;

class InvoicePaystackPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request, [
            'payment_id' => 'required|string',
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

            if (!isset($settings['payment_settings']['paystack_secret_key'])) {
                \Log::error('Paystack payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return back()->withErrors(['error' => __('Paystack not configured')]);
            }

            // Verify payment with Paystack API
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . $validated['payment_id'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $settings['payment_settings']['paystack_secret_key'],
                    "Cache-Control: no-cache",
                ],
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $result = json_decode($response, true);

            if ($result['status'] && $result['data']['status'] === 'success') {
                // Verify amount matches
                $paystackAmount = $result['data']['amount'] / 100; // Convert from kobo to naira
                if (abs($paystackAmount - $validated['amount']) > 0.01) {
                    \Log::error('Paystack amount mismatch', [
                        'invoice_id' => $invoice->id,
                        'expected' => $validated['amount'],
                        'received' => $paystackAmount
                    ]);
                    return back()->withErrors(['error' => __('Payment amount mismatch')]);
                }

                InvoicePayment::storePayment([
                    'invoice_id' => $validated['invoice_id'],
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'payment_method' => 'paystack',
                    'payment_id' => $validated['payment_id'],
                ]);

                \Log::info('Paystack payment successful', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'payment_id' => $validated['payment_id']
                ]);

                return back()->with('success', __('Payment successful'));
            }

            \Log::warning('Paystack payment verification failed', [
                'invoice_id' => $invoice->id,
                'payment_id' => $validated['payment_id'],
                'result' => $result
            ]);
            return back()->withErrors(['error' => __('Payment verification failed')]);

        } catch (\Exception $e) {
            \Log::error('Paystack payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleInvoicePaymentError($e, 'paystack');
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

    private function handleInvoicePaymentError($e, $method = 'paystack')
    {
        return back()->withErrors(['error' => __('Payment processing failed. Please try again or contact support.')]);
    }
}