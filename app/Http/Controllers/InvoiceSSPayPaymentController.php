<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;

class InvoiceSSPayPaymentController extends Controller
{
    private function getSSPayCredentials($companyId)
    {
        $settings = $this->getInvoicePaymentSettings($companyId);

        return [
            'secret_key' => $settings['payment_settings']['sspay_secret_key'] ?? null,
            'category_code' => $settings['payment_settings']['sspay_category_code'] ?? null,
        ];
    }

    public function createPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request);

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $credentials = $this->getSSPayCredentials($invoice->created_by);

            if (!$credentials['secret_key'] || !$credentials['category_code']) {
                return response()->json(['success' => false, 'message' => __('SSPay not configured.')], 400);
            }

            $validation = $invoice->validatePaymentAmount($validated['amount'], $validated['payment_type']);
            if (!$validation['valid']) {
                return response()->json(['success' => false, 'message' => $validation['message']], 400);
            }

            $orderId = 'inv_ssp_' . $invoice->id . '_' . time();

            $paymentData = [
                'userSecretKey' => $credentials['secret_key'],
                'categoryCode' => $credentials['category_code'],
                'billName' => "Invoice #{$invoice->invoice_number}",
                'billDescription' => "Invoice #{$invoice->invoice_number} - " . ucfirst($validated['payment_type']) . ' payment',
                'billPriceSetting' => 1,
                'billPayorInfo' => 1,
                'billAmount' => $validated['amount'] * 100,
                'billReturnUrl' => route('invoice.sspay.success') . '?invoice_id=' . $invoice->id . '&amount=' . $validated['amount'] . '&payment_type=' . $validated['payment_type'] . '&order_id=' . $orderId,
                'billCallbackUrl' => route('invoice.sspay.callback'),
                'billExternalReferenceNo' => $orderId,
                'billTo' => $invoice->billing_email ?? 'customer@example.com',
                'billEmail' => $invoice->billing_email ?? 'customer@example.com',
                'billPhone' => '60123456789',
                'billAddrLine1' => $invoice->billing_address ?? 'Address Line 1',
                'billAddrLine2' => '',
                'billPostcode' => $invoice->billing_postal_code ?? '12345',
                'billCity' => $invoice->billing_city ?? 'Kuala Lumpur',
                'billState' => $invoice->billing_state ?? 'Selangor',
                'billCountry' => 'MY',
            ];

            return response()->json([
                'success' => true,
                'payment_url' => 'https://sspay.my/index.php/api/createBill',
                'payment_data' => $paymentData,
                'order_id' => $orderId
            ]);

        } catch (\Exception $e) {
            \Log::error('SSPay invoice payment creation error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => __('Payment creation failed.')], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $invoiceId = $request->input('invoice_id');
            $amount = $request->input('amount');
            $paymentType = $request->input('payment_type');
            $orderId = $request->input('order_id');
            $statusId = $request->input('status_id');

            if (!$invoiceId || !$amount || !$paymentType || !$orderId) {
                return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Invalid payment parameters'));
            }

            $invoice = Invoice::findOrFail($invoiceId);

            if ($statusId === '1') {
                $existingPayment = InvoicePayment::where('payment_id', $orderId)->first();

                if (!$existingPayment) {
                    InvoicePayment::storePayment([
                        'invoice_id' => $invoiceId,
                        'amount' => $amount,
                        'payment_type' => $paymentType,
                        'payment_method' => 'sspay',
                        'payment_id' => $orderId,
                    ]);

                    \Log::info('SSPay invoice payment successful', [
                        'invoice_id' => $invoiceId,
                        'amount' => $amount,
                        'payment_id' => $orderId
                    ]);
                }

                return redirect()->route('invoices.public', ['invoice' => encrypt($invoiceId)])->with('success', __('Payment completed successfully!'));
            } else {
                return redirect()->route('invoices.public', ['invoice' => encrypt($invoiceId)])->with('error', __('Payment failed or cancelled.'));
            }

        } catch (\Exception $e) {
            \Log::error('SSPay invoice success callback error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Payment verification failed.'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $orderId = $request->input('billExternalReferenceNo');
            $statusId = $request->input('status_id');

            if ($orderId && $statusId === '1') {
                if (preg_match('/inv_ssp_(\d+)_/', $orderId, $matches)) {
                    $invoiceId = $matches[1];
                    $invoice = Invoice::find($invoiceId);

                    if ($invoice) {
                        $existingPayment = InvoicePayment::where('payment_id', $orderId)->first();

                        if (!$existingPayment) {
                            $amount = floatval($request->input('billAmount') ?? 0) / 100;
                            $paymentType = $amount >= $invoice->total_amount ? 'full' : 'partial';

                            InvoicePayment::storePayment([
                                'invoice_id' => $invoice->id,
                                'amount' => $amount,
                                'payment_type' => $paymentType,
                                'payment_method' => 'sspay',
                                'payment_id' => $orderId,
                            ]);

                            \Log::info('SSPay invoice payment successful via webhook', [
                                'invoice_id' => $invoice->id,
                                'payment_id' => $orderId,
                                'amount' => $amount
                            ]);
                        }
                    }
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            \Log::error('SSPay invoice callback error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
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
