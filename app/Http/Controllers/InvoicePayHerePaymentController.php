<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;

class InvoicePayHerePaymentController extends Controller
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

            if (!isset($settings['payment_settings']['payhere_merchant_id'])) {
                \Log::error('PayHere payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return response()->json(['error' => __('PayHere not configured')], 400);
            }

            $orderId = 'inv_' . $invoice->id . '_' . time();

            $paymentData = [
                'merchant_id' => $settings['payment_settings']['payhere_merchant_id'],
                'return_url' => route('invoice.payhere.success', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type']
                ]),
                'cancel_url' => route('invoices.public', $invoice->id),
                'notify_url' => route('invoice.payhere.callback'),
                'order_id' => $orderId,
                'items' => 'Invoice Payment - ' . $invoice->invoice_number,
                'currency' => 'LKR',
                'amount' => number_format($validated['amount'], 2, '.', ''),
                'first_name' => $invoice->name ?? 'Customer',
                'last_name' => 'User',
                'email' => $invoice->email ?? $company->email,
                'phone' => $invoice->phone ?? '0771234567',
                'address' => $invoice->billing_address ?? 'No.1, Galle Road',
                'city' => $invoice->billing_city ?? 'Colombo',
                'country' => 'Sri Lanka',
            ];

            // Generate hash
            $hashString = strtoupper(
                md5(
                    $paymentData['merchant_id'] .
                    $paymentData['order_id'] .
                    number_format($paymentData['amount'], 2, '.', '') .
                    $paymentData['currency'] .
                    strtoupper(md5($settings['payment_settings']['payhere_merchant_secret']))
                )
            );
            $paymentData['hash'] = $hashString;

            $baseUrl = $settings['payment_settings']['payhere_mode'] === 'live'
                ? 'https://www.payhere.lk'
                : 'https://sandbox.payhere.lk';

            return response()->json([
                'success' => true,
                'payment_url' => $baseUrl . '/pay/checkout',
                'payment_data' => $paymentData,
                'order_id' => $orderId
            ]);

        } catch (\Exception $e) {
            \Log::error('PayHere payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

            if ($invoiceId) {
                $invoice = Invoice::find($invoiceId);

                if ($invoice) {
                    InvoicePayment::storePayment([
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_type' => $paymentType,
                        'payment_method' => 'payhere',
                        'payment_id' => $orderId ?? 'PAYHERE-' . time(),
                    ]);

                    \Log::info('PayHere invoice payment successful', [
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_type' => $paymentType
                    ]);

                    return redirect()->route('invoices.public', $invoice->id)->with('success', __('Payment successful'));
                }
            }
            return redirect()->route('invoices.public', $invoiceId)->with('error', __('Payment verification failed'));
        } catch (\Exception $e) {
            \Log::error('PayHere success callback error', [
                'error' => $e->getMessage()
            ]);
            return redirect()->route('invoices.public', $request->input('invoice_id'))->with('error', __('Payment processing failed'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            $statusCode = $request->input('status_code');

            if ($orderId && $statusCode === '2') {
                $parts = explode('_', $orderId);
                
                if (count($parts) >= 2) {
                    $invoiceId = $parts[1];
                    $invoice = Invoice::find($invoiceId);

                    if ($invoice) {
                        $remainingAmount = $invoice->getRemainingAmount();
                        
                        InvoicePayment::storePayment([
                            'invoice_id' => $invoice->id,
                            'amount' => $remainingAmount,
                            'payment_type' => 'full',
                            'payment_method' => 'payhere',
                            'payment_id' => $request->input('payment_id') ?? $orderId,
                        ]);

                        \Log::info('PayHere invoice payment callback successful', [
                            'invoice_id' => $invoice->id,
                            'order_id' => $orderId
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            \Log::error('PayHere callback error', [
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
