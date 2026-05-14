<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;
use FedaPay\FedaPay;
use FedaPay\Transaction;

class InvoiceFedaPayPaymentController extends Controller
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

            if (!isset($settings['payment_settings']['fedapay_secret_key'])) {
                \Log::error('FedaPay payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return response()->json(['error' => __('FedaPay not configured')], 400);
            }

            $this->configureFedaPay($settings['payment_settings']);

            $transaction = Transaction::create([
                'description' => 'Invoice Payment - ' . $invoice->invoice_number . ' - ' . ucfirst($validated['payment_type']) . ' payment',
                'amount' => (int) ($validated['amount']),
                'currency' => ['iso' => 'XOF'],
                'callback_url' => route('invoice.fedapay.callback') . '?company_id=' . $companyId . '&invoice_id=' . $invoice->id,
                'customer' => [
                    'firstname' => $invoice->name ?? 'Customer',
                    'email' => $invoice->email ?? $company->email,
                ],
                'custom_metadata' => [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                ]
            ]);

            $token = $transaction->generateToken();

            return response()->json([
                'success' => true,
                'payment_url' => $token->url,
                'transaction_id' => $transaction->id,
                'token' => $token->token
            ]);

        } catch (\Exception $e) {
            \Log::error('FedaPay payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    public function callback(Request $request)
    {
        try {
            $companyId = $request->input('company_id');
            $settings = $this->getInvoicePaymentSettings($companyId);
            $this->configureFedaPay($settings['payment_settings']);

            $transactionId = $request->input('id');
            $transaction = Transaction::retrieve($transactionId);

            if ($transaction->status === 'approved') {
                $metadata = $transaction->custom_metadata;
                $invoice = Invoice::find($metadata['invoice_id']);

                if ($invoice) {
                    InvoicePayment::storePayment([
                        'invoice_id' => $invoice->id,
                        'amount' => $metadata['amount'],
                        'payment_type' => $metadata['payment_type'],
                        'payment_method' => 'fedapay',
                        'payment_id' => $transactionId,
                    ]);

                    \Log::info('FedaPay invoice payment callback successful', [
                        'invoice_id' => $invoice->id,
                        'transaction_id' => $transactionId
                    ]);

                    return redirect()->route('invoices.public', encrypt($invoice->id))->with('success', __('Payment successful'));
                }
            }

            return redirect()->route('invoices.public', encrypt($metadata['invoice_id'] ?? null))->with('error', __('Payment was not completed'));
        } catch (\Exception $e) {
            \Log::error('FedaPay callback error', [
                'error' => $e->getMessage()
            ]);
            // return response()->json(['error' => __('Callback processing failed')], 500);
            return redirect()->route('invoices.public',encrypt($request->input('invoice_id')))->with('error', __('Callback processing failed'));
        }
    }

    private function configureFedaPay($settings)
    {
        FedaPay::setApiKey($settings['fedapay_secret_key']);
        FedaPay::setEnvironment($settings['fedapay_mode'] === 'live' ? 'live' : 'sandbox');
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
