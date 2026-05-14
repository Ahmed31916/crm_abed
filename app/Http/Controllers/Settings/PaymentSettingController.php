<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentSettingController extends Controller
{
    public function index()
    {
        $paymentSettings = getPaymentSettings();

        return Inertia::render('settings/index', [
            'paymentSettings' => $paymentSettings,
        ]);
    }

    public function getPaymentMethods()
    {
        $superAdminId = \App\Models\User::where('type', 'superadmin')->first()?->id;

        if (!$superAdminId) {
            return response()->json([]);
        }

        $paymentSettings = getPaymentSettings($superAdminId);

        // Filter out sensitive credentials and only return safe configuration
        $safeSettings = $this->filterSensitiveData($paymentSettings);

        // Add default currency to payment settings
        $settings = settings($superAdminId);
        $safeSettings['defaultCurrency'] = $settings['defaultCurrency'] ?? 'usd';

        return response()->json($safeSettings);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'stripe_key' => 'nullable|string',
                'stripe_secret' => 'nullable|string',
                'paypal_client_id' => 'nullable|string',
                'paypal_secret_key' => 'nullable|string',
                'paypal_mode' => 'in:sandbox,live',
                'bank_detail' => 'nullable|string',
                'razorpay_key' => 'nullable|string',
                'razorpay_secret' => 'nullable|string',
                'mercadopago_mode' => 'in:sandbox,live',
                'mercadopago_access_token' => 'nullable|string',
                'paystack_public_key' => 'nullable|string',
                'paystack_secret_key' => 'nullable|string',
                'flutterwave_public_key' => 'nullable|string',
                'flutterwave_secret_key' => 'nullable|string',
                'paytabs_profile_id' => 'nullable|string',
                'paytabs_server_key' => 'nullable|string',
                'paytabs_region' => 'nullable|string',
                'paytabs_mode' => 'in:sandbox,live',
                'skrill_merchant_id' => 'nullable|string',
                'skrill_secret_word' => 'nullable|string',
                'coingate_api_token' => 'nullable|string',
                'coingate_mode' => 'in:sandbox,live',
                'payfast_merchant_id' => 'nullable|string',
                'payfast_merchant_key' => 'nullable|string',
                'payfast_passphrase' => 'nullable|string',
                'payfast_mode' => 'in:sandbox,live',
                'tap_secret_key' => 'nullable|string',
                'xendit_api_key' => 'nullable|string',
                'paytr_merchant_id' => 'nullable|string',
                'paytr_merchant_key' => 'nullable|string',
                'paytr_merchant_salt' => 'nullable|string',
                'mollie_api_key' => 'nullable|string',
                'toyyibpay_category_code' => 'nullable|string',
                'toyyibpay_secret_key' => 'nullable|string',
                'paymentwall_public_key' => 'nullable|string',
                'paymentwall_private_key' => 'nullable|string',
                'sspay_secret_key' => 'nullable|string',
                'sspay_category_code' => 'nullable|string',
                'benefit_mode' => 'in:sandbox,live',
                'benefit_secret_key' => 'nullable|string',
                'benefit_public_key' => 'nullable|string',
                'iyzipay_mode' => 'in:sandbox,live',
                'iyzipay_secret_key' => 'nullable|string',
                'iyzipay_public_key' => 'nullable|string',
                'aamarpay_store_id' => 'nullable|string',
                'aamarpay_signature' => 'nullable|string',
                'midtrans_mode' => 'in:sandbox,live',
                'midtrans_secret_key' => 'nullable|string',
                'yookassa_shop_id' => 'nullable|string',
                'yookassa_secret_key' => 'nullable|string',
                'nepalste_mode' => 'in:sandbox,live',
                'nepalste_secret_key' => 'nullable|string',
                'nepalste_public_key' => 'nullable|string',
                'paiement_merchant_id' => 'nullable|string',
                'cinetpay_site_id' => 'nullable|string',
                'cinetpay_api_key' => 'nullable|string',
                'cinetpay_secret_key' => 'nullable|string',
                'payhere_mode' => 'in:sandbox,live',
                'payhere_merchant_id' => 'nullable|string',
                'payhere_merchant_secret' => 'nullable|string',
                'payhere_app_id' => 'nullable|string',
                'payhere_app_secret' => 'nullable|string',
                'fedapay_mode' => 'in:sandbox,live',
                'fedapay_secret_key' => 'nullable|string',
                'fedapay_public_key' => 'nullable|string',
                'authorizenet_mode' => 'in:sandbox,live',
                'authorizenet_merchant_id' => 'nullable|string',
                'authorizenet_transaction_key' => 'nullable|string',
                'khalti_secret_key' => 'nullable|string',
                'khalti_public_key' => 'nullable|string',
                'easebuzz_merchant_key' => 'nullable|string',
                'easebuzz_salt_key' => 'nullable|string',
                'easebuzz_environment' => 'nullable|string',
                'ozow_mode' => 'in:sandbox,live',
                'ozow_site_key' => 'nullable|string',
                'ozow_private_key' => 'nullable|string',
                'ozow_api_key' => 'nullable|string',
                'cashfree_mode' => 'in:sandbox,live',
                'cashfree_secret_key' => 'nullable|string',
                'cashfree_public_key' => 'nullable|string',
            ]);

            $settings = $this->preparePaymentSettings($request, $validatedData);
            $this->validateEnabledPaymentMethods($request, $validatedData);
            $this->savePaymentSettings($settings);

            return back()->with('success', __('Payment settings saved successfully.'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            return back()->withErrors(['error' => __('Failed to save payment settings: :message', ['message' => $e->getMessage()])]);
        }
    }

    private function preparePaymentSettings(Request $request, array $validatedData): array
    {
        return [
            'is_manually_enabled' => $request->boolean('is_manually_enabled'),
            'is_bank_enabled' => $request->boolean('is_bank_enabled'),
            'is_stripe_enabled' => $request->boolean('is_stripe_enabled'),
            'is_paypal_enabled' => $request->boolean('is_paypal_enabled'),
            'is_razorpay_enabled' => $request->boolean('is_razorpay_enabled'),
            'is_mercadopago_enabled' => $request->boolean('is_mercadopago_enabled'),
            'is_paystack_enabled' => $request->boolean('is_paystack_enabled'),
            'is_flutterwave_enabled' => $request->boolean('is_flutterwave_enabled'),
            'is_paytabs_enabled' => $request->boolean('is_paytabs_enabled'),
            'is_skrill_enabled' => $request->boolean('is_skrill_enabled'),
            'is_coingate_enabled' => $request->boolean('is_coingate_enabled'),
            'is_payfast_enabled' => $request->boolean('is_payfast_enabled'),
            'is_tap_enabled' => $request->boolean('is_tap_enabled'),
            'is_xendit_enabled' => $request->boolean('is_xendit_enabled'),
            'is_paytr_enabled' => $request->boolean('is_paytr_enabled'),
            'is_mollie_enabled' => $request->boolean('is_mollie_enabled'),
            'is_toyyibpay_enabled' => $request->boolean('is_toyyibpay_enabled'),
            'is_paymentwall_enabled' => $request->boolean('is_paymentwall_enabled'),
            'is_sspay_enabled' => $request->boolean('is_sspay_enabled'),
            'is_benefit_enabled' => $request->boolean('is_benefit_enabled'),
            'is_iyzipay_enabled' => $request->boolean('is_iyzipay_enabled'),
            'is_aamarpay_enabled' => $request->boolean('is_aamarpay_enabled'),
            'is_midtrans_enabled' => $request->boolean('is_midtrans_enabled'),
            'is_yookassa_enabled' => $request->boolean('is_yookassa_enabled'),
            'is_nepalste_enabled' => $request->boolean('is_nepalste_enabled'),
            'is_paiement_enabled' => $request->boolean('is_paiement_enabled'),
            'is_cinetpay_enabled' => $request->boolean('is_cinetpay_enabled'),
            'is_payhere_enabled' => $request->boolean('is_payhere_enabled'),
            'is_fedapay_enabled' => $request->boolean('is_fedapay_enabled'),
            'is_authorizenet_enabled' => $request->boolean('is_authorizenet_enabled'),
            'is_khalti_enabled' => $request->boolean('is_khalti_enabled'),
            'is_easebuzz_enabled' => $request->boolean('is_easebuzz_enabled'),
            'is_ozow_enabled' => $request->boolean('is_ozow_enabled'),
            'is_cashfree_enabled' => $request->boolean('is_cashfree_enabled'),
            'paypal_mode' => $validatedData['paypal_mode'] ?? 'sandbox',
            'mercadopago_mode' => $validatedData['mercadopago_mode'] ?? 'sandbox',
            'bank_detail' => $validatedData['bank_detail'],
            'stripe_key' => $validatedData['stripe_key'],
            'stripe_secret' => $validatedData['stripe_secret'],
            'paypal_client_id' => $validatedData['paypal_client_id'],
            'paypal_secret_key' => $validatedData['paypal_secret_key'],
            'razorpay_key' => $validatedData['razorpay_key'],
            'razorpay_secret' => $validatedData['razorpay_secret'],
            'mercadopago_access_token' => $validatedData['mercadopago_access_token'],
            'paystack_public_key' => $validatedData['paystack_public_key'],
            'paystack_secret_key' => $validatedData['paystack_secret_key'],
            'flutterwave_public_key' => $validatedData['flutterwave_public_key'],
            'flutterwave_secret_key' => $validatedData['flutterwave_secret_key'],
            'paytabs_profile_id' => $validatedData['paytabs_profile_id'],
            'paytabs_server_key' => $validatedData['paytabs_server_key'],
            'paytabs_region' => $validatedData['paytabs_region'],
            'paytabs_mode' => $validatedData['paytabs_mode'] ?? 'sandbox',
            'skrill_merchant_id' => $validatedData['skrill_merchant_id'],
            'skrill_secret_word' => $validatedData['skrill_secret_word'],
            'coingate_api_token' => $validatedData['coingate_api_token'],
            'coingate_mode' => $validatedData['coingate_mode'] ?? 'sandbox',
            'payfast_merchant_id' => $validatedData['payfast_merchant_id'],
            'payfast_merchant_key' => $validatedData['payfast_merchant_key'],
            'payfast_passphrase' => $validatedData['payfast_passphrase'],
            'payfast_mode' => $validatedData['payfast_mode'] ?? 'sandbox',
            'tap_secret_key' => $validatedData['tap_secret_key'],
            'xendit_api_key' => $validatedData['xendit_api_key'],
            'paytr_merchant_id' => $validatedData['paytr_merchant_id'],
            'paytr_merchant_key' => $validatedData['paytr_merchant_key'],
            'paytr_merchant_salt' => $validatedData['paytr_merchant_salt'],
            'mollie_api_key' => $validatedData['mollie_api_key'],
            'toyyibpay_category_code' => $validatedData['toyyibpay_category_code'],
            'toyyibpay_secret_key' => $validatedData['toyyibpay_secret_key'],
            'paymentwall_public_key' => $validatedData['paymentwall_public_key'],
            'paymentwall_private_key' => $validatedData['paymentwall_private_key'],
            'sspay_secret_key' => $validatedData['sspay_secret_key'],
            'sspay_category_code' => $validatedData['sspay_category_code'],
            'benefit_mode' => $validatedData['benefit_mode'] ?? 'sandbox',
            'benefit_secret_key' => $validatedData['benefit_secret_key'],
            'benefit_public_key' => $validatedData['benefit_public_key'],
            'iyzipay_mode' => $validatedData['iyzipay_mode'] ?? 'sandbox',
            'iyzipay_secret_key' => $validatedData['iyzipay_secret_key'],
            'iyzipay_public_key' => $validatedData['iyzipay_public_key'],
            'aamarpay_store_id' => $validatedData['aamarpay_store_id'],
            'aamarpay_signature' => $validatedData['aamarpay_signature'],
            'midtrans_mode' => $validatedData['midtrans_mode'] ?? 'sandbox',
            'midtrans_secret_key' => $validatedData['midtrans_secret_key'],
            'yookassa_shop_id' => $validatedData['yookassa_shop_id'],
            'yookassa_secret_key' => $validatedData['yookassa_secret_key'],
            'nepalste_mode' => $validatedData['nepalste_mode'] ?? 'sandbox',
            'nepalste_secret_key' => $validatedData['nepalste_secret_key'],
            'nepalste_public_key' => $validatedData['nepalste_public_key'],
            'paiement_merchant_id' => $validatedData['paiement_merchant_id'],
            'cinetpay_site_id' => $validatedData['cinetpay_site_id'],
            'cinetpay_api_key' => $validatedData['cinetpay_api_key'],
            'cinetpay_secret_key' => $validatedData['cinetpay_secret_key'],
            'payhere_mode' => $validatedData['payhere_mode'] ?? 'sandbox',
            'payhere_merchant_id' => $validatedData['payhere_merchant_id'],
            'payhere_merchant_secret' => $validatedData['payhere_merchant_secret'],
            'payhere_app_id' => $validatedData['payhere_app_id'],
            'payhere_app_secret' => $validatedData['payhere_app_secret'],
            'fedapay_mode' => $validatedData['fedapay_mode'] ?? 'sandbox',
            'fedapay_secret_key' => $validatedData['fedapay_secret_key'],
            'fedapay_public_key' => $validatedData['fedapay_public_key'],
            'authorizenet_mode' => $validatedData['authorizenet_mode'] ?? 'sandbox',
            'authorizenet_merchant_id' => $validatedData['authorizenet_merchant_id'],
            'authorizenet_transaction_key' => $validatedData['authorizenet_transaction_key'],
            'khalti_secret_key' => $validatedData['khalti_secret_key'],
            'khalti_public_key' => $validatedData['khalti_public_key'],
            'easebuzz_merchant_key' => $validatedData['easebuzz_merchant_key'],
            'easebuzz_salt_key' => $validatedData['easebuzz_salt_key'],
            'easebuzz_environment' => $validatedData['easebuzz_environment'],
            'ozow_mode' => $validatedData['ozow_mode'] ?? 'sandbox',
            'ozow_site_key' => $validatedData['ozow_site_key'],
            'ozow_private_key' => $validatedData['ozow_private_key'],
            'ozow_api_key' => $validatedData['ozow_api_key'],
            'cashfree_mode' => $validatedData['cashfree_mode'] ?? 'sandbox',
            'cashfree_secret_key' => $validatedData['cashfree_secret_key'],
            'cashfree_public_key' => $validatedData['cashfree_public_key'],
        ];
    }

    private function validateEnabledPaymentMethods(Request $request, array $validatedData): void
    {
        // Validation logic here
    }

    private function savePaymentSettings(array $settings): void
    {
        $userId = auth()->id();

        foreach ($settings as $key => $value) {
            PaymentSetting::updateOrCreateSetting($userId, $key, $value);
        }
    }

    public function getEnabledMethods()
    {
        $enabledMethods = getEnabledPaymentMethods();

        return response()->json($enabledMethods);
    }

    private function filterSensitiveData(array $settings): array
    {
        $safeSettings = [];

        $enabledKeys = [
            'is_manually_enabled', 'is_bank_enabled', 'is_stripe_enabled', 'is_paypal_enabled',
            'is_razorpay_enabled', 'is_mercadopago_enabled', 'is_paystack_enabled', 'is_flutterwave_enabled',
            'is_paytabs_enabled', 'is_skrill_enabled', 'is_coingate_enabled', 'is_payfast_enabled',
            'is_tap_enabled', 'is_xendit_enabled', 'is_paytr_enabled', 'is_mollie_enabled',
            'is_toyyibpay_enabled', 'is_paymentwall_enabled', 'is_sspay_enabled', 'is_benefit_enabled',
            'is_iyzipay_enabled', 'is_aamarpay_enabled', 'is_midtrans_enabled', 'is_yookassa_enabled',
            'is_nepalste_enabled', 'is_paiement_enabled', 'is_cinetpay_enabled', 'is_payhere_enabled',
            'is_fedapay_enabled', 'is_authorizenet_enabled', 'is_khalti_enabled', 'is_easebuzz_enabled',
            'is_ozow_enabled', 'is_cashfree_enabled'
        ];

        $modeKeys = [
            'paypal_mode', 'mercadopago_mode', 'paytabs_mode', 'coingate_mode', 'payfast_mode',
            'benefit_mode', 'iyzipay_mode', 'midtrans_mode', 'nepalste_mode', 'payhere_mode',
            'fedapay_mode', 'authorizenet_mode', 'ozow_mode', 'cashfree_mode'
        ];

        $frontendKeys = [
            'stripe_key', 'razorpay_key', 'paystack_public_key', 'flutterwave_public_key',
            'khalti_public_key', 'cashfree_public_key', 'iyzipay_public_key', 'benefit_public_key',
            'fedapay_public_key', 'nepalste_public_key', 'paymentwall_public_key',
            'paypal_client_id', 'toyyibpay_category_code', 'aamarpay_store_id',
            'authorizenet_merchant_id', 'cinetpay_site_id', 'easebuzz_merchant_key',
            'ozow_site_key', 'paiement_merchant_id', 'payhere_merchant_id', 'paytr_merchant_id',
            'skrill_merchant_id', 'yookassa_shop_id', 'bank_detail','tap_secret_key', 'xendit_api_key',
            'midtrans_secret_key','sspay_secret_key'
        ];

        foreach (array_merge($enabledKeys, $modeKeys, $frontendKeys) as $key) {
            if (isset($settings[$key])) {
                $safeSettings[$key] = $settings[$key];
            }
        }

        return $safeSettings;
    }
}
