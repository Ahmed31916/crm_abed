<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;

class InvoicePaymentWallPaymentController extends Controller
{
    private function getPaymentWallCredentials($companyId)
    {
        $settings = $this->getInvoicePaymentSettings($companyId);

        return [
            'public_key' => $settings['payment_settings']['paymentwall_public_key'] ?? null,
            'private_key' => $settings['payment_settings']['paymentwall_private_key'] ?? null,
            'mode' => $settings['payment_settings']['paymentwall_mode'] ?? 'sandbox',
            'currency' => $settings['general_settings']['defaultCurrency'] ?? 'USD'
        ];
    }

    public function createPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request);

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $credentials = $this->getPaymentWallCredentials($invoice->created_by);

            if (!$credentials['public_key']) {
                return response()->json(['success' => false, 'message' => __('PaymentWall not configured.')], 400);
            }

            $validation = $invoice->validatePaymentAmount($validated['amount'], $validated['payment_type']);
            if (!$validation['valid']) {
                return response()->json(['success' => false, 'message' => $validation['message']], 400);
            }

            $isTestMode = $credentials['mode'] === 'sandbox';

            return response()->json([
                'success' => true,
                'brick_config' => [
                    'public_key' => $credentials['public_key'],
                    'amount' => $validated['amount'],
                    'currency' => $credentials['currency'],
                    'description' => "Invoice #{$invoice->invoice_number} - " . ucfirst($validated['payment_type']) . ' payment',
                    'invoice_id' => $invoice->id,
                    'payment_type' => $validated['payment_type'],
                    'test_mode' => $isTestMode
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('PaymentWall invoice payment creation error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => __('Payment creation failed.')], 500);
        }
    }

    public function processPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'invoice_id' => 'required|exists:invoices,id',
                'amount' => 'required|numeric|min:0.01',
                'payment_type' => 'required|in:full,partial',
                'brick_token' => 'required|string',
                'brick_fingerprint' => 'required|string',
            ]);

            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $credentials = $this->getPaymentWallCredentials($invoice->created_by);

            if (!$credentials['private_key']) {
                return response()->json(['success' => false, 'message' => __('PaymentWall not configured.')], 400);
            }

            $validation = $invoice->validatePaymentAmount($validated['amount'], $validated['payment_type']);
            if (!$validation['valid']) {
                return response()->json(['success' => false, 'message' => $validation['message']], 400);
            }

            $isTestMode = $credentials['mode'] === 'sandbox';

            $chargeData = [
                'token' => $validated['brick_token'],
                'fingerprint' => $validated['brick_fingerprint'],
                'amount' => $validated['amount'],
                'currency' => $credentials['currency'],
                'email' => $invoice->billing_email ?? 'customer@example.com',
                'description' => "Invoice #{$invoice->invoice_number} - " . ucfirst($validated['payment_type']) . ' payment',
                'uid' => $invoice->id,
                'test_mode' => $isTestMode ? 1 : 0,
            ];

            $response = $this->processCharge($chargeData, $credentials['private_key']);

            if ($response && isset($response['type']) && $response['type'] === 'Charge' && $response['captured']) {
                InvoicePayment::storePayment([
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'payment_method' => 'paymentwall',
                    'payment_id' => $response['id'] ?? 'brick_' . time(),
                ]);

                \Log::info('PaymentWall invoice payment successful', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_id' => $response['id'] ?? 'brick_' . time()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => __('Payment completed successfully!')
                ]);
            } else {
                $errorMessage = $response['error'] ?? __('Payment processing failed');
                return response()->json(['success' => false, 'message' => $errorMessage], 400);
            }

        } catch (\Exception $e) {
            \Log::error('PaymentWall invoice payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => __('Payment processing failed.')], 500);
        }
    }

    public function callback(Request $request)
    {
        try {
            $params = $request->all();
            
            // Get credentials from first available company (admin)
            $settings = PaymentSetting::getUserSettings(1);
            $privateKey = $settings['paymentwall_private_key'] ?? '';

            if (!$this->validatePingback($params, $privateKey)) {
                \Log::error('PaymentWall invoice callback: Invalid signature');
                return response('Invalid signature', 400);
            }

            $uid = $request->input('uid'); // This is invoice_id
            $type = $request->input('type');
            $ref = $request->input('ref');

            if ($type === '0' && $uid) { // Type 0 = payment successful
                $invoice = Invoice::find($uid);

                if ($invoice) {
                    $existingPayment = InvoicePayment::where('payment_id', $ref)->first();

                    if (!$existingPayment) {
                        $amount = floatval($request->input('amount') ?? 0);
                        $paymentType = $amount >= $invoice->total_amount ? 'full' : 'partial';

                        InvoicePayment::storePayment([
                            'invoice_id' => $invoice->id,
                            'amount' => $amount,
                            'payment_type' => $paymentType,
                            'payment_method' => 'paymentwall',
                            'payment_id' => $ref,
                        ]);

                        \Log::info('PaymentWall invoice payment successful via webhook', [
                            'invoice_id' => $invoice->id,
                            'payment_id' => $ref,
                            'amount' => $amount
                        ]);
                    }
                }
            }

            return response('OK', 200);

        } catch (\Exception $e) {
            \Log::error('PaymentWall invoice callback error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response('ERROR', 500);
        }
    }

    private function validatePingback($params, $secretKey)
    {
        $signature = $params['sig'] ?? '';
        unset($params['sig']);

        $str = '';
        ksort($params);
        foreach ($params as $key => $value) {
            $str .= $key . '=' . $value;
        }
        $str .= $secretKey;

        return md5($str) === $signature;
    }

    private function processCharge($chargeData, $privateKey)
    {
        try {
            $url = 'https://api.paymentwall.com/api/brick/charge';
            $chargeData['key'] = $privateKey;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($chargeData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return null;
            }

            return json_decode($response, true);

        } catch (\Exception $e) {
            return null;
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
