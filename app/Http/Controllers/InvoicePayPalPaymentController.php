<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Http\Request;

class InvoicePayPalPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|string',
            'payment_id' => 'required|string',
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_type' => 'required|in:full,partial',
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

            // Store invoice payment using common method
            InvoicePayment::storePayment([
                'invoice_id' => $validated['invoice_id'],
                'amount' => $validated['amount'],
                'payment_type' => $validated['payment_type'],
                'payment_method' => 'paypal',
                'payment_id' => $validated['payment_id'],
            ]);

            \Log::info('PayPal payment successful', [
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
                'payment_type' => $validated['payment_type'],
                'payment_id' => $validated['payment_id']
            ]);

            return back()->with('success', __('Payment successful'));

        } catch (\Exception $e) {
            \Log::error('PayPal payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => __('Payment processing failed. Please try again or contact support.')]);
        }
    }
}
