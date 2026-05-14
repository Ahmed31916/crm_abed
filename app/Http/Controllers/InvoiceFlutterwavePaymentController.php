<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;

class InvoiceFlutterwavePaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request, [
            'payment_id' => 'required',
            'tx_ref' => 'required|string',
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

            if (!isset($settings['payment_settings']['flutterwave_secret_key'])) {
                \Log::error('Flutterwave payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return back()->withErrors(['error' => __('Flutterwave not configured')]);
            }

            // Verify payment with Flutterwave API
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/" . $validated['payment_id'] . "/verify",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $settings['payment_settings']['flutterwave_secret_key'],
                    "Content-Type: application/json",
                ],
            ));

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpCode !== 200) {
                \Log::error('Flutterwave API error', [
                    'invoice_id' => $invoice->id,
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
                return back()->withErrors(['error' => __('Payment verification failed - API error')]);
            }

            $result = json_decode($response, true);

            if (!$result) {
                \Log::error('Flutterwave invalid response', ['invoice_id' => $invoice->id]);
                return back()->withErrors(['error' => __('Payment verification failed - Invalid response')]);
            }

            if ($result['status'] === 'success' && $result['data']['status'] === 'successful') {
                // Verify amount matches
                $paidAmount = $result['data']['amount'];
                if (abs($paidAmount - $validated['amount']) > 0.01) {
                    \Log::error('Flutterwave amount mismatch', [
                        'invoice_id' => $invoice->id,
                        'expected' => $validated['amount'],
                        'received' => $paidAmount
                    ]);
                    return back()->withErrors(['error' => __('Payment amount verification failed')]);
                }

                InvoicePayment::storePayment([
                    'invoice_id' => $validated['invoice_id'],
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'payment_method' => 'flutterwave',
                    'payment_id' => $validated['payment_id'],
                ]);

                \Log::info('Flutterwave payment successful', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'payment_id' => $validated['payment_id']
                ]);

                return back()->with('success', __('Payment successful'));
            }

            \Log::warning('Flutterwave payment verification failed', [
                'invoice_id' => $invoice->id,
                'payment_id' => $validated['payment_id'],
                'result' => $result
            ]);
            return back()->withErrors(['error' => __('Payment verification failed')]);

        } catch (\Exception $e) {
            \Log::error('Flutterwave payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleInvoicePaymentError($e, 'flutterwave');
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

    private function handleInvoicePaymentError($e, $method = 'flutterwave')
    {
        return back()->withErrors(['error' => __('Payment processing failed. Please try again or contact support.')]);
    }
}