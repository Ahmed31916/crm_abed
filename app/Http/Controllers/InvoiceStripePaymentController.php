<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class InvoiceStripePaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request, [
            'payment_method_id' => 'required|string',
            'cardholder_name' => 'required|string',
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
                return back()->withErrors(['error' => $validation['message']]);
            }

            $companyId = $invoice->created_by;
            $company = User::findOrFail($companyId);
            $settings = $this->getInvoicePaymentSettings($companyId);
            $currency = $settings['general_settings']['defaultCurrency'] ?? 'usd';

            if (!isset($settings['payment_settings']['stripe_secret']) || !isset($settings['payment_settings']['stripe_key'])) {
                \Log::error('Stripe payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return back()->withErrors(['error' => __('Stripe not configured')]);
            }

            $stripeSecret = $settings['payment_settings']['stripe_secret'];
            if (!str_starts_with($stripeSecret, 'sk_')) {
                \Log::error('Stripe payment failed: Invalid secret key format', ['invoice_id' => $invoice->id]);
                return back()->withErrors(['error' => __('Invalid Stripe secret key format')]);
            }

            Stripe::setApiKey($stripeSecret);
            $paymentIntent = PaymentIntent::create([
                'amount' => $validated['amount'] * 100,
                'currency' => $currency,
                'payment_method' => $validated['payment_method_id'],
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => route('invoices.public', $invoice->id),
                'description' => 'Invoice Payment - ' . $invoice->invoice_number . ' - ' . ucfirst($validated['payment_type']) . ' payment',
                'shipping' => [
                    'name' => $validated['cardholder_name'],
                    'address' => [
                        'line1' => $invoice->billing_address ?? 'Not provided',
                        'city' => $invoice->billing_city ?? 'Not provided',
                        'state' => $invoice->billing_state ?? 'Not provided',
                        'postal_code' => $invoice->billing_postal_code ?? '000000',
                        'country' => $company->country ?? 'US',
                    ],
                ],
            ]);

            if ($paymentIntent->status === 'succeeded') {
                InvoicePayment::storePayment([
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'payment_method' => 'stripe',
                    'payment_id' => $paymentIntent->id,
                ]);

                \Log::info('Stripe payment successful', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'payment_id' => $paymentIntent->id
                ]);

                return back()->with('success', __('Payment successful'));
            }

            \Log::warning('Stripe payment failed', [
                'invoice_id' => $invoice->id,
                'payment_intent_status' => $paymentIntent->status
            ]);
            return back()->withErrors(['error' => __('Payment failed')]);
        } catch (\Exception $e) {
            \Log::error('Stripe payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleInvoicePaymentError($e, 'stripe');
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



    private function handleInvoicePaymentError($e, $method = 'stripe')
    {
        return back()->withErrors(['error' => __('Payment processing failed. Please try again or contact support.')]);
    }
}
