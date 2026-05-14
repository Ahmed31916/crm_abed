<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;

class InvoicePaiementPaymentController extends Controller
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

            if (!isset($settings['payment_settings']['paiement_merchant_id'])) {
                \Log::error('Paiement Pro payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return response()->json(['error' => __('Paiement Pro not configured')], 400);
            }

            $transactionId = 'INV-' . time() . '-' . $invoice->id;

            $data = [
                'merchantId' => $settings['payment_settings']['paiement_merchant_id'],
                'amount' => (int)($validated['amount'] * 549),
                'description' => 'Invoice Payment - ' . $invoice->invoice_number . ' - ' . ucfirst($validated['payment_type']) . ' payment',
                'channel' => 'CARD',
                'countryCurrencyCode' => '952',
                'referenceNumber' => $transactionId,
                'customerEmail' => $invoice->email ?? $company->email,
                'customerFirstName' => $invoice->name ?? 'Customer',
                'customerLastname' => $invoice->name ?? 'User',
                'customerPhoneNumber' => $invoice->phone ?? '01234567',
                'notificationURL' => route('invoice.paiement.callback'),
                'returnURL' => route('invoice.paiement.success', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type']
                ]),
                'returnContext' => json_encode([
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type']
                ])
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.paiementpro.net/webservice/onlinepayment/init/curl-init.php');
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $responseData = json_decode($response, true);
                return response()->json([
                    'success' => true,
                    'payment_response' => $responseData,
                    'transaction_id' => $transactionId
                ]);
            }

            \Log::error('Paiement Pro payment creation failed', ['invoice_id' => $invoice->id, 'http_code' => $httpCode]);
            return response()->json(['error' => __('Payment initialization failed')], 500);

        } catch (\Exception $e) {
            \Log::error('Paiement Pro payment error', [
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
            $transactionId = $request->input('reference');

            if ($invoiceId) {
                $invoice = Invoice::find($invoiceId);

                if ($invoice) {
                    InvoicePayment::storePayment([
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_type' => $paymentType,
                        'payment_method' => 'paiement',
                        'payment_id' => $transactionId ?? 'PAIEMENT-' . time(),
                    ]);

                    \Log::info('Paiement Pro invoice payment successful', [
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_type' => $paymentType
                    ]);

                    return redirect()->route('invoices.public', $invoice->id)->with('success', __('Payment successful'));
                }
            }
            return redirect()->route('invoices.public', $invoiceId)->with('error', __('Payment verification failed'));
        } catch (\Exception $e) {
            \Log::error('Paiement Pro success callback error', [
                'error' => $e->getMessage()
            ]);
            return redirect()->route('invoices.public', $request->input('invoice_id'))->with('error', __('Payment processing failed'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $transactionId = $request->input('reference');
            $status = $request->input('status');

            if ($transactionId && $status === 'success') {
                $parts = explode('-', $transactionId);
                
                if (count($parts) >= 3) {
                    $invoiceId = end($parts);
                    $invoice = Invoice::find($invoiceId);

                    if ($invoice) {
                        $remainingAmount = $invoice->getRemainingAmount();
                        
                        InvoicePayment::storePayment([
                            'invoice_id' => $invoice->id,
                            'amount' => $remainingAmount,
                            'payment_type' => 'full',
                            'payment_method' => 'paiement',
                            'payment_id' => $request->input('transaction_id') ?? $transactionId,
                        ]);

                        \Log::info('Paiement Pro invoice payment callback successful', [
                            'invoice_id' => $invoice->id,
                            'transaction_id' => $transactionId
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            \Log::error('Paiement Pro callback error', [
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
