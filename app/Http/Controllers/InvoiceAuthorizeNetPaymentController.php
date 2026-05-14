<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class InvoiceAuthorizeNetPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = $this->validateInvoicePaymentRequest($request, [
            'card_number' => 'required|string',
            'expiry_month' => 'required|string|size:2',
            'expiry_year' => 'required|string|size:2',
            'cvv' => 'required|string|min:3|max:4',
            'cardholder_name' => 'required|string|min:2|max:50',
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

            if (!isset($settings['payment_settings']['authorizenet_merchant_id']) || 
                !isset($settings['payment_settings']['authorizenet_transaction_key'])) {
                \Log::error('AuthorizeNet payment failed: Configuration missing', ['invoice_id' => $invoice->id]);
                return back()->withErrors(['error' => __('AuthorizeNet not configured')]);
            }

            // Validate minimum amount
            if ($validated['amount'] < 0.50) {
                return back()->withErrors(['error' => __('Minimum payment amount is $0.50')]);
            }

            $result = $this->createAuthorizeNetTransaction($validated, $invoice, $company, $settings);

            if ($result['success']) {
                InvoicePayment::storePayment([
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'payment_method' => 'authorizenet',
                    'payment_id' => $result['transaction_id'],
                ]);

                \Log::info('AuthorizeNet invoice payment successful', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'payment_type' => $validated['payment_type'],
                    'transaction_id' => $result['transaction_id']
                ]);

                return back()->with('success', __('Payment successful'));
            }

            return back()->withErrors(['error' => $result['error']]);

        } catch (\Exception $e) {
            \Log::error('AuthorizeNet payment error', [
                'invoice_id' => $validated['invoice_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => __('Payment processing failed. Please try again.')]);
        }
    }

    private function createAuthorizeNetTransaction($paymentData, $invoice, $company, $settings)
    {
        try {
            $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
            $merchantAuthentication->setName($settings['payment_settings']['authorizenet_merchant_id']);
            $merchantAuthentication->setTransactionKey($settings['payment_settings']['authorizenet_transaction_key']);

            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber(preg_replace('/\s+/', '', $paymentData['card_number']));
            
            $expiryYear = 2000 + intval($paymentData['expiry_year']);
            $expiryMonth = str_pad($paymentData['expiry_month'], 2, '0', STR_PAD_LEFT);
            $creditCard->setExpirationDate($expiryYear . '-' . $expiryMonth);
            $creditCard->setCardCode($paymentData['cvv']);

            $paymentOne = new AnetAPI\PaymentType();
            $paymentOne->setCreditCard($creditCard);

            $order = new AnetAPI\OrderType();
            $order->setInvoiceNumber($invoice->invoice_number);
            $order->setDescription('Invoice Payment - ' . $invoice->invoice_number);

            $billTo = new AnetAPI\CustomerAddressType();
            $billTo->setFirstName(explode(' ', $paymentData['cardholder_name'])[0]);
            $billTo->setLastName(implode(' ', array_slice(explode(' ', $paymentData['cardholder_name']), 1)) ?: 'Customer');
            $billTo->setCompany($invoice->name ?? '');
            $billTo->setAddress($invoice->billing_address ?? '-');
            $billTo->setCity($invoice->billing_city ?? '-');
            $billTo->setState($invoice->billing_state ?? '-');
            $billTo->setZip($invoice->billing_postal_code ?? '00000');
            $billTo->setCountry($company->country ?? 'US');

            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType('authCaptureTransaction');
            $transactionRequestType->setAmount(number_format($paymentData['amount'], 2, '.', ''));
            $transactionRequestType->setPayment($paymentOne);
            $transactionRequestType->setOrder($order);
            $transactionRequestType->setBillTo($billTo);

            $request = new AnetAPI\CreateTransactionRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setTransactionRequest($transactionRequestType);

            $controller = new AnetController\CreateTransactionController($request);
            
            $environment = ($settings['payment_settings']['authorizenet_mode'] === 'sandbox') 
                ? \net\authorize\api\constants\ANetEnvironment::SANDBOX 
                : \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
                
            $response = $controller->executeWithApiResponse($environment);

            return $this->handleAuthorizeNetResponse($response);
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => __('Transaction processing failed. Please check your card details and try again.'),
                'transaction_id' => null
            ];
        }
    }

    private function handleAuthorizeNetResponse($response)
    {
        if ($response === null) {
            return ['success' => false, 'error' => __('No response received from payment gateway'), 'transaction_id' => null];
        }

        $messages = $response->getMessages();
        
        if ($messages->getResultCode() !== 'Ok') {
            $errorMessage = __('Payment gateway error');
            if ($messages->getMessage() && count($messages->getMessage()) > 0) {
                $errorMessage = $messages->getMessage()[0]->getText();
            }
            
            return ['success' => false, 'error' => $errorMessage, 'transaction_id' => null];
        }

        $tresponse = $response->getTransactionResponse();
        
        if ($tresponse === null) {
            return ['success' => false, 'error' => __('Invalid transaction response'), 'transaction_id' => null];
        }

        $responseCode = $tresponse->getResponseCode();
        
        switch ($responseCode) {
            case '1':
                return ['success' => true, 'error' => null, 'transaction_id' => $tresponse->getTransId()];
                
            case '2':
            case '3':
                $errorMessage = 'Transaction declined';
                if ($tresponse->getErrors() && count($tresponse->getErrors()) > 0) {
                    $errorMessage = $tresponse->getErrors()[0]->getErrorText();
                }
                return ['success' => false, 'error' => $errorMessage, 'transaction_id' => null];
                
            case '4':
                return ['success' => false, 'error' => __('Transaction is being reviewed. Please contact support.'), 'transaction_id' => $tresponse->getTransId()];
                
            default:
                return ['success' => false, 'error' => __('Unknown transaction response'), 'transaction_id' => null];
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
