<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Currency;
use App\Models\PaymentSetting;
use App\Models\Webhook;
use App\Models\Workspace;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SettingsController extends Controller
{
    /**
     * Display the main settings page.
     *
     * @return \Inertia\Response
     */
    public function index()
    {

        $user = auth()->user();
        $workspaceId = null;

        // For company users, get the current workspace
        if ($user->type === 'company') {
            $workspaceId = $user->current_workspace_id;
        }

        // Get system settings using helper function
        $systemSettings = settings($user->id, $workspaceId);

        // Add invoice template settings
        $systemSettings['invoiceTemplate'] = $systemSettings['invoiceTemplate'] ?? 'template1';
        $systemSettings['invoiceColor'] = $systemSettings['invoiceColor'] ?? 'ffffff';
        $systemSettings['invoiceQrEnabled'] = $systemSettings['invoiceQrEnabled'] ?? 'off';
        if (!empty($systemSettings['invoiceLogoId'])) {
            $media = Media::find($systemSettings['invoiceLogoId']);
            $systemSettings['invoiceLogo'] = $media ? $media->getUrl() : null;
        }

        // Add quote template settings from database
        $systemSettings['quoteTemplate'] = $systemSettings['quoteTemplate'] ?? 'template1';
        $systemSettings['quoteColor'] = $systemSettings['quoteColor'] ?? 'ffffff';
        $systemSettings['quoteQrEnabled'] = $systemSettings['quoteQrEnabled'] ?? 'off';
        if (!empty($systemSettings['quoteLogoId'])) {
            $media = Media::find($systemSettings['quoteLogoId']);
            $systemSettings['quoteLogo'] = $media ? $media->getUrl() : null;
        }

        // Add sales order template settings
        $systemSettings['salesOrderTemplate'] = $systemSettings['salesOrderTemplate'] ?? 'template1';
        $systemSettings['salesOrderColor'] = $systemSettings['salesOrderColor'] ?? 'ffffff';
        $systemSettings['salesOrderQrEnabled'] = $systemSettings['salesOrderQrEnabled'] ?? 'off';
        if (!empty($systemSettings['salesOrderLogoId'])) {
            $media = Media::find($systemSettings['salesOrderLogoId']);
            $systemSettings['salesOrderLogo'] = $media ? $media->getUrl() : null;
        }
        $systemSettings['logoDark'] = $systemSettings['logoDark'] ?? null;

        // Get ReCaptcha settings separately (always without workspace for company users in non-SaaS mode)
        if ($user->type === 'company') {
            $recaptchaSettings = Setting::where('user_id', $user->id)
                ->whereIn('key', [
                    'recaptchaEnabled',
                    'recaptchaVersion',
                    'recaptchaSiteKey',
                    'recaptchaSecretKey',
                    'enableLogging',
                    'strictlyNecessaryCookies',
                    'cookieTitle',
                    'strictlyCookieTitle',
                    'cookieDescription',
                    'strictlyCookieDescription',
                    'contactUsDescription',
                    'contactUsUrl',
                    'metaKeywords',
                    'metaDescription',
                    'metaImage',
                ])
                ->pluck('value', 'key')->toArray();
            $systemSettings = array_merge($systemSettings, $recaptchaSettings);
        }

        $currencies = Currency::all();
        $paymentSettings = PaymentSetting::getUserSettings($user->id, $workspaceId);

        // Mask sensitive data for display in demo mode
        if (config('app.is_demo', false)) {
            $paymentSettings = $this->maskSensitiveDataForDemo($paymentSettings);
        }
        $webhooks = Webhook::where('user_id', $user->id)
            ->get();

        // Get current workspace for company users
        $currentWorkspace = null;
        if ($user->type === 'company' && $workspaceId) {
            $currentWorkspace = Workspace::find($workspaceId);
        }

        return Inertia::render('settings/index', [
            'systemSettings' => $systemSettings,
            'settings' => $systemSettings, // For helper functions
            'cacheSize' => getCacheSize(),
            'currencies' => $currencies,
            'timezones' => config('timezones'),
            'dateFormats' => config('dateformat'),
            'timeFormats' => config('timeformat'),
            'paymentSettings' => $paymentSettings,
            'currentWorkspace' => $currentWorkspace,
            'webhooks' => $webhooks,
            'isDemoMode' => config('app.is_demo', false),
        ]);
    }

    /**
     * Mask sensitive payment data for demo mode display
     */
    private function maskSensitiveDataForDemo(array $settings): array
    {
        $sensitiveKeys = [
            'stripe_key',
            'stripe_secret',
            'paypal_client_id',
            'paypal_secret_key',
            'razorpay_key',
            'razorpay_secret',
            'mercadopago_access_token',
            'paystack_public_key',
            'paystack_secret_key',
            'flutterwave_public_key',
            'flutterwave_secret_key',
            'paytabs_profile_id',
            'paytabs_server_key',
            'skrill_merchant_id',
            'skrill_secret_word',
            'coingate_api_token',
            'payfast_merchant_id',
            'payfast_merchant_key',
            'payfast_passphrase',
            'tap_secret_key',
            'xendit_api_key',
            'paytr_merchant_key',
            'paytr_merchant_salt',
            'mollie_api_key',
            'toyyibpay_secret_key',
            'paymentwall_public_key',
            'paymentwall_private_key',
            'sspay_secret_key',
            'benefit_secret_key',
            'benefit_public_key',
            'iyzipay_secret_key',
            'iyzipay_public_key',
            'aamarpay_signature',
            'midtrans_secret_key',
            'yookassa_secret_key',
            'nepalste_secret_key',
            'nepalste_public_key',
            'cinetpay_api_key',
            'cinetpay_secret_key',
            'payhere_merchant_secret',
            'payhere_app_secret',
            'fedapay_secret_key',
            'fedapay_public_key',
            'authorizenet_transaction_key',
            'khalti_secret_key',
            'khalti_public_key',
            'easebuzz_merchant_key',
            'easebuzz_salt_key',
            'ozow_private_key',
            'ozow_api_key',
            'cashfree_secret_key',
            'cashfree_public_key'
        ];

        foreach ($sensitiveKeys as $key) {
            if (isset($settings[$key]) && !empty($settings[$key])) {
                $settings[$key] = str_repeat('*', strlen(100));
            }
        }

        if (isset($settings['bank_detail']) && !empty($settings['bank_detail'])) {
            $settings['bank_detail'] = "Bank: ****\nAccount: ****\nRouting: ****";
        }

        return $settings;
    }
}
