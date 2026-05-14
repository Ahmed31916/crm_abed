<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Http\Request;

class InvoiceBankPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request, [
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            
            $this->createInvoicePayment([
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
                'payment_type' => $validated['payment_type'],
                'payment_method' => 'bank',
                'payment_id' => 'BANK_' . strtoupper(uniqid()),
                'status' => 'pending',
            ]);

            return back()->with('success', __('Payment request submitted. Your invoice will be updated after payment verification.'));

        } catch (\Exception $e) {
            return $this->handleInvoicePaymentError($e, 'bank');
        }
    }

    
    private function validateInvoicePaymentRequest($request, $additionalRules = [])
    {
        $baseRules = [
            'invoice_id' => 'required|exists:invoices,id',
            'payment_type' => 'required|in:full,partial',
        ];
        
        return $request->validate(array_merge($baseRules, $additionalRules));
    }
    
    private function createInvoicePayment($data)
    {
        $invoice = Invoice::findOrFail($data['invoice_id']);
        
        // Auto-correct payment type if partial payment equals remaining balance
        $remainingAmount = $invoice->getRemainingAmount();
        if ($data['payment_type'] === 'partial' && $data['amount'] == $remainingAmount) {
            $data['payment_type'] = 'full';
        }
        
        // Validate payment amount and type
        $validation = $invoice->validatePaymentAmount($data['amount'], $data['payment_type']);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException($validation['message']);
        }
        
        return InvoicePayment::create([
            'invoice_id' => $data['invoice_id'],
            'amount' => $data['amount'],
            'payment_type' => $data['payment_type'],
            'payment_method' => $data['payment_method'],
            'payment_id' => $data['payment_id'],
            'status' => $data['status'] ?? 'pending',
            'processed_at' => $data['status'] === 'completed' ? now() : null,
            'notes' => "Invoice #{$invoice->invoice_number} - {$data['payment_type']} payment (Bank Transfer)",
        ]);
    }
    
    private function handleInvoicePaymentError($e, $method = 'bank')
    {
        return back()->withErrors(['error' => __('Payment processing failed: :message', ['message' => $e->getMessage()])]);
    }
}