<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;

class InvoiceKhaltiPaymentController extends Controller
{
    public function createPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request);

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
                return response()->json(['error' => $validation['message']], 400);
            }

            $companyId = $invoice->created_by;
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (!isset($settings['payment_settings']['khalti_public_key'])) {
                \Log::error('Khalti payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return response()->json(['error' => __('Khalti not configured')], 400);
            }

            return response()->json([
                'success' => true,
                'public_key' => $settings['payment_settings']['khalti_public_key'],
                'amount' => (int)($validated['amount'] * 100), // Khalti uses paisa as integer
                'product_identity' => 'invoice_' . $invoice->id,
                'product_name' => 'Invoice Payment - ' . $invoice->invoice_number,
                'product_url' => route('invoices.public', $invoice->id),
            ]);

        } catch (\Exception $e) {
            \Log::error('Khalti payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    public function processPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request, [
            'token' => 'required|string',
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

            if (!isset($settings['payment_settings']['khalti_secret_key'])) {
                \Log::error('Khalti payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return back()->withErrors(['error' => __('Khalti not configured')]);
            }

            // Verify payment with Khalti API
            $isValid = $this->verifyKhaltiPayment($validated['token'], $validated['amount'], $settings['payment_settings']);

            if ($isValid) {
                InvoicePayment::storePayment([
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'payment_method' => 'khalti',
                    'payment_id' => $validated['token'],
                ]);

                \Log::info('Khalti invoice payment successful', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'token' => $validated['token']
                ]);

                return back()->with('success', __('Payment successful'));
            }

            return back()->withErrors(['error' => __('Payment verification failed')]);

        } catch (\Exception $e) {
            \Log::error('Khalti payment processing error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => __('Payment processing failed. Please try again.')]);
        }
    }

    private function verifyKhaltiPayment($token, $amount, $settings)
    {
        try {
            $url = 'https://khalti.com/api/v2/payment/verify/';

            $data = [
                'token' => $token,
                'amount' => (int)($amount * 100), // Convert to paisa as integer
            ];

            $headers = [
                'Authorization: Key ' . $settings['khalti_secret_key'],
                'Content-Type: application/json',
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);

            return isset($result['state']['name']) && $result['state']['name'] === 'Completed';

        } catch (\Exception $e) {
            return false;
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
