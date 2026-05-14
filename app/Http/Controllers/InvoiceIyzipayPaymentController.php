<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Iyzipay\Options;
use Iyzipay\Model\CheckoutForm;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Iyzipay\Request\RetrieveCheckoutFormRequest;
use Iyzipay\Model\Locale;
use Iyzipay\Model\Currency;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\Address;

class InvoiceIyzipayPaymentController extends Controller
{
    private function getIyzipayOptions($settings)
    {
        $options = new Options();
        $options->setApiKey($settings['iyzipay_public_key']);
        $options->setSecretKey($settings['iyzipay_secret_key']);
        $options->setBaseUrl($settings['iyzipay_mode'] === 'live'
            ? 'https://api.iyzipay.com'
            : 'https://sandbox-api.iyzipay.com');

        return $options;
    }

    public function createPaymentForm(Request $request)
    {
        $validated = $request->validate([
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
                return response()->json(['error' => $validation['message']], 400);
            }

            $companyId = $invoice->created_by;
            $company = User::findOrFail($companyId);
            $settings = $this->getInvoicePaymentSettings($companyId);

            if (!isset($settings['payment_settings']['iyzipay_secret_key']) || !isset($settings['payment_settings']['iyzipay_public_key'])) {
                return response()->json(['error' => __('Iyzipay not configured')], 400);
            }

            $conversationId = 'invoice_' . $invoice->id . '_' . time();
            $options = $this->getIyzipayOptions($settings['payment_settings']);

            // Create checkout form initialize request
            $checkoutRequest = new CreateCheckoutFormInitializeRequest();
            $checkoutRequest->setLocale(Locale::EN);
            $checkoutRequest->setConversationId($conversationId);
            $checkoutRequest->setPrice(number_format($validated['amount'], 2, '.', ''));
            $checkoutRequest->setPaidPrice(number_format($validated['amount'], 2, '.', ''));
            $checkoutRequest->setCurrency(Currency::USD);
            $checkoutRequest->setBasketId('invoice_' . $invoice->id);
            $checkoutRequest->setPaymentGroup(PaymentGroup::PRODUCT);
            $callbackUrl = route('invoice.iyzipay.callback') . '?invoice_id=' . $invoice->id . '&amount=' . $validated['amount'] . '&payment_type=' . $validated['payment_type'];
            $checkoutRequest->setCallbackUrl($callbackUrl);
            $checkoutRequest->setEnabledInstallments([1]);

            // Set buyer information
            $buyer = new Buyer();
            $buyer->setId($company->id);
            $buyer->setName($company->name ?? 'Customer');
            $buyer->setSurname('User');
            $buyer->setGsmNumber('+1234567890');
            $buyer->setEmail($company->email);
            $buyer->setIdentityNumber('11111111111');
            $buyer->setLastLoginDate(now()->format('Y-m-d H:i:s'));
            $buyer->setRegistrationDate($company->created_at->format('Y-m-d H:i:s'));
            $buyer->setRegistrationAddress($invoice->billing_address ?? '123 Main Street');
            $buyer->setIp($request->ip());
            $buyer->setCity($invoice->billing_city ?? 'New York');
            $buyer->setCountry($invoice->billing_country ?? 'United States');
            $buyer->setZipCode($invoice->billing_postal_code ?? '10001');
            $checkoutRequest->setBuyer($buyer);

            // Set shipping address
            $shippingAddress = new Address();
            $shippingAddress->setContactName($company->name ?? 'Customer User');
            $shippingAddress->setCity($invoice->billing_city ?? 'New York');
            $shippingAddress->setCountry($invoice->billing_country ?? 'United States');
            $shippingAddress->setAddress($invoice->billing_address ?? '123 Main Street');
            $shippingAddress->setZipCode($invoice->billing_postal_code ?? '10001');
            $checkoutRequest->setShippingAddress($shippingAddress);

            // Set billing address
            $billingAddress = new Address();
            $billingAddress->setContactName($company->name ?? 'Customer User');
            $billingAddress->setCity($invoice->billing_city ?? 'New York');
            $billingAddress->setCountry($invoice->billing_country ?? 'United States');
            $billingAddress->setAddress($invoice->billing_address ?? '123 Main Street');
            $billingAddress->setZipCode($invoice->billing_postal_code ?? '10001');
            $checkoutRequest->setBillingAddress($billingAddress);

            // Set basket items
            $basketItem = new BasketItem();
            $basketItem->setId($invoice->id);
            $basketItem->setName('Invoice #' . $invoice->invoice_number);
            $basketItem->setCategory1('Invoice Payment');
            $basketItem->setItemType(BasketItemType::VIRTUAL);
            $basketItem->setPrice(number_format($validated['amount'], 2, '.', ''));
            $basketItems = [$basketItem];
            $checkoutRequest->setBasketItems($basketItems);

            // Initialize checkout form
            $checkoutFormInitialize = CheckoutFormInitialize::create($checkoutRequest, $options);

            if ($checkoutFormInitialize->getStatus() === 'success') {
                return response()->json([
                    'success' => true,
                    'redirect_url' => $checkoutFormInitialize->getPaymentPageUrl(),
                    'token' => $checkoutFormInitialize->getToken()
                ]);
            } else {
                return response()->json(['error' => $checkoutFormInitialize->getErrorMessage()], 400);
            }

        } catch (\Exception $e) {
            \Log::error('Iyzipay invoice payment form creation error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => __('Payment form creation failed')], 500);
        }
    }

    public function callback(Request $request)
    {
        try {
            $token = $request->input('token');
            $invoiceId = $request->input('invoice_id');
            $amount = $request->input('amount');
            $paymentType = $request->input('payment_type');

            if (!$token || !$invoiceId) {
                return redirect()->route('invoices.public', encrypt($invoiceId ?? 0))->withErrors(['error' => __('Invalid payment response')]);
            }

            $invoice = Invoice::findOrFail($invoiceId);
            $companyId = $invoice->created_by;
            $settings = $this->getInvoicePaymentSettings($companyId);

            // Retrieve payment result from Iyzipay
            $paymentResult = $this->retrieveIyzipayPayment($token, $settings['payment_settings']);

            if ($paymentResult && $paymentResult->getPaymentStatus() === 'SUCCESS') {
                InvoicePayment::storePayment([
                    'invoice_id' => $invoice->id,
                    'amount' => $amount,
                    'payment_type' => $paymentType,
                    'payment_method' => 'iyzipay',
                    'payment_id' => $paymentResult->getPaymentId(),
                ]);

                \Log::info('Iyzipay invoice payment successful', [
                    'invoice_id' => $invoice->id,
                    'amount' => $amount,
                    'payment_type' => $paymentType,
                    'payment_id' => $paymentResult->getPaymentId()
                ]);

                return redirect()->route('invoices.public', encrypt($invoice->id))->with('success', __('Payment successful'));
            }

            return redirect()->route('invoices.public', encrypt($invoice->id))->withErrors(['error' => __('Payment failed or cancelled')]);

        } catch (\Exception $e) {
            \Log::error('Iyzipay invoice payment callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('invoices.public', encrypt($request->input('invoice_id') ?? 0))->withErrors(['error' => __('Payment processing failed')]);
        }
    }

    private function retrieveIyzipayPayment($token, $settings)
    {
        try {
            $options = $this->getIyzipayOptions($settings);

            $request = new RetrieveCheckoutFormRequest();
            $request->setToken($token);

            $checkoutForm = CheckoutForm::retrieve($request, $options);

            return $checkoutForm;
        } catch (\Exception $e) {
            return null;
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
