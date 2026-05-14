<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;

class InvoiceMidtransPaymentController extends Controller
{
    public function createPayment(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_type' => 'required|in:full,partial',
        ]);

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

            if (!isset($settings['payment_settings']['midtrans_secret_key'])) {
                return response()->json(['error' => __('Midtrans not configured')], 400);
            }

            $orderId = 'invoice_' . $invoice->id . '_' . time();
            $amount = intval($validated['amount']);

            $paymentData = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amount
                ],
                'credit_card' => [
                    'secure' => true
                ],
                'customer_details' => [
                    'first_name' => $company->name ?? 'Customer',
                    'email' => $company->email,
                    'billing_address' => [
                        'address' => $invoice->billing_address ?? '',
                        'city' => $invoice->billing_city ?? '',
                        'postal_code' => $invoice->billing_postal_code ?? '',
                        'country_code' => 'IDN'
                    ]
                ],
                'item_details' => [
                    [
                        'id' => $invoice->id,
                        'price' => $amount,
                        'quantity' => 1,
                        'name' => 'Invoice #' . $invoice->invoice_number
                    ]
                ],
                'callbacks' => [
                    'finish' => route('invoice.midtrans.success', [
                        'invoice_id' => $invoice->id,
                        'amount' => $validated['amount'],
                        'payment_type' => $validated['payment_type']
                    ])
                ]
            ];

            $snapToken = $this->createSnapToken($paymentData, $settings['payment_settings']);

            if ($snapToken) {
                $baseUrl = $settings['payment_settings']['midtrans_mode'] === 'live'
                    ? 'https://app.midtrans.com'
                    : 'https://app.sandbox.midtrans.com';

                return response()->json([
                    'success' => true,
                    'snap_token' => $snapToken,
                    'payment_url' => $baseUrl . '/snap/v1/transactions/' . $snapToken,
                    'order_id' => $orderId
                ]);
            }

            throw new \Exception(__('Failed to create Midtrans snap token'));

        } catch (\Exception $e) {
            \Log::error('Midtrans invoice payment creation error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $invoiceId = $request->input('invoice_id');
            $amount = $request->input('amount');
            $paymentType = $request->input('payment_type');
            $orderId = $request->input('order_id');
            $transactionStatus = $request->input('transaction_status');

            if ($invoiceId && in_array($transactionStatus, ['capture', 'settlement'])) {
                $invoice = Invoice::find($invoiceId);

                if ($invoice) {
                    InvoicePayment::storePayment([
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_type' => $paymentType,
                        'payment_method' => 'midtrans',
                        'payment_id' => $orderId,
                    ]);

                    \Log::info('Midtrans invoice payment successful', [
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_id' => $orderId
                    ]);

                    return redirect()->route('invoices.public', encrypt($invoice->id))->with('success', __('Payment successful'));
                }
            }

            return redirect()->route('invoices.public', encrypt($invoiceId ?? 0))->withErrors(['error' => __('Payment failed or cancelled')]);

        } catch (\Exception $e) {
            \Log::error('Midtrans invoice payment success error', [
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('invoices.public', encrypt($request->input('invoice_id') ?? 0))->withErrors(['error' => __('Payment processing failed')]);
        }
    }

    public function callback(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            $transactionStatus = $request->input('transaction_status');

            if ($orderId && in_array($transactionStatus, ['capture', 'settlement'])) {
                \Log::info('Midtrans invoice callback received', [
                    'order_id' => $orderId,
                    'status' => $transactionStatus
                ]);
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            return response()->json(['error' => __('Callback processing failed')], 500);
        }
    }

    private function createSnapToken($paymentData, $settings)
    {
        try {
            $baseUrl = $settings['midtrans_mode'] === 'live'
                ? 'https://app.midtrans.com'
                : 'https://app.sandbox.midtrans.com';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . '/snap/v1/transactions');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . base64_encode($settings['midtrans_secret_key'] . ':'),
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 201) {
                return false;
            }

            $result = json_decode($response, true);
            return $result['token'] ?? false;

        } catch (\Exception $e) {
            return false;
        }
    }

    private function getInvoicePaymentSettings($companyId)
    {
        return [
            'payment_settings' => PaymentSetting::getUserSettings($companyId),
            'general_settings' => \App\Models\Setting::getUserSettings($companyId),
        ];
    }
}
