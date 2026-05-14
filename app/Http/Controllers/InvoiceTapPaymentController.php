<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;

class InvoiceTapPaymentController extends Controller
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
                return response()->json(['success' => false, 'error' => $validation['message']]);
            }

            $companyId = $invoice->created_by;
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (!isset($settings['payment_settings']['tap_secret_key'])) {
                return response()->json(['success' => false, 'error' => __('Tap not configured')]);
            }

            $transactionId = 'inv_tap_' . $invoice->id . '_' . time() . '_' . uniqid();

            // Initialize Tap Payment library
            require_once app_path('Libraries/Tap/Tap.php');
            require_once app_path('Libraries/Tap/Reference.php');
            require_once app_path('Libraries/Tap/Payment.php');
            $tap = new \App\Package\Payment([
                'company_tap_secret_key' => $settings['payment_settings']['tap_secret_key']
            ]);

            $chargeData = [
                'amount' => $validated['amount'],
                'currency' => 'USD',
                'threeDSecure' => 'true',
                'description' => "Invoice #{$invoice->invoice_number} - {$validated['payment_type']} payment",
                'statement_descriptor' => 'Invoice Payment',
                'customer' => [
                    'first_name' => $invoice->account->name ?? $invoice->contact->name ?? 'Customer',
                    'email' => $invoice->account->email ?? $invoice->contact->email ?? 'customer@example.com',
                ],
                'source' => ['id' => 'src_card'],
                'post' => ['url' => route('invoice.tap.callback')],
                'redirect' => ['url' => route('invoice.tap.success', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'transaction_id' => $transactionId
                ])]
            ];

            return $tap->charge($chargeData, true);

        } catch (\Exception $e) {
            \Log::error('Tap invoice payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'error' => __('Payment creation failed')]);
        }
    }

    public function success(Request $request)
    {
        try {
            $chargeId = $request->input('tap_id');
            $invoiceId = $request->input('invoice_id');
            $amount = $request->input('amount');
            $paymentType = $request->input('payment_type');
            $transactionId = $request->input('transaction_id');

            if (!$chargeId || !$invoiceId || !$amount || !$paymentType) {
                return redirect()->back()->with('error', __('Invalid payment parameters'));
            }

            $invoice = Invoice::findOrFail($invoiceId);
            $companyId = $invoice->created_by;
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (!isset($settings['payment_settings']['tap_secret_key'])) {
                return redirect()->back()->with('error', __('Tap not configured'));
            }

            // Initialize Tap Payment library
            require_once app_path('Libraries/Tap/Tap.php');
            require_once app_path('Libraries/Tap/Reference.php');
            require_once app_path('Libraries/Tap/Payment.php');
            $tap = new \App\Package\Payment([
                'company_tap_secret_key' => $settings['payment_settings']['tap_secret_key']
            ]);

            // Get charge details from Tap API
            $chargeDetails = $tap->getCharge($chargeId);

            if ($chargeDetails && isset($chargeDetails->status) && $chargeDetails->status === 'CAPTURED') {
                InvoicePayment::storePayment([
                    'invoice_id' => $invoiceId,
                    'amount' => $amount,
                    'payment_type' => $paymentType,
                    'payment_method' => 'tap',
                    'payment_id' => $chargeId,
                ]);

                \Log::info('Tap invoice payment successful', [
                    'invoice_id' => $invoiceId,
                    'amount' => $amount,
                    'payment_type' => $paymentType,
                    'charge_id' => $chargeId
                ]);

                return redirect()->route('invoices.public', encrypt($invoiceId))
                    ->with('success', __('Payment completed successfully!'));
            } else {
                return redirect()->route('invoices.public', encrypt($invoiceId))->with('error', __('Payment failed'));
            }

        } catch (\Exception $e) {
            \Log::error('Tap invoice success error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            $invoiceId = $request->input('invoice_id');
            if ($invoiceId) {
                return redirect()->route('invoices.public', encrypt($invoiceId))->with('error', __('Payment verification failed'));
            }
            return redirect()->back()->with('error', __('Payment verification failed'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $chargeId = $request->input('tap_id');
            $status = $request->input('status');

            \Log::info('Tap invoice callback received', [
                'charge_id' => $chargeId,
                'status' => $status,
                'all_data' => $request->all()
            ]);

            return response('OK', 200);

        } catch (\Exception $e) {
            \Log::error('Tap invoice callback error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            return response('Error', 500);
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
