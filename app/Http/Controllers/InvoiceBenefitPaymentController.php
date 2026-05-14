<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;

class InvoiceBenefitPaymentController extends Controller
{
    public function processPayment(Request $request)
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
                return response()->json(['success' => false, 'message' => $validation['message']], 400);
            }

            $companyId = $invoice->created_by;
            $company = User::findOrFail($companyId);
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (!isset($settings['payment_settings']['benefit_secret_key']) || !isset($settings['payment_settings']['benefit_public_key'])) {
                return response()->json(['success' => false, 'message' => __('Benefit payment not configured')], 400);
            }

            $orderID = strtoupper(str_replace('.', '', uniqid('INV_BEN_', true)));

            $userData = [
                "amount" => $validated['amount'],
                "currency" => "BHD",
                "customer_initiated" => true,
                "threeDSecure" => true,
                "save_card" => false,
                "description" => "Invoice #{$invoice->invoice_number} - " . ucfirst($validated['payment_type']) . " payment",
                "metadata" => ["udf1" => "Invoice Payment"],
                "reference" => ["transaction" => $orderID, "order" => $orderID],
                "receipt" => ["email" => true, "sms" => true],
                "customer" => [
                    "first_name" => $invoice->customer_name ?? 'Customer',
                    "middle_name" => "",
                    "last_name" => "",
                    "email" => $invoice->customer_email ?? 'customer@example.com',
                    "phone" => ["country_code" => "973", "number" => "33123456"]
                ],
                "source" => ["id" => "src_bh.benefit"],
                "post" => ["url" => route('invoice.benefit.callback')],
                "redirect" => ["url" => route('invoice.benefit.success', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'order_id' => $orderID
                ])]
            ];

            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . $settings['payment_settings']['benefit_secret_key'],
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post('https://api.tap.company/v2/charges', $userData);

            if ($response->successful()) {
                $res = $response->json();
                if (isset($res['transaction']['url'])) {
                    return response()->json([
                        'success' => true,
                        'redirect_url' => $res['transaction']['url'],
                        'transaction_id' => $orderID
                    ]);
                }
            }

            \Log::error('Benefit payment initialization failed', [
                'invoice_id' => $invoice->id,
                'response' => $response->body()
            ]);

            return response()->json(['success' => false, 'message' => __('Payment initialization failed')], 500);

        } catch (\Exception $e) {
            \Log::error('Benefit invoice payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => __('Payment processing failed')], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $invoiceId = $request->input('invoice_id');
            $amount = $request->input('amount');
            $paymentType = $request->input('payment_type');
            $orderId = $request->input('order_id');
            $tapId = $request->input('tap_id');

            if (!$invoiceId || !$amount || !$paymentType) {
                return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Invalid payment parameters'));
            }

            $invoice = Invoice::findOrFail($invoiceId);

            InvoicePayment::storePayment([
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'payment_method' => 'benefit',
                'payment_id' => $tapId ?? $orderId,
            ]);

            \Log::info('Benefit invoice payment successful', [
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'payment_id' => $tapId ?? $orderId
            ]);

            return redirect()->route('invoices.public', ['invoice' => encrypt($invoiceId)])->with('success', __('Payment completed successfully!'));

        } catch (\Exception $e) {
            \Log::error('Benefit success callback error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Payment verification failed'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $payload = $request->all();

            \Log::info('Benefit invoice callback received', [
                'payload' => $payload
            ]);

            return response('OK', 200);

        } catch (\Exception $e) {
            \Log::error('Benefit invoice callback error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response(__('Callback processing failed'), 500);
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
