<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InvoicePayTRPaymentController extends Controller
{
    private function getPayTRCredentials($companyId)
    {
        $settings = $this->getInvoicePaymentSettings($companyId);

        return [
            'merchant_id' => $settings['payment_settings']['paytr_merchant_id'] ?? null,
            'merchant_key' => $settings['payment_settings']['paytr_merchant_key'] ?? null,
            'merchant_salt' => $settings['payment_settings']['paytr_merchant_salt'] ?? null,
            'currency' => $settings['general_settings']['defaultCurrency'] ?? 'TRY'
        ];
    }

    public function createPaymentToken(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request, [
            'user_name' => 'required|string',
            'user_email' => 'required|email',
            'user_phone' => 'required|string',
            'user_address' => 'nullable|string',
        ]);

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $credentials = $this->getPayTRCredentials($invoice->created_by);

            if (!$credentials['merchant_id'] || !$credentials['merchant_key'] || !$credentials['merchant_salt']) {
                throw new \Exception(__('PayTR credentials not configured'));
            }

            // Validate payment amount and type
            $validation = $invoice->validatePaymentAmount($validated['amount'], $validated['payment_type']);
            if (!$validation['valid']) {
                return response()->json(['error' => $validation['message']], 400);
            }

            $merchant_oid = 'invoice_' . $invoice->id . '_' . time() . '_' . uniqid();
            $payment_amount = intval($validated['amount'] * 100); // Convert to kuruş
            $user_basket = json_encode([[
                "Invoice #{$invoice->invoice_number} - " . ucfirst($validated['payment_type']) . ' payment',
                number_format($validated['amount'], 2),
                1
            ]]);

            // Generate hash according to PayTR documentation
            $hashStr = $credentials['merchant_id'] .
                      $request->ip() .
                      $merchant_oid .
                      $validated['user_email'] .
                      $payment_amount .
                      $user_basket .
                      '1' . // no_installment
                      '0' . // max_installment
                      $credentials['currency'] .
                      '1' . // test_mode
                      $credentials['merchant_salt'];

            $paytr_token = base64_encode(hash_hmac('sha256', $hashStr, $credentials['merchant_key'], true));

            $post_data = [
                'merchant_id' => $credentials['merchant_id'],
                'user_ip' => $request->ip(),
                'merchant_oid' => $merchant_oid,
                'email' => $validated['user_email'],
                'payment_amount' => $payment_amount,
                'paytr_token' => $paytr_token,
                'user_basket' => $user_basket,
                'no_installment' => 1,
                'max_installment' => 0,
                'user_name' => $validated['user_name'],
                'user_address' => $validated['user_address'] ?? 'Turkey',
                'user_phone' => $validated['user_phone'],
                'merchant_ok_url' => route('invoice.paytr.success') . '?invoice_id=' . $invoice->id . '&amount=' . $validated['amount'] . '&payment_type=' . $validated['payment_type'] . '&merchant_oid=' . $merchant_oid,
                'merchant_fail_url' => route('invoice.paytr.failure') . '?invoice_id=' . $invoice->id,
                'timeout_limit' => 30,
                'currency' => $credentials['currency'],
                'test_mode' => 1
            ];

            $response = Http::asForm()->timeout(40)->post('https://www.paytr.com/odeme/api/get-token', $post_data);

            if ($response->successful()) {
                $result = $response->json();
                if ($result['status'] == 'success') {
                    return response()->json([
                        'success' => true,
                        'token' => $result['token'],
                        'iframe_url' => 'https://www.paytr.com/odeme/guvenli/' . $result['token']
                    ]);
                } else {
                    throw new \Exception($result['reason'] ?? __('Token generation failed'));
                }
            } else {
                throw new \Exception(__('PayTR API connection failed'));
            }
        } catch (\Exception $e) {
            \Log::error('PayTR invoice payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $invoiceId = $request->input('invoice_id');
            $amount = $request->input('amount');
            $paymentType = $request->input('payment_type');
            $merchantOid = $request->input('merchant_oid');

            if (!$invoiceId || !$amount || !$paymentType || !$merchantOid) {
                return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Invalid payment parameters'));
            }

            $invoice = Invoice::findOrFail($invoiceId);

            InvoicePayment::storePayment([
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'payment_method' => 'paytr',
                'payment_id' => $merchantOid,
            ]);

            \Log::info('PayTR invoice payment successful', [
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'merchant_oid' => $merchantOid
            ]);

            return redirect()->route('invoices.public', ['invoice' => encrypt($invoiceId)])->with('success', __('Payment completed successfully!'));

        } catch (\Exception $e) {
            \Log::error('PayTR invoice success callback error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Payment verification failed.'));
        }
    }

    public function failure(Request $request)
    {
        try {
            $invoiceId = $request->input('invoice_id');

            \Log::warning('PayTR invoice payment failed', [
                'invoice_id' => $invoiceId,
                'request' => $request->all()
            ]);

            if ($invoiceId) {
                return redirect()->route('invoices.public', ['invoice' => encrypt($invoiceId)])->with('error', __('Payment failed. Please try again.'));
            }

            return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Payment failed.'));
        } catch (\Exception $e) {
            return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Payment failed.'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $merchant_oid = $request->input('merchant_oid');
            $status = $request->input('status');
            $total_amount = $request->input('total_amount');
            $hash = $request->input('hash');

            // Extract invoice ID from merchant_oid
            preg_match('/invoice_(\d+)_/', $merchant_oid, $matches);
            $invoiceId = $matches[1] ?? null;

            if (!$invoiceId) {
                \Log::error('PayTR invoice callback: Could not extract invoice ID', ['merchant_oid' => $merchant_oid]);
                return response('ERROR', 500);
            }

            $invoice = Invoice::find($invoiceId);
            if (!$invoice) {
                \Log::error('PayTR invoice callback: Invoice not found', ['invoice_id' => $invoiceId]);
                return response('ERROR', 500);
            }

            $credentials = $this->getPayTRCredentials($invoice->created_by);

            // Verify hash for security
            $hashStr = $merchant_oid . $credentials['merchant_salt'] . $status . $total_amount;
            $calculatedHash = base64_encode(hash_hmac('sha256', $hashStr, $credentials['merchant_key'], true));

            if ($hash === $calculatedHash && $status === 'success') {
                \Log::info('PayTR invoice callback verified', [
                    'merchant_oid' => $merchant_oid,
                    'invoice_id' => $invoiceId,
                    'status' => $status
                ]);
            } else {
                \Log::warning('PayTR invoice callback hash mismatch or failed status', [
                    'merchant_oid' => $merchant_oid,
                    'status' => $status,
                    'hash_match' => $hash === $calculatedHash
                ]);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            \Log::error('PayTR invoice callback error', [
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
