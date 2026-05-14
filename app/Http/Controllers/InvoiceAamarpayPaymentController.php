<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;

class InvoiceAamarpayPaymentController extends Controller
{
    public function createPayment(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_type' => 'required|in:full,partial',
        ]);

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
            $company = User::findOrFail($companyId);
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (!isset($settings['payment_settings']['aamarpay_store_id']) || !isset($settings['payment_settings']['aamarpay_signature'])) {
                return response()->json(['error' => __('Aamarpay not configured')], 400);
            }

            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            $currency = $settings['payment_settings']['currency'] ?? 'BDT';
            $url = 'https://sandbox.aamarpay.com/request.php';

            $storeId = $settings['payment_settings']['aamarpay_store_id'];

            $fields = [
                'store_id' => $storeId,
                'amount' => $validated['amount'],
                'payment_type' => '',
                'currency' => $currency,
                'tran_id' => $orderID,
                'cus_name' => $company->name ?? 'Customer',
                'cus_email' => $company->email,
                'cus_add1' => $invoice->billing_address ?? '',
                'cus_add2' => '',
                'cus_city' => $invoice->billing_city ?? '',
                'cus_state' => $invoice->billing_state ?? '',
                'cus_postcode' => $invoice->billing_postal_code ?? '',
                'cus_country' => $invoice->billing_country ?? '',
                'cus_phone' => '1234567890',
                'success_url' => route('invoice.aamarpay.success', [
                    'response' => 'success',
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'order_id' => $orderID,
                ]),
                'fail_url' => route('invoice.aamarpay.success', [
                    'response' => 'failure',
                    'invoice_id' => $invoice->id,
                ]),
                'cancel_url' => route('invoice.aamarpay.success', ['response' => 'cancel']),
                'signature_key' => $settings['payment_settings']['aamarpay_signature'],
                'desc' => 'Invoice #' . $invoice->invoice_number,
            ];

            $fields_string = http_build_query($fields);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $url_forward = str_replace('"', '', stripslashes($response));
            curl_close($ch);

            if ($url_forward) {
                return $this->redirectToMerchant($url_forward);
            }

            return response()->json(['error' => __('Payment creation failed')], 500);

        } catch (\Exception $e) {
            \Log::error('Aamarpay invoice payment creation error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    private function redirectToMerchant($url)
    {
        $token = csrf_token();
        $redirectUrl = 'https://sandbox.aamarpay.com/' . $url;

        return response(view('aamarpay-redirect', compact('redirectUrl', 'token')));
    }

    public function success(Request $request)
    {
        try {
            $response = $request->input('response');
            $invoiceId = $request->input('invoice_id');
            $amount = $request->input('amount');
            $paymentType = $request->input('payment_type');
            $orderId = $request->input('order_id');

            if ($response === 'success' && $invoiceId) {
                $invoice = Invoice::find($invoiceId);

                if ($invoice) {
                    InvoicePayment::storePayment([
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_type' => $paymentType,
                        'payment_method' => 'aamarpay',
                        'payment_id' => $orderId,
                    ]);

                    \Log::info('Aamarpay invoice payment successful', [
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_id' => $orderId
                    ]);

                    return redirect()->route('invoices.public', encrypt($invoice->id))->with('success', __('Payment successful'));
                }
            }

            return redirect()->route('invoices.public', encrypt($invoiceId ?? 0))->withErrors(['error' => __('Payment failed or cancelled')]);

        } catch (\Exception $e) {
            \Log::error('Aamarpay invoice payment success error', [
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('invoices.public', encrypt($request->input('invoice_id') ?? 0))->withErrors(['error' => __('Payment processing failed')]);
        }
    }

    public function callback(Request $request)
    {
        try {
            $transactionId = $request->input('mer_txnid');
            $status = $request->input('pay_status');

            if ($transactionId && $status === 'Successful') {
                \Log::info('Aamarpay invoice callback received', [
                    'transaction_id' => $transactionId,
                    'status' => $status
                ]);
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            return response()->json(['error' => __('Callback processing failed')], 500);
        }
    }

    private function getInvoicePaymentSettings($companyId)
    {
        return [
            'payment_settings' => PaymentSetting::getUserSettings($companyId),
            'general_settings' => \App\Models\Setting::getUserSettings($companyId),
        ];
    }
}
