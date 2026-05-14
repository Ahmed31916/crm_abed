<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;

class InvoiceToyyibPayPaymentController extends Controller
{
    private function getToyyibPayCredentials($companyId)
    {
        $settings = $this->getInvoicePaymentSettings($companyId);

        return [
            'secret_key' => $settings['payment_settings']['toyyibpay_secret_key'] ?? null,
            'category_code' => $settings['payment_settings']['toyyibpay_category_code'] ?? null,
        ];
    }

    public function processPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request, [
            'billName' => 'required|string',
            'billTo' => 'required|string',
            'billEmail' => 'required|email',
            'billPhone' => 'required|string',
        ]);

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $credentials = $this->getToyyibPayCredentials($invoice->created_by);

            if (!$credentials['secret_key'] || !$credentials['category_code']) {
                return response()->json(['success' => false, 'message' => __('ToyyibPay not configured.')], 400);
            }

            $validation = $invoice->validatePaymentAmount($validated['amount'], $validated['payment_type']);
            if (!$validation['valid']) {
                return response()->json(['success' => false, 'message' => $validation['message']], 400);
            }

            $paymentId = 'inv_typ_' . $invoice->id . '_' . time() . '_' . uniqid();

            // Format phone number for Malaysian format
            $phone = preg_replace('/[^0-9]/', '', $validated['billPhone']);
            if (!str_starts_with($phone, '60')) {
                $phone = '60' . ltrim($phone, '0');
            }

            $billData = [
                'userSecretKey' => $credentials['secret_key'],
                'categoryCode' => $credentials['category_code'],
                'billName' => $validated['billName'],
                'billDescription' => "Invoice #{$invoice->invoice_number} - " . ucfirst($validated['payment_type']) . ' payment',
                'billPriceSetting' => 1,
                'billPayorInfo' => 1,
                'billAmount' => intval($validated['amount'] * 100),
                'billReturnUrl' => route('invoice.toyyibpay.success') . '?invoice_id=' . $invoice->id . '&amount=' . $validated['amount'] . '&payment_type=' . $validated['payment_type'] . '&payment_id=' . $paymentId,
                'billCallbackUrl' => route('invoice.toyyibpay.callback'),
                'billExternalReferenceNo' => $paymentId,
                'billTo' => $validated['billTo'],
                'billEmail' => $validated['billEmail'],
                'billPhone' => $phone,
                'billSplitPayment' => 0,
                'billSplitPaymentArgs' => '',
                'billPaymentChannel' => '0',
                'billContentEmail' => 'Thank you for your payment!',
                'billChargeToCustomer' => 1,
                'billExpiryDate' => date('d-m-Y', strtotime('+3 days')),
                'billExpiryDays' => 3
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_URL, 'https://toyyibpay.com/index.php/api/createBill');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $billData);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);

            $result = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);

            if ($curlError) {
                throw new \Exception('cURL Error: ' . $curlError);
            }

            if ($httpCode !== 200) {
                throw new \Exception('HTTP Error: ' . $httpCode);
            }

            if (str_contains($result, 'KEY-DID-NOT-EXIST-OR-USER-IS-NOT-ACTIVE')) {
                throw new \Exception(__('Invalid ToyyibPay credentials or inactive account'));
            }

            $responseData = json_decode($result, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(__('Invalid JSON response from ToyyibPay'));
            }

            if (isset($responseData[0]['BillCode'])) {
                $redirectUrl = 'https://toyyibpay.com/' . $responseData[0]['BillCode'];
                
                \Log::info('ToyyibPay invoice payment created', [
                    'invoice_id' => $invoice->id,
                    'payment_id' => $paymentId,
                    'bill_code' => $responseData[0]['BillCode']
                ]);

                return response()->json([
                    'success' => true,
                    'redirect_url' => $redirectUrl
                ]);
            } else {
                $errorMsg = $responseData[0]['msg'] ?? __('Failed to create payment bill');
                throw new \Exception($errorMsg);
            }

        } catch (\Exception $e) {
            \Log::error('ToyyibPay invoice payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => __('Payment processing failed.')], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $invoiceId = $request->input('invoice_id');
            $amount = $request->input('amount');
            $paymentType = $request->input('payment_type');
            $paymentId = $request->input('payment_id');
            $statusId = $request->input('status_id');

            if (!$invoiceId || !$amount || !$paymentType || !$paymentId) {
                return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Invalid payment parameters'));
            }

            $invoice = Invoice::findOrFail($invoiceId);

            if ($statusId == '1') {
                $existingPayment = InvoicePayment::where('payment_id', $paymentId)->first();

                if (!$existingPayment) {
                    InvoicePayment::storePayment([
                        'invoice_id' => $invoiceId,
                        'amount' => $amount,
                        'payment_type' => $paymentType,
                        'payment_method' => 'toyyibpay',
                        'payment_id' => $paymentId,
                    ]);

                    \Log::info('ToyyibPay invoice payment successful', [
                        'invoice_id' => $invoiceId,
                        'amount' => $amount,
                        'payment_type' => $paymentType,
                        'payment_id' => $paymentId
                    ]);
                }

                return redirect()->route('invoices.public', ['invoice' => encrypt($invoiceId)])->with('success', __('Payment completed successfully!'));
            } else {
                return redirect()->route('invoices.public', ['invoice' => encrypt($invoiceId)])->with('error', __('Payment was not completed. Please try again.'));
            }

        } catch (\Exception $e) {
            \Log::error('ToyyibPay invoice success callback error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Payment verification failed.'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $billcode = $request->input('billcode');
            $statusId = $request->input('status_id');
            $orderId = $request->input('order_id');
            $transactionId = $request->input('transaction_id');

            if ($statusId == '1' && $orderId) {
                if (preg_match('/inv_typ_(\d+)_/', $orderId, $matches)) {
                    $invoiceId = $matches[1];
                    $invoice = Invoice::find($invoiceId);

                    if ($invoice) {
                        $existingPayment = InvoicePayment::where('payment_id', $orderId)->first();

                        if (!$existingPayment) {
                            // Get amount from bill details if needed
                            $amount = $request->input('amount') ?? 0;
                            $paymentType = 'full'; // Default, will be corrected if needed

                            InvoicePayment::storePayment([
                                'invoice_id' => $invoice->id,
                                'amount' => $amount,
                                'payment_type' => $paymentType,
                                'payment_method' => 'toyyibpay',
                                'payment_id' => $orderId,
                            ]);

                            \Log::info('ToyyibPay invoice payment successful via webhook', [
                                'invoice_id' => $invoice->id,
                                'payment_id' => $orderId,
                                'transaction_id' => $transactionId
                            ]);
                        }
                    }
                }
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            \Log::error('ToyyibPay invoice callback error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response('ERROR', 500);
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
