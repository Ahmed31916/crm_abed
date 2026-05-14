<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;

class InvoiceEasebuzzPaymentController extends Controller
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

            if (!isset($settings['payment_settings']['easebuzz_merchant_key']) || !isset($settings['payment_settings']['easebuzz_salt_key'])) {
                \Log::error('Easebuzz payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return response()->json(['error' => __('Easebuzz not configured')], 400);
            }

            // Include Easebuzz library
            require_once app_path('Libraries/Easebuzz/easebuzz_payment_gateway.php');

            $txnid = 'inv_' . $invoice->id . '_' . time();
            $environment = $settings['payment_settings']['easebuzz_environment'] === 'prod' ? 'prod' : 'test';

            $easebuzz = new \Easebuzz(
                $settings['payment_settings']['easebuzz_merchant_key'],
                $settings['payment_settings']['easebuzz_salt_key'],
                $environment
            );

            $postData = [
                'txnid' => $txnid,
                'amount' => number_format($validated['amount'], 2, '.', ''),
                'productinfo' => 'Invoice Payment - ' . $invoice->invoice_number,
                'firstname' => $invoice->name ?? 'Customer',
                'email' => $invoice->email ?? $company->email,
                'phone' => $invoice->phone ?? '9999999999',
                'surl' => route('invoice.easebuzz.success', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type']
                ]),
                'furl' => route('invoice.easebuzz.failure', [
                    'invoice_id' => $invoice->id
                ]),
                'udf1' => $validated['payment_type'],
                'udf2' => $validated['amount'],
            ];

            $result = $easebuzz->initiatePaymentAPI($postData, false);
            $resultArray = json_decode($result, true);

            if ($resultArray && isset($resultArray['status']) && $resultArray['status'] == 1) {
                $accessKey = $resultArray['access_key'] ?? null;
                if ($accessKey) {
                    $baseUrl = $settings['payment_settings']['easebuzz_environment'] === 'prod'
                        ? 'https://pay.easebuzz.in'
                        : 'https://testpay.easebuzz.in';

                    return response()->json([
                        'success' => true,
                        'payment_url' => $baseUrl . '/pay/' . $accessKey,
                        'transaction_id' => $txnid
                    ]);
                }
            }

            \Log::error('Easebuzz payment creation failed', ['invoice_id' => $invoice->id, 'result' => $resultArray]);
            return response()->json(['error' => 'Payment initialization failed'], 400);

        } catch (\Exception $e) {
            \Log::error('Easebuzz payment error', [
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
            require_once app_path('Libraries/Easebuzz/easebuzz_payment_gateway.php');

            $invoiceId = $request->input('invoice_id');
            $amount = $request->input('amount');
            $paymentType = $request->input('payment_type');

            $invoice = Invoice::find($invoiceId);
            if (!$invoice) {
                return redirect()->route('invoices.public', encrypt($invoiceId))->with('error', __('Invoice not found'));
            }

            $companyId = $invoice->created_by;
            $settings = $this->getInvoicePaymentSettings($companyId);
            $environment = $settings['payment_settings']['easebuzz_environment'] === 'prod' ? 'prod' : 'test';

            $easebuzz = new \Easebuzz(
                $settings['payment_settings']['easebuzz_merchant_key'],
                $settings['payment_settings']['easebuzz_salt_key'],
                $environment
            );

            $result = $easebuzz->easebuzzResponse($request->all());
            $resultArray = json_decode($result, true);

            if ($resultArray && $resultArray['status'] == 1 && $request->input('status') === 'success') {
                InvoicePayment::storePayment([
                    'invoice_id' => $invoice->id,
                    'amount' => $amount,
                    'payment_type' => $paymentType,
                    'payment_method' => 'easebuzz',
                    'payment_id' => $request->input('easepayid'),
                ]);

                \Log::info('Easebuzz invoice payment successful', [
                    'invoice_id' => $invoice->id,
                    'amount' => $amount,
                    'payment_type' => $paymentType
                ]);

                return redirect()->route('invoices.public', encrypt($invoice->id))->with('success', __('Payment successful'));
            }

            return redirect()->route('invoices.public', encrypt($invoiceId))->with('error', __('Payment verification failed'));

        } catch (\Exception $e) {
            \Log::error('Easebuzz success callback error', [
                'error' => $e->getMessage()
            ]);
            return redirect()->route('invoices.public', encrypt($request->input('invoice_id')))->with('error', __('Payment processing failed'));
        }
    }

    public function failure(Request $request)
    {
        try {
            $invoiceId = $request->input('invoice_id');
            $invoice = Invoice::find($invoiceId);

            if ($invoice) {
                \Log::info('Easebuzz invoice payment failed', [
                    'invoice_id' => $invoice->id,
                    'txnid' => $request->input('txnid'),
                    'status' => $request->input('status')
                ]);

                return redirect()->route('invoices.public', encrypt($invoice->id))->with('error', __('Payment failed or cancelled'));
            }

            return redirect()->route('invoices.public', encrypt($invoiceId))->with('error', __('Payment failed'));

        } catch (\Exception $e) {
            \Log::error('Easebuzz failure callback error', [
                'error' => $e->getMessage()
            ]);
            return redirect()->route('invoices.public', encrypt($request->input('invoice_id')))->with('error', __('Payment failed'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $txnid = $request->input('txnid');
            $status = $request->input('status');

            if ($txnid && $status === 'success') {
                $parts = explode('_', $txnid);

                if (count($parts) >= 2) {
                    $invoiceId = $parts[1];
                    $invoice = Invoice::find($invoiceId);

                    if ($invoice) {
                        $paymentType = $request->input('udf1', 'full');
                        $amount = $request->input('udf2', $invoice->getRemainingAmount());

                        InvoicePayment::storePayment([
                            'invoice_id' => $invoice->id,
                            'amount' => $amount,
                            'payment_type' => $paymentType,
                            'payment_method' => 'easebuzz',
                            'payment_id' => $request->input('easepayid'),
                        ]);

                        \Log::info('Easebuzz invoice payment callback successful', [
                            'invoice_id' => $invoice->id,
                            'txnid' => $txnid
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            \Log::error('Easebuzz callback error', [
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
