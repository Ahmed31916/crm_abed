<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;
use MercadoPago\SDK;
use MercadoPago\Preference;
use MercadoPago\Item;

class InvoiceMercadoPagoPaymentController extends Controller
{
    public function createPreference(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request);

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $companyId = $invoice->created_by;
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (!isset($settings['payment_settings']['mercadopago_access_token'])) {
                return response()->json(['error' => __('MercadoPago not configured')], 400);
            }

            // Validate payment amount and type
            $validation = $invoice->validatePaymentAmount($validated['amount'], $validated['payment_type']);
            if (!$validation['valid']) {
                return response()->json(['error' => $validation['message']], 400);
            }

            $accessToken = $settings['payment_settings']['mercadopago_access_token'];
            $currency = $settings['general_settings']['defaultCurrency'] ?? 'BRL';
            $mode = $settings['payment_settings']['mercadopago_mode'] ?? 'sandbox';

            SDK::setAccessToken($accessToken);

            $preference = new Preference();

            $item = new Item();
            $item->title = "Invoice #{$invoice->invoice_number} - {$validated['payment_type']} payment";
            $item->quantity = 1;
            $item->unit_price = (float)$validated['amount'];
            $item->currency_id = $currency;
            $item->id = "invoice_{$invoice->id}";

            $preference->items = [$item];

            $preference->back_urls = [
                "success" => route('invoice.mercadopago.success'),
                "failure" => route('invoice.mercadopago.failure'),
                "pending" => route('invoice.mercadopago.pending')
            ];

            $externalReference = "invoice_{$invoice->id}_{$validated['amount']}_{$validated['payment_type']}_" . time();
            $preference->external_reference = $externalReference;

            $preference->binary_mode = true;

            $result = $preference->save();

            if (!$result || !$preference->id) {
                throw new \Exception(__('Failed to create MercadoPago preference'));
            }

            $redirectUrl = $mode === 'sandbox' ? $preference->sandbox_init_point : $preference->init_point;

            return response()->json([
                'preference_id' => $preference->id,
                'redirect_url' => $redirectUrl,
                'mode' => $mode
            ]);

        } catch (\Exception $e) {
            \Log::error('MercadoPago preference creation failed', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function success(Request $request)
    {
        return $this->handlePaymentCallback($request, 'success');
    }

    public function failure(Request $request)
    {
        return $this->handlePaymentCallback($request, 'failure');
    }

    public function pending(Request $request)
    {
        return $this->handlePaymentCallback($request, 'pending');
    }

    private function handlePaymentCallback(Request $request, string $status)
    {
        try {
            $paymentId = $request->payment_id;
            $externalReference = $request->external_reference;

            if (!$externalReference) {
                return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Invalid payment reference'));
            }

            // Parse external reference: invoice_{id}_{amount}_{type}_{timestamp}
            $parts = explode('_', $externalReference);
            if (count($parts) < 4) {
                return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Invalid payment reference format'));
            }

            $invoiceId = (int)$parts[1];
            $amount = (float)$parts[2];
            $paymentType = $parts[3];

            $invoice = Invoice::findOrFail($invoiceId);

            if ($status === 'success') {
                InvoicePayment::storePayment([
                    'invoice_id' => $invoiceId,
                    'amount' => $amount,
                    'payment_type' => $paymentType,
                    'payment_method' => 'mercadopago',
                    'payment_id' => $paymentId,
                ]);

                \Log::info('MercadoPago payment successful', [
                    'invoice_id' => $invoiceId,
                    'amount' => $amount,
                    'payment_type' => $paymentType,
                    'payment_id' => $paymentId
                ]);

                return redirect()->route('invoices.public', $invoice)->with('success', __('Payment successful'));
            } elseif ($status === 'pending') {
                return redirect()->route('invoices.public', $invoice)->with('info', __('Your payment is pending. We will notify you once it is confirmed.'));
            } else {
                return redirect()->route('invoices.public', $invoice)->with('error', __('Payment failed'));
            }

        } catch (\Exception $e) {
            \Log::error('MercadoPago payment processing failed', [
                'error' => $e->getMessage(),
                'status' => $status
            ]);
            return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Payment processing failed'));
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