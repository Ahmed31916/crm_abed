<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;

class InvoiceXenditPaymentController extends Controller
{
    public function createPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request);

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $companyId = $invoice->created_by;
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (!isset($settings['payment_settings']['xendit_api_key'])) {
                return response()->json(['error' => __('Xendit not configured')], 400);
            }

            $externalId = 'inv_xendit_' . $invoice->id . '_' . time();

            $invoiceData = [
                'external_id' => $externalId,
                'amount' => $validated['amount'],
                'description' => "Invoice Payment: #{$invoice->invoice_number}",
                'invoice_duration' => 86400,
                'currency' => 'PHP',
                'customer' => [
                    'given_names' => $invoice->account->name ?? $invoice->contact->name ?? 'Customer',
                    'email' => $invoice->account->email ?? $invoice->contact->email ?? 'customer@example.com'
                ],
                'success_redirect_url' => route('invoice.xendit.success', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'external_id' => $externalId
                ]),
                'failure_redirect_url' => route('invoices.public', encrypt($invoice->id))
            ];

            $response = \Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($settings['payment_settings']['xendit_api_key'] . ':'),
                'Content-Type' => 'application/json'
            ])->post('https://api.xendit.co/v2/invoices', $invoiceData);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['invoice_url'])) {
                    return response()->json([
                        'success' => true,
                        'payment_url' => $result['invoice_url'],
                        'external_id' => $externalId
                    ]);
                }
            }

            return response()->json(['error' => $response->body()], 500);

        } catch (\Exception $e) {
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    public function processPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request);

        try {
            $invoice = Invoice::findOrFail($validated['invoice_id']);
            $companyId = $invoice->created_by;
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (!isset($settings['payment_settings']['xendit_api_key'])) {
                return response()->json(['error' => __('Xendit not configured')], 400);
            }

            $externalId = 'inv_xendit_' . $invoice->id . '_' . time();

            $invoiceData = [
                'external_id' => $externalId,
                'amount' => $validated['amount'],
                'description' => "Invoice Payment: #{$invoice->invoice_number}",
                'invoice_duration' => 86400,
                'currency' => 'PHP',
                'customer' => [
                    'given_names' => $invoice->account->name ?? $invoice->contact->name ?? 'Customer',
                    'email' => $invoice->account->email ?? $invoice->contact->email ?? 'customer@example.com'
                ],
                'success_redirect_url' => route('invoice.xendit.success', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'external_id' => $externalId
                ]),
                'failure_redirect_url' => route('invoices.public', encrypt($invoice->id))
            ];

            $response = \Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($settings['payment_settings']['xendit_api_key'] . ':'),
                'Content-Type' => 'application/json'
            ])->post('https://api.xendit.co/v2/invoices', $invoiceData);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['invoice_url'])) {
                    return response()->json([
                        'success' => true,
                        'payment_url' => $result['invoice_url'],
                        'external_id' => $externalId
                    ]);
                }
            }

            return response()->json(['error' => $response->body()], 500);

        } catch (\Exception $e) {
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $invoiceId = $request->input('invoice_id');
            $amount = $request->input('amount');
            $paymentType = $request->input('payment_type');
            $externalId = $request->input('external_id');

            if (!$invoiceId || !$amount || !$paymentType || !$externalId) {
                return redirect()->back()->with('error', __('Invalid payment parameters'));
            }

            $invoice = Invoice::findOrFail($invoiceId);

            InvoicePayment::storePayment([
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'payment_method' => 'xendit',
                'payment_id' => $externalId,
            ]);

            \Log::info('Xendit invoice payment successful', [
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'external_id' => $externalId
            ]);

            return redirect()->route('invoices.public', encrypt($invoiceId))
                ->with('success', __('Payment completed successfully!'));

        } catch (\Exception $e) {
            \Log::error('Xendit invoice success error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            $invoiceId = $request->input('invoice_id');
            if ($invoiceId) {
                return redirect()->route('invoices.public', encrypt($invoiceId))->with('error', __('Payment verification failed'));
            }
            return redirect()->back()->with('error', __('Payment verification failed'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $externalId = $request->input('external_id');
            $status = $request->input('status');

            \Log::info('Xendit invoice callback received', [
                'external_id' => $externalId,
                'status' => $status,
                'all_data' => $request->all()
            ]);

            if ($status === 'PAID') {
                // Extract invoice ID from external ID
                if (preg_match('/inv_xendit_(\d+)_/', $externalId, $matches)) {
                    $invoiceId = $matches[1];
                    $invoice = Invoice::find($invoiceId);

                    if ($invoice) {
                        // Check if payment already exists to avoid duplicates
                        $existingPayment = InvoicePayment::where('payment_id', $externalId)
                            ->where('status', 'completed')
                            ->first();

                        if (!$existingPayment) {
                            $amount = floatval($request->input('amount', 0));
                            $paymentType = $amount >= $invoice->total_amount ? 'full' : 'partial';

                            InvoicePayment::storePayment([
                                'invoice_id' => $invoice->id,
                                'amount' => $amount,
                                'payment_type' => $paymentType,
                                'payment_method' => 'xendit',
                                'payment_id' => $externalId,
                            ]);

                            \Log::info('Xendit invoice payment successful via callback', [
                                'invoice_id' => $invoice->id,
                                'external_id' => $externalId,
                                'amount' => $amount
                            ]);
                        }
                    }
                }
            }

            return response('OK', 200);

        } catch (\Exception $e) {
            \Log::error('Xendit invoice callback error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            return response('Error', 500);
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
