<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;
use CoinGate\Client;

class InvoiceCoingatePaymentController extends Controller
{
    public function processPayment(Request $request)
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
                return back()->withErrors(['error' => $validation['message']]);
            }

            $companyId = $invoice->created_by;
            $settings = $this->getInvoicePaymentSettings($companyId);
            $currency = $settings['general_settings']['defaultCurrency'] ?? 'USD';

            if (!isset($settings['payment_settings']['coingate_api_token']) || empty($settings['payment_settings']['coingate_api_token'])) {
                \Log::error('Coingate payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return back()->withErrors(['error' => __('Coingate not configured')]);
            }

            // Generate unique order ID (like plan payment)
            $orderId = "invoice_{$invoice->id}_".time();

            // Use official CoinGate package
            $client = new Client(
                $settings['payment_settings']['coingate_api_token'],
                ($settings['payment_settings']['coingate_mode'] ?? 'sandbox') === 'sandbox'
            );

            $orderParams = [
                'order_id' => $orderId,
                'price_amount' => $validated['amount'],
                'price_currency' => $currency,
                'receive_currency' => $currency,
                'callback_url' => route('invoice.coingate.callback'),
                'cancel_url' => route('invoices.public', encrypt($invoice->id)),
                'success_url' => route('invoice.coingate.callback'),
                'title' => 'Invoice #' . $invoice->invoice_number . ' - ' . ucfirst($validated['payment_type']) . ' payment',
                'description' => 'Payment for Invoice #' . $invoice->invoice_number,
            ];

            $orderResponse = $client->order->create($orderParams);

            if ($orderResponse && isset($orderResponse->payment_url)) {
                // Store in session like plan payment with invoice data
                session(['coingate_data' => array_merge((array)$orderResponse, [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type']
                ])]);

                \Log::info('Coingate payment initiated', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'order_id' => $orderId
                ]);

                return redirect($orderResponse->payment_url);
            } else {
                \Log::error('Coingate order creation failed', [
                    'invoice_id' => $invoice->id,
                    'response' => $orderResponse
                ]);
                return back()->withErrors(['error' => __('Payment initialization failed')]);
            }

        } catch (\Exception $e) {
            \Log::error('Coingate payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleInvoicePaymentError($e, 'coingate');
        }
    }

    public function callback(Request $request)
    {
        try {
            $coingateData = session('coingate_data');

            if (!$coingateData) {
                \Log::error('Coingate callback: Data not found in session');
                return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Payment session expired'));
            }

            $orderId = is_object($coingateData) ? $coingateData->order_id : $coingateData['order_id'];

            if (!$orderId) {
                \Log::error('Coingate callback: Order ID not found', [
                    'session_data' => $coingateData
                ]);
                return redirect()->route('invoices.public', ['invoice' => 'unknown'])->with('error', __('Order ID not found'));
            }

            // Only create payment entry on successful callback (no pending status)
            InvoicePayment::storePayment([
                'invoice_id' => $coingateData['invoice_id'],
                'amount' => $coingateData['amount'],
                'payment_type' => $coingateData['payment_type'],
                'payment_method' => 'coingate',
                'payment_id' => $orderId,
            ]);

            // Clear session
            session()->forget('coingate_data');

            \Log::info('Coingate payment completed', [
                'invoice_id' => $coingateData['invoice_id'],
                'payment_id' => $orderId,
                'amount' => $coingateData['amount']
            ]);

            return redirect()->route('invoices.public', encrypt($coingateData['invoice_id']))
                ->with('success', __('Payment successful'));

        } catch (\Exception $e) {
            \Log::error('Coingate callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
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

    private function handleInvoicePaymentError($e, $method = 'coingate')
    {
        return back()->withErrors(['error' => __('Payment processing failed: :message', ['message' => $e->getMessage()])]);
    }
}
