<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;

class InvoiceOzowPaymentController extends Controller
{
    public function createPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request);

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
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (!isset($settings['payment_settings']['ozow_site_key']) || !isset($settings['payment_settings']['ozow_private_key']) || !isset($settings['payment_settings']['ozow_api_key'])) {
                \Log::error('Ozow payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return response()->json(['error' => __('Ozow not configured')], 400);
            }

            $siteCode = $settings['payment_settings']['ozow_site_key'];
            $privateKey = $settings['payment_settings']['ozow_private_key'];
            $apiKey = $settings['payment_settings']['ozow_api_key'];
            $isTest = $settings['payment_settings']['ozow_mode'] == 'sandbox' ? 'true' : 'false';
            $amount = $validated['amount'];
            $cancelUrl = route('invoices.public', encrypt($invoice->id));
            $successUrl = route('invoice.ozow.success', [
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
                'payment_type' => $validated['payment_type']
            ]);
            $notifyUrl = route('invoice.ozow.callback');
            $bankReference = 'INV' . $invoice->id . time();
            $transactionReference = 'inv_' . $invoice->id . '_' . time();
            $countryCode = 'ZA';
            $currency = 'ZAR';

            $inputString = $siteCode . $countryCode . $currency . $amount . $transactionReference . $bankReference . $cancelUrl . $successUrl . $successUrl . $notifyUrl . $isTest . $privateKey;
            $hashCheck = hash('sha512', strtolower($inputString));

            $data = [
                'countryCode' => $countryCode,
                'amount' => $amount,
                'transactionReference' => $transactionReference,
                'bankReference' => $bankReference,
                'cancelUrl' => $cancelUrl,
                'currencyCode' => $currency,
                'errorUrl' => $successUrl,
                'isTest' => $isTest,
                'notifyUrl' => $notifyUrl,
                'siteCode' => $siteCode,
                'successUrl' => $successUrl,
                'hashCheck' => $hashCheck,
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.ozow.com/postpaymentrequest',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'ApiKey: ' . $apiKey,
                    'Content-Type: application/json'
                ],
            ]);

            $response = curl_exec($curl);
            curl_close($curl);
            $json_attendance = json_decode($response);

            if (isset($json_attendance->url) && $json_attendance->url != null) {
                return response()->json([
                    'success' => true,
                    'payment_url' => $json_attendance->url,
                    'transaction_id' => $transactionReference
                ]);
            } else {
                \Log::error('Ozow payment creation failed', ['invoice_id' => $invoice->id, 'response' => $response]);
                return response()->json(['error' => __('Payment creation failed')], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Ozow payment error', [
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

            $invoice = Invoice::find($invoiceId);
            if (!$invoice) {
                return redirect()->route('invoices.public', encrypt($invoiceId))->with('error', __('Invoice not found'));
            }

            InvoicePayment::storePayment([
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'payment_method' => 'ozow',
                'payment_id' => $request->input('TransactionReference', 'OZOW-' . time()),
            ]);

            \Log::info('Ozow invoice payment successful', [
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_type' => $paymentType
            ]);

            return redirect()->route('invoices.public', encrypt($invoice->id))->with('success', __('Payment successful'));

        } catch (\Exception $e) {
            \Log::error('Ozow success callback error', [
                'error' => $e->getMessage()
            ]);
            return redirect()->route('invoices.public', encrypt($request->input('invoice_id')))->with('error', __('Payment processing failed'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $transactionId = $request->input('TransactionReference');
            $status = $request->input('Status');

            if ($transactionId && $status === 'Complete') {
                $parts = explode('_', $transactionId);

                if (count($parts) >= 2) {
                    $invoiceId = $parts[1];
                    $invoice = Invoice::find($invoiceId);

                    if ($invoice) {
                        $remainingAmount = $invoice->getRemainingAmount();

                        InvoicePayment::storePayment([
                            'invoice_id' => $invoice->id,
                            'amount' => $remainingAmount,
                            'payment_type' => 'full',
                            'payment_method' => 'ozow',
                            'payment_id' => $transactionId,
                        ]);

                        \Log::info('Ozow invoice payment callback successful', [
                            'invoice_id' => $invoice->id,
                            'transaction_id' => $transactionId
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            \Log::error('Ozow callback error', [
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
