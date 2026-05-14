<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;

class InvoiceSkrillPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request, [
            'email' => 'required|email',
        ]);

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
                return back()->withErrors(['error' => $validation['message']]);
            }

            $companyId = $invoice->created_by;
            $company = User::findOrFail($companyId);
            $settings = $this->getInvoicePaymentSettings($companyId);
            $currency = $settings['general_settings']['defaultCurrency'] ?? 'USD';

            if (!isset($settings['payment_settings']['skrill_merchant_id'])) {
                \Log::error('Skrill payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return back()->withErrors(['error' => __('Skrill not configured')]);
            }

            // Generate unique transaction ID
            $transactionId = 'INV_SKRILL_' . $invoice->id . '_' . time();

            // Prepare Skrill payment data
            $paymentData = [
                'pay_to_email' => $settings['payment_settings']['skrill_merchant_id'],
                'transaction_id' => $transactionId,
                'return_url' => route('invoices.public', encrypt($invoice->id)),
                'cancel_url' => route('invoices.public', encrypt($invoice->id)),
                'status_url' => route('invoice.skrill.callback'),
                'language' => 'EN',
                'amount' => $validated['amount'],
                'currency' => $currency,
                'detail1_description' => 'Invoice Payment',
                'detail1_text' => 'Invoice #' . $invoice->invoice_number . ' - ' . ucfirst($validated['payment_type']) . ' payment',
                'pay_from_email' => $validated['email'],
                'recipient_description' => $company->name ?? 'Invoice Payment',
                'logo_url' => config('app.url') . 'logo/logo-light.png',
            ];

            // Create form and auto-submit to Skrill
            $form = '<form id="skrill-form" method="POST" action="https://www.moneybookers.com/app/payment.pl">';
            foreach ($paymentData as $key => $value) {
                $form .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '">';
            }
            $form .= '</form>';
            $form .= '<script>document.getElementById("skrill-form").submit();</script>';
            $form .= '<div style="text-align: center; padding: 20px;">';
            $form .= '<p>Redirecting to Skrill payment gateway...</p>';
            $form .= '<p>If you are not redirected automatically, <a href="#" onclick="document.getElementById(\'skrill-form\').submit();">click here</a>.</p>';
            $form .= '</div>';

            \Log::info('Skrill payment initiated', [
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
                'payment_type' => $validated['payment_type'],
                'transaction_id' => $transactionId
            ]);

            return response($form);
        } catch (\Exception $e) {
            \Log::error('Skrill payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleInvoicePaymentError($e, 'skrill');
        }
    }

    public function callback(Request $request)
    {
        try {
            $transactionId = $request->input('transaction_id');
            $status = $request->input('status');
            $amount = $request->input('amount');
            $currency = $request->input('currency');
            $payFromEmail = $request->input('pay_from_email');

            \Log::info('Skrill callback received', [
                'transaction_id' => $transactionId,
                'status' => $status,
                'amount' => $amount,
                'currency' => $currency,
                'pay_from_email' => $payFromEmail
            ]);

            if (!$transactionId) {
                \Log::error('Skrill callback: Missing transaction ID');
                return response('Missing transaction ID', 400);
            }

            $payment = InvoicePayment::where('payment_id', $transactionId)
                ->where('payment_method', 'skrill')
                ->first();

            if (!$payment) {
                \Log::error('Skrill callback: Payment not found', ['transaction_id' => $transactionId]);
                return response('Payment not found', 404);
            }

            if ($status == '2') { // Payment processed successfully
                InvoicePayment::storePayment([
                    'invoice_id' => $payment->invoice_id,
                    'amount' => $payment->amount,
                    'payment_type' => $payment->payment_type,
                    'payment_method' => 'skrill',
                    'payment_id' => $payment->payment_id,
                ]);

                \Log::info('Skrill payment completed', [
                    'invoice_id' => $payment->invoice_id,
                    'payment_id' => $payment->payment_id,
                    'amount' => $payment->amount
                ]);
            } elseif ($status == '0') { // Payment pending
                \Log::info('Skrill payment pending', [
                    'invoice_id' => $payment->invoice_id,
                    'transaction_id' => $transactionId
                ]);
            } else { // Payment failed or cancelled
                $payment->update([
                    'status' => 'failed',
                    'notes' => $payment->notes . ' | Skrill payment failed (Status: ' . $status . ')'
                ]);

                \Log::warning('Skrill payment failed', [
                    'invoice_id' => $payment->invoice_id,
                    'transaction_id' => $transactionId,
                    'status' => $status
                ]);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            \Log::error('Skrill callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response('Internal Server Error', 500);
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

    private function handleInvoicePaymentError($e, $method = 'skrill')
    {
        return back()->withErrors(['error' => __('Payment processing failed: :message', ['message' => $e->getMessage()])]);
    }
}
