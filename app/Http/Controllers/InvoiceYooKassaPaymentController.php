<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;
use YooKassa\Client;

class InvoiceYooKassaPaymentController extends Controller
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

            if (!isset($settings['payment_settings']['yookassa_shop_id']) || !isset($settings['payment_settings']['yookassa_secret_key'])) {
                \Log::error('YooKassa payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return response()->json(['error' => __('YooKassa not configured')], 400);
            }

            $client = new Client();
            $client->setAuth((int)$settings['payment_settings']['yookassa_shop_id'], $settings['payment_settings']['yookassa_secret_key']);

            $orderID = strtoupper(str_replace('.', '', uniqid('INV-', true)));

            $payment = $client->createPayment([
                'amount' => [
                    'value' => number_format($validated['amount'], 2, '.', ''),
                    'currency' => 'RUB',
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => route('invoice.yookassa.success', [
                        'invoice_id' => $invoice->id,
                        'order_id' => $orderID,
                        'amount' => $validated['amount'],
                        'payment_type' => $validated['payment_type']
                    ]),
                ],
                'capture' => true,
                'description' => 'Invoice Payment - ' . $invoice->invoice_number . ' - ' . ucfirst($validated['payment_type']) . ' payment',
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'order_id' => $orderID
                ]
            ], uniqid('', true));

            if ($payment['confirmation']['confirmation_url'] != null) {
                return response()->json([
                    'success' => true,
                    'payment_url' => $payment['confirmation']['confirmation_url'],
                    'payment_id' => $payment['id']
                ]);
            } else {
                \Log::error('YooKassa payment creation failed', ['invoice_id' => $invoice->id]);
                return response()->json(['error' => __('Payment creation failed')], 500);
            }

        } catch (\Exception $e) {
            \Log::error('YooKassa payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => __('Payment creation failed'),'message' => $e->getMessage()], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $invoiceId = $request->input('invoice_id');
            $orderId = $request->input('order_id');
            $amount = $request->input('amount');
            $paymentType = $request->input('payment_type');

            if ($invoiceId && $orderId) {
                $invoice = Invoice::find($invoiceId);

                if ($invoice) {
                    // Store payment record
                    InvoicePayment::storePayment([
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_type' => $paymentType,
                        'payment_method' => 'yookassa',
                        'payment_id' => $orderId,
                    ]);

                    \Log::info('YooKassa invoice payment successful', [
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_type' => $paymentType,
                        'payment_id' => $orderId
                    ]);

                    return redirect()->route('invoices.public', $invoice->id)->with('success', __('Payment successful'));
                }
            }
            return redirect()->route('invoices.public', $invoiceId)->with('error', __('Payment verification failed'));
        } catch (\Exception $e) {
            \Log::error('YooKassa success callback error', [
                'error' => $e->getMessage()
            ]);
            return redirect()->route('invoices.public', $request->input('invoice_id'))->with('error', __('Payment processing failed'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $paymentId = $request->input('object.id');
            $status = $request->input('object.status');
            $metadata = $request->input('object.metadata');

            if ($paymentId && $status === 'succeeded' && $metadata) {
                $invoiceId = $metadata['invoice_id'];
                $amount = $metadata['amount'];
                $paymentType = $metadata['payment_type'];

                $invoice = Invoice::find($invoiceId);

                if ($invoice) {
                    InvoicePayment::storePayment([
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_type' => $paymentType,
                        'payment_method' => 'yookassa',
                        'payment_id' => $paymentId,
                    ]);

                    \Log::info('YooKassa invoice payment callback successful', [
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_id' => $paymentId
                    ]);
                }
            }
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            \Log::error('YooKassa callback error', [
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
