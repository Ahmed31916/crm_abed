<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceCinetPayPaymentController extends Controller
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
            $company = User::findOrFail($companyId);
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (!isset($settings['payment_settings']['cinetpay_site_id']) || !isset($settings['payment_settings']['cinetpay_api_key'])) {
                \Log::error('CinetPay payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return response()->json(['error' => __('CinetPay not configured')], 400);
            }

            $transactionId = 'inv_' . $invoice->id . '_' . time();

            $apiData = [
                'apikey' => $settings['payment_settings']['cinetpay_api_key'],
                'site_id' => $settings['payment_settings']['cinetpay_site_id'],
                'transaction_id' => $transactionId,
                'amount' => (int) ($validated['amount']),
                'currency' => 'XOF',
                'description' => 'Invoice Payment - ' . $invoice->invoice_number . ' - ' . ucfirst($validated['payment_type']) . ' payment',
                'notify_url' => route('invoice.cinetpay.callback'),
                'return_url' => route('invoice.cinetpay.success', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type']
                ]),
                'channels' => 'ALL',
                'metadata' => json_encode([
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                ]),
                'customer_name' => $invoice->name ?? 'Customer',
                'customer_surname' => $invoice->name ?? 'User',
                'customer_email' => $invoice->email ?? $company->email,
                'customer_phone_number' => $invoice->phone ?? '+2250000000000',
                'customer_address' => $invoice->billing_address ?? 'Abidjan',
                'customer_city' => $invoice->billing_city ?? 'Abidjan',
                'customer_country' => 'CI',
                'customer_state' => 'CI',
                'customer_zip_code' =>  preg_replace('/[\s-]/', '', $invoice->billing_postal_code) ?? '00000',
            ];

            $response = $this->callCinetPayAPI($apiData);

            if ($response && isset($response['code']) && $response['code'] === '201') {
                return response()->json([
                    'success' => true,
                    'payment_url' => $response['data']['payment_url'],
                    'payment_token' => $response['data']['payment_token'],
                    'transaction_id' => $transactionId
                ]);
            }

            \Log::error('CinetPay payment creation failed', ['invoice_id' => $invoice->id, 'response' => $response]);
            return response()->json([
                'error' => $response['message'] ?? __('Payment creation failed')
            ], 400);

        } catch (\Exception $e) {
            \Log::error('CinetPay payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    private function callCinetPayAPI($data)
    {
        $url = 'https://api-checkout.cinetpay.com/v2/payment';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: SalesyCRM/1.0'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            Log::error('CinetPay cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            Log::error('CinetPay API HTTP error: ' . $httpCode . ' Response: ' . $response);
        }

        return json_decode($response, true);
    }

    public function success(Request $request)
    {
        try {
            $invoiceId = $request->input('invoice_id');
            $amount = $request->input('amount');
            $paymentType = $request->input('payment_type');
            $transactionId = $request->input('transaction_id');

            if ($invoiceId) {
                $invoice = Invoice::find($invoiceId);

                if ($invoice) {
                    InvoicePayment::storePayment([
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_type' => $paymentType,
                        'payment_method' => 'cinetpay',
                        'payment_id' => $transactionId ?? 'CINETPAY-' . time(),
                    ]);

                    \Log::info('CinetPay invoice payment successful', [
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_type' => $paymentType
                    ]);

                    return redirect()->route('invoices.public', $invoice->id)->with('success', __('Payment successful'));
                }
            }
            return redirect()->route('invoices.public', $invoiceId)->with('error', __('Payment verification failed'));
        } catch (\Exception $e) {
            \Log::error('CinetPay success callback error', [
                'error' => $e->getMessage()
            ]);
            return redirect()->route('invoices.public', $request->input('invoice_id'))->with('error', __('Payment processing failed'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $transactionId = $request->input('cpm_trans_id');
            $result = $request->input('cpm_result');

            if ($transactionId && $result === '00') {
                $parts = explode('_', $transactionId);

                if (count($parts) >= 2) {
                    $invoiceId = $parts[1];
                    $invoice = Invoice::find($invoiceId);

                    if ($invoice) {
                        $customData = json_decode($request->input('cpm_custom'), true);

                        InvoicePayment::storePayment([
                            'invoice_id' => $invoice->id,
                            'amount' => $customData['amount'] ?? $invoice->getRemainingAmount(),
                            'payment_type' => $customData['payment_type'] ?? 'full',
                            'payment_method' => 'cinetpay',
                            'payment_id' => $transactionId,
                        ]);

                        \Log::info('CinetPay invoice payment callback successful', [
                            'invoice_id' => $invoice->id,
                            'transaction_id' => $transactionId
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            \Log::error('CinetPay callback error', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => __('Callback processing failed')], 500);
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
