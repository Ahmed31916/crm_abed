<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceReminderController extends Controller
{
    public function sendReminder(Request $request, $invoiceId)
    {

        if (!auth()->user()->can('send-reminder-invoices')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validated = $request->validate([
            'type' => 'required|string|in:email',
        ]);

        $invoice = Invoice::where('id', $invoiceId)
            ->where('created_by', createdBy())
            ->with(['account', 'contact'])
            ->first();

        if (!$invoice) {
            return redirect()->back()->with('error', __('Invoice not found.'));
        }

        if (!in_array($invoice->status, ['pending', 'overdue', 'partially_paid'])) {
            return redirect()->back()->with('error', __('Reminders can only be sent for pending, overdue, or partially paid invoices.'));
        }

        if (!$invoice->contact || !$invoice->contact->email) {
            return redirect()->back()->with('error', __('No contact email found for this invoice.'));
        }

        $recipient = $invoice->contact->email;
        $recipientName = $invoice->contact->name;

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('error', __('Invalid email address.'));
        }

        try {
            if (!IsDemo()) {
                event(new \App\Events\InvoiceReminderSent($invoice, $recipient, $recipientName));
            }

            $emailError = session()->pull('email_error');

            if (!empty($emailError)) {
                return redirect()->back()->with('error', __('Email send failed: ') . $emailError);
            }

            \App\Models\InvoiceReminder::create([
                'invoice_id' => $invoice->id,
                'sent_by' => auth()->id(),
                'created_by' => createdBy(),
                'type' => $validated['type'],
            ]);

            return redirect()->back()->with('success', __('Payment reminder sent successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to send payment reminder.'));
        }
    }
}
