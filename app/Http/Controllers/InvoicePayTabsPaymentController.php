<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Paytabscom\Laravel_paytabs\Facades\paypage;

class InvoicePayTabsPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request, [
            'payment_id' => 'required|string',
            'transaction_id' => 'required|string',
        ]);

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);

            // Validate payment amount and type
            $validation = $invoice->validatePaymentAmount($validated['amount'], $validated['payment_type']);
            if (!$validation['valid']) {
                return response()->json(['success' => false, 'message' => $validation['message']], 400);
            }

            $companyId = $invoice->created_by;
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (empty($settings['payment_settings']['paytabs_profile_id']) || empty($settings['payment_settings']['paytabs_server_key'])) {
                return response()->json(['success' => false, 'message' => __('PayTabs configuration incomplete.')], 400);
            }

            $cartId = 'INV_PT_' . $invoice->id . '_' . time();

            // Force PayTabs configuration
            config([
                'paytabs.profile_id' => $settings['payment_settings']['paytabs_profile_id'],
                'paytabs.server_key' => $settings['payment_settings']['paytabs_server_key'],
                'paytabs.region' => $settings['payment_settings']['paytabs_region'] ?? 'ARE',
                'paytabs.currency' => 'INR'
            ]);

            $pay = paypage::sendPaymentCode('all')
                ->sendTransaction('sale', 'ecom')
                ->sendCart($cartId, $validated['amount'], "Invoice #{$invoice->invoice_number} - {$validated['payment_type']} payment")
                ->sendCustomerDetails(
                    'Customer',
                    'customer@example.com',
                    '1234567890',
                    'Address',
                    'City',
                    'State',
                    'SA',
                    '12345',
                    request()->ip()
                )
                ->sendURLs(
                    route('invoice.paytabs.success') . '?cart_id=' . $cartId . '&invoice_id=' . $invoice->id . '&amount=' . $validated['amount'] . '&payment_type=' . $validated['payment_type'],
                    route('invoice.paytabs.callback')
                )
                ->sendLanguage('en')
                ->sendFramed(false)
                ->create_pay_page();

            if ($pay) {
                $redirectUrl = method_exists($pay, 'getTargetUrl') ? $pay->getTargetUrl() : (string)$pay;

                return response()->json([
                    'success' => true,
                    'redirect_url' => $redirectUrl
                ]);
            }

            return response()->json(['success' => false, 'message' => __('Payment initialization failed.')], 400);

        } catch (\Exception $e) {
            \Log::info('PayTabs payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => __('Payment processing failed.')], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $cartId = $request->input('cart_id');
            $invoiceId = $request->input('invoice_id');
            $amount = $request->input('amount');
            $paymentType = $request->input('payment_type');

            if (!$cartId || !$invoiceId || !$amount || !$paymentType) {
                return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Invalid payment parameters'));
            }

            $invoice = Invoice::findOrFail($invoiceId);

            InvoicePayment::storePayment([
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'payment_method' => 'paytabs',
                'payment_id' => $cartId,
            ]);

            \Log::info('PayTabs payment successful', [
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'cart_id' => $cartId
            ]);

            return redirect()->route('invoices.public', ['invoice' => encrypt($invoiceId)])->with('success', __('Payment completed successfully!'));

        } catch (\Exception $e) {
            \Log::info('PayTabs success callback error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Payment verification failed.'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $cartId = $request->input('cartId') ?? $request->input('cart_id');
            $respStatus = $request->input('respStatus') ?? $request->input('resp_status');
            $tranRef = $request->input('tranRef') ?? $request->input('tran_ref');

            \Log::info('PayTabs callback received', [
                'cart_id' => $cartId,
                'resp_status' => $respStatus,
                'tran_ref' => $tranRef,
                'all_params' => $request->all()
            ]);

            return response('OK', 200);

        } catch (\Exception $e) {
            \Log::info('PayTabs callback error', [
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
