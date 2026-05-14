<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;

class InvoicePayfastPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request, [
            'customer_details' => 'required|array',
            'customer_details.firstName' => 'required|string',
            'customer_details.lastName' => 'required|string',
            'customer_details.email' => 'required|email',
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
                return response()->json(['success' => false, 'error' => $validation['message']]);
            }

            $companyId = $invoice->created_by;
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (!isset($settings['payment_settings']['payfast_merchant_id']) || !isset($settings['payment_settings']['payfast_merchant_key'])) {
                return response()->json(['success' => false, 'error' => __('PayFast not configured')]);
            }

            if ($validated['amount'] < 5.00) {
                return response()->json(['success' => false, 'error' => __('Minimum amount is R5.00')]);
            }

            $paymentId = 'inv_pf_' . $invoice->id . '_' . time() . '_' . uniqid();

            $isLive = ($settings['payment_settings']['payfast_mode'] ?? 'sandbox') === 'live';

            $data = [
                'merchant_id' => $settings['payment_settings']['payfast_merchant_id'],
                'merchant_key' => $settings['payment_settings']['payfast_merchant_key'],
                'return_url' => route('invoice.payfast.success') . '?invoice_id=' . $invoice->id . '&amount=' . $validated['amount'] . '&payment_type=' . $validated['payment_type'].'&m_payment_id='.$paymentId,
                'cancel_url' => route('invoices.public', encrypt($invoice->id)),
                'notify_url' => route('invoice.payfast.callback'),
                'name_first' => $validated['customer_details']['firstName'],
                'name_last' => $validated['customer_details']['lastName'],
                'email_address' => $validated['customer_details']['email'],
                'm_payment_id' => $paymentId,
                'amount' => number_format($validated['amount'], 2, '.', ''),
                'item_name' => "Invoice #{$invoice->invoice_number}",
            ];

            $passphrase = $settings['payment_settings']['payfast_passphrase'] ?? '';
            $signature = $this->generateSignature($data, $passphrase);
            $data['signature'] = $signature;

            $htmlForm = '';
            foreach ($data as $name => $value) {
                $htmlForm .= '<input name="' . $name . '" type="hidden" value="' . $value . '" />';
            }

            $endpoint = $isLive
                ? 'https://www.payfast.co.za/eng/process'
                : 'https://sandbox.payfast.co.za/eng/process';

            return response()->json([
                'success' => true,
                'inputs' => $htmlForm,
                'action' => $endpoint
            ]);

        } catch (\Exception $e) {
            \Log::error('PayFast invoice payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'error' => __('Payment failed')]);
        }
    }

    public function callback(Request $request)
    {
        try {
            $pfData = $request->all();
            $paymentId = $pfData['m_payment_id'] ?? null;
            $paymentStatus = $pfData['payment_status'] ?? null;

            if (!$paymentId) {
                return response(__('Missing payment ID'), 400);
            }

            if ($paymentStatus === 'COMPLETE') {
                // Extract invoice ID and amount from payment ID
                if (preg_match('/inv_pf_(\d+)_/', $paymentId, $matches)) {
                    $invoiceId = $matches[1];
                    $invoice = Invoice::find($invoiceId);

                    if ($invoice) {
                        $companyId = $invoice->created_by;
                        $settings = $this->getInvoicePaymentSettings($companyId);

                        // Verify signature
                        if (!$this->verifyPayfastSignature($pfData, $settings['payment_settings']['payfast_passphrase'] ?? '')) {
                            return response(__('Invalid signature'), 400);
                        }

                        $amount = floatval($pfData['amount_gross'] ?? 0);
                        $paymentType = $amount >= $invoice->total_amount ? 'full' : 'partial';

                        // Store payment using static method
                        InvoicePayment::storePayment([
                            'invoice_id' => $invoice->id,
                            'amount' => $amount,
                            'payment_type' => $paymentType,
                            'payment_method' => 'payfast',
                            'payment_id' => $paymentId,
                        ]);

                        \Log::info('PayFast invoice payment successful', [
                            'invoice_id' => $invoice->id,
                            'payment_id' => $paymentId,
                            'amount' => $amount
                        ]);
                    }
                }
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            \Log::error('PayFast invoice callback error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            return response('ERROR', 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $invoiceId = $request->get('invoice_id');
            $amount = $request->get('amount');
            $paymentType = $request->get('payment_type');
            $paymentId = $request->get('m_payment_id');

            if (!$invoiceId || !$amount || !$paymentType) {
                return redirect()->back()->with('error', __('Invalid payment parameters'));
            }

            $invoice = Invoice::findOrFail($invoiceId);

            InvoicePayment::storePayment([
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'payment_method' => 'payfast',
                'payment_id' => $paymentId,
            ]);

            return redirect()->route('invoices.public', encrypt($invoiceId))
                ->with('success', __('Payment completed successfully!'));

        } catch (\Exception $e) {
            \Log::error('PayFast success error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', __('Payment verification failed'));
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

    private function generateSignature($data, $passPhrase = null)
    {
        $pfOutput = '';
        foreach ($data as $key => $val) {
            if ($val !== '') {
                $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }

        $getString = substr($pfOutput, 0, -1);
        if ($passPhrase !== null) {
            $getString .= '&passphrase=' . urlencode(trim($passPhrase));
        }
        return md5($getString);
    }

    private function verifyPayfastSignature($pfData, $passphrase = '')
    {
        $signature = $pfData['signature'] ?? '';
        unset($pfData['signature']);

        $expectedSignature = $this->generateSignature($pfData, $passphrase);

        return hash_equals($expectedSignature, $signature);
    }


}
