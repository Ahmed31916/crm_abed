<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class InvoiceCashfreePaymentController extends Controller
{
    public function createPaymentSession(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request);

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);

            $remainingAmount = $invoice->getRemainingAmount();
            if ($validated['payment_type'] === 'partial' && $validated['amount'] == $remainingAmount) {
                $validated['payment_type'] = 'full';
            }

            $validation = $invoice->validatePaymentAmount($validated['amount'], $validated['payment_type']);
            if (!$validation['valid']) {
                return response()->json(['error' => $validation['message']], 400);
            }

            $companyId = $invoice->created_by;
            $company = User::findOrFail($companyId);
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (!isset($settings['payment_settings']['cashfree_public_key']) || !isset($settings['payment_settings']['cashfree_secret_key'])) {
                \Log::error('Cashfree payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return response()->json(['error' => __('Cashfree not configured')], 400);
            }

            $amount = (float)$validated['amount'];
            if ($amount < 1) {
                return response()->json(['error' => __('Order amount must be at least 1 INR')], 400);
            }

            $orderId = 'inv_' . $invoice->id . '_' . time() . '_' . uniqid();

            $phone = $invoice->phone ?: '9999999999';
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone) !== 10) {
                $phone = '9999999999';
            }

            $orderData = [
                'order_id' => $orderId,
                'order_amount' => $amount,
                'order_currency' => 'INR',
                'customer_details' => [
                    'customer_id' => 'inv_customer_' . $invoice->id,
                    'customer_name' => $invoice->name ?: 'Customer',
                    'customer_email' => $invoice->email ?: $company->email,
                    'customer_phone' => $phone
                ],
                'order_meta' => [
                    'return_url' => route('invoices.public', encrypt($invoice->id)),
                    'notify_url' => route('invoice.cashfree.webhook')
                ],
                'order_note' => 'Invoice Payment - ' . $invoice->invoice_number,
                'order_tags' => [
                    'invoice_id' => (string)$invoice->id,
                    'amount' => (string)$validated['amount'],
                    'payment_type' => (string)$validated['payment_type']
                ]
            ];

            $responseData = $this->makeCashfreeApiCall('post', '/orders', $orderData, $settings['payment_settings']);

            return response()->json([
                'payment_session_id' => $responseData['payment_session_id'],
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => 'INR',
                'mode' => $settings['payment_settings']['cashfree_mode']
            ]);

        } catch (\Exception $e) {
            \Log::error('Cashfree payment session creation failed', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => __('Failed to create payment session: ') . $e->getMessage()], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request, [
            'order_id' => 'required|string',
        ]);

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $companyId = $invoice->created_by;
            $settings = $this->getInvoicePaymentSettings($companyId);

            $orderData = $this->makeCashfreeApiCall('get', '/orders/' . $validated['order_id'], null, $settings['payment_settings']);

            if ($orderData['order_status'] !== 'PAID') {
                return response()->json(['error' => __('Payment not completed successfully')], 400);
            }

            $payments = $this->makeCashfreeApiCall('get', '/orders/' . $validated['order_id'] . '/payments', null, $settings['payment_settings']);
            $successfulPayment = null;

            foreach ($payments as $payment) {
                if ($payment['payment_status'] === 'SUCCESS') {
                    $successfulPayment = $payment;
                    break;
                }
            }

            if (!$successfulPayment) {
                return response()->json(['error' => __('No successful payment found for this order')], 400);
            }

            InvoicePayment::storePayment([
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
                'payment_type' => $validated['payment_type'],
                'payment_method' => 'cashfree',
                'payment_id' => $successfulPayment['cf_payment_id'],
            ]);

            \Log::info('Cashfree invoice payment successful', [
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
                'payment_type' => $validated['payment_type']
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            \Log::error('Cashfree payment verification failed', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => __('Payment verification failed: ') . $e->getMessage()], 500);
        }
    }

    public function webhook(Request $request)
    {
        try {
            $signature = $request->header('x-webhook-signature');
            $timestamp = $request->header('x-webhook-timestamp');
            $rawBody = $request->getContent();

            $data = $request->json()->all();

            if ($data['type'] === 'PAYMENT_SUCCESS_WEBHOOK') {
                $paymentData = $data['data'];
                $orderTags = $paymentData['order']['order_tags'] ?? [];

                if (isset($orderTags['invoice_id'])) {
                    $invoice = Invoice::find($orderTags['invoice_id']);

                    if ($invoice) {
                        InvoicePayment::storePayment([
                            'invoice_id' => $invoice->id,
                            'amount' => $orderTags['amount'],
                            'payment_type' => $orderTags['payment_type'],
                            'payment_method' => 'cashfree',
                            'payment_id' => $paymentData['cf_payment_id'],
                        ]);

                        \Log::info('Cashfree invoice payment webhook successful', [
                            'invoice_id' => $invoice->id
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            \Log::error('Cashfree webhook error', ['error' => $e->getMessage()]);
            return response()->json(['error' => __('Webhook processing failed')], 500);
        }
    }

    private function makeCashfreeApiCall($method, $endpoint, $data, $paymentSettings)
    {
        $modeValue = $paymentSettings['cashfree_mode'] ?? 'sandbox';
        $mode = ($modeValue === 0 || $modeValue === '0' || $modeValue === 'sandbox') ? 'sandbox' : 'production';
        $baseUrl = $mode === 'production' ? 'https://api.cashfree.com/pg' : 'https://sandbox.cashfree.com/pg';

        $headers = [
            'x-client-id' => $paymentSettings['cashfree_public_key'],
            'x-client-secret' => $paymentSettings['cashfree_secret_key'],
            'x-api-version' => '2023-08-01'
        ];

        if ($data) {
            $headers['Content-Type'] = 'application/json';
        }

        $url = $baseUrl . $endpoint;
        $response = Http::withHeaders($headers)->$method($url, $data);

        if (!$response->successful()) {
            throw new \Exception('API Error: ' . $response->body());
        }

        return $response->json();
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
