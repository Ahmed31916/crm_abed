<?php

use App\Models\Setting;
use App\Models\User;
use App\Models\Coupon;
use App\Models\NotificationTemplate;
use Carbon\Carbon;
use App\Models\Plan;
use App\Models\PlanOrder;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\PaymentSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

if (!function_exists('getCacheSize')) {
    /**
     * Get the total cache size in MB
     *
     * @return string
     */
    function getCacheSize()
    {
        $file_size = 0;
        $framework_path = storage_path('framework');

        if (is_dir($framework_path)) {
            foreach (\File::allFiles($framework_path) as $file) {
                $file_size += $file->getSize();
            }
        }

        return number_format($file_size / 1000000, 2);
    }
}

if (! function_exists('settings')) {
    function settings($user_id = null)
    {
        // Skip database queries during installation
        if (request()->is('install/*') || request()->is('update/*') || !file_exists(storage_path('installed'))) {
            return [];
        }

        if (is_null($user_id)) {
            if (auth()->user()) {
                if (!in_array(auth()->user()->type, ['superadmin', 'company'])) {
                    $user_id = auth()->user()->created_by;
                } else {
                    $user_id = auth()->id();
                }
            } else {
                $user = User::where('type', 'superadmin')->first();
                $user_id = $user ? $user->id : null;
            }
        }

        if (!$user_id) {
            return collect();
        }

        $userSettings = Setting::where('user_id', $user_id)->pluck('value', 'key')->toArray();

        // If user is not superadmin, merge with superadmin settings for specific keys
        if (auth()->check() && auth()->user()->type !== 'superadmin') {
            $superAdmin = User::where('type', 'superadmin')->first();
            if ($superAdmin) {
                $superAdminKeys = ['dateFormat', 'timeFormat', 'calendarStartDay', 'defaultTimezone'];
                $superAdminSettings = Setting::where('user_id', $superAdmin->id)
                    ->whereIn('key', $superAdminKeys)
                    ->pluck('value', 'key')
                    ->toArray();
                $userSettings = array_merge($superAdminSettings, $userSettings);
            }
        }

        return $userSettings;
    }
}

if (! function_exists('formatDateTime')) {
    function formatDateTime($date, $includeTime = true)
    {
        if (!$date) {
            return null;
        }

        $settings = settings();

        $dateFormat = $settings['dateFormat'] ?? 'Y-m-d';
        $timeFormat = $settings['timeFormat'] ?? 'H:i';
        $timezone = $settings['defaultTimezone'] ?? config('app.timezone', 'UTC');

        $format = $includeTime ? "$dateFormat $timeFormat" : $dateFormat;

        return Carbon::parse($date)->timezone($timezone)->format($format);
    }
}

if (! function_exists('getSetting')) {
    function getSetting($key, $default = null, $user_id = null)
    {
        $settings = settings($user_id);

        // If no value found and no default provided, try to get from defaultSettings
        if (!isset($settings[$key]) && $default === null) {
            $defaultSettings = defaultSettings();
            $default = $defaultSettings[$key] ?? null;
        }

        return $settings[$key] ?? $default;
    }
}

if (! function_exists('updateSetting')) {
    function updateSetting($key, $value, $user_id = null)
    {
        if (is_null($user_id)) {
            if (auth()->user()) {
                if (!in_array(auth()->user()->type, ['superadmin', 'company'])) {
                    $user_id = auth()->user()->created_by;
                } else {
                    $user_id = auth()->id();
                }
            } else {
                $user = User::where('type', 'superadmin')->first();
                $user_id = $user ? $user->id : null;
            }
        }

        if (!$user_id) {
            return false;
        }

        return Setting::updateOrCreate(
            ['user_id' => $user_id, 'key' => $key],
            ['value' => $value]
        );
    }
}

if (! function_exists('isLandingPageEnabled')) {
    function isLandingPageEnabled()
    {
        return getSetting('landingPageEnabled', true) === true || getSetting('landingPageEnabled', true) === '1';
    }
}

if (! function_exists('isRegistrationEnabled')) {
    function isRegistrationEnabled()
    {
        return getSetting('registrationEnabled', true) === true || getSetting('registrationEnabled', true) === '1';
    }
}

if (! function_exists('defaultRoleAndSetting')) {
    function defaultRoleAndSetting($user)
    {
        $companyRole = Role::where('name', 'company')->first();

        if ($companyRole) {
            $user->assignRole($companyRole);
        }

        // Create default settings for the user
        if ($user->type === 'superadmin') {
            createDefaultSettings($user->id);
            createDefaultEmailTemplateSettings($user->id);
            createDefaultNotificationTemplateSettings($user->id);
        } elseif ($user->type === 'company') {
            copySettingsFromSuperAdmin($user->id);
            createDefaultNotificationTemplates($user->id);
            createDefaultEmailTemplateSettings($user->id);
            createDefaultNotificationTemplateSettings($user->id);
            $user->companyDefaultData($user);
        }

        return true;
    }
}

if (! function_exists('createDefaultEmailTemplateSettings')) {
    /**
     * Create default email template settings for a user
     *
     * @param int $userId
     * @return void
     */
    function createDefaultEmailTemplateSettings($userId)
    {
        $templates = \App\Models\EmailTemplate::all();

        foreach ($templates as $template) {
            \App\Models\UserEmailTemplate::updateOrCreate(
                ['user_id' => $userId, 'template_id' => $template->id],
                ['is_active' => false] // Disable all templates by default
            );
        }
    }
}

if (! function_exists('isNotificationTemplateEnabled')) {
    /**
     * Check if a notification template is enabled for a user
     *
     * @param string $templateName
     * @param int|null $userId
     * @return bool
     */
    function isNotificationTemplateEnabled($templateName, $templateType, $userId = null)
    {
        if (is_null($userId)) {
            $userId = createdBy();
        }

        $template = \App\Models\NotificationTemplate::where('name', $templateName)
            ->where('type', $templateType)
            ->first();
        if (!$template) {
            return false;
        }

        $userTemplate = \App\Models\UserNotificationTemplate::where('user_id', $userId)
            ->where('template_id', $template->id)
            ->first();

        return $userTemplate ? $userTemplate->is_active : false;
    }
}

if (! function_exists('createDefaultNotificationTemplateSettings')) {
    /**
     * Create default notification template settings for a user
     *
     * @param int $userId
     * @return void
     */
    function createDefaultNotificationTemplateSettings($userId)
    {
        $templates = \App\Models\NotificationTemplate::all();

        foreach ($templates as $template) {
            \App\Models\UserNotificationTemplate::updateOrCreate(
                ['user_id' => $userId, 'template_id' => $template->id],
                ['is_active' => false] // Disable all templates by default
            );
        }
    }
}

if (! function_exists('getPaymentSettings')) {
    /**
     * Get payment settings for a user
     *
     * @param int|null $userId
     * @return array
     */
    function getPaymentSettings($userId = null)
    {
        if (is_null($userId)) {
            $userId = auth()->id();
        }

        return PaymentSetting::getUserSettings($userId);
    }
}

if (! function_exists('updatePaymentSetting')) {
    /**
     * Update or create a payment setting
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $userId
     * @return \App\Models\PaymentSetting
     */
    function updatePaymentSetting($key, $value, $userId = null)
    {
        if (is_null($userId)) {
            $userId = auth()->id();
        }

        return PaymentSetting::updateOrCreateSetting($userId, $key, $value);
    }
}

if (! function_exists('isPaymentMethodEnabled')) {
    /**
     * Check if a payment method is enabled
     *
     * @param string $method (stripe, paypal, razorpay, mercadopago, bank)
     * @param int|null $userId
     * @return bool
     */
    function isPaymentMethodEnabled($method, $userId = null)
    {
        $settings = getPaymentSettings($userId);
        $key = "is_{$method}_enabled";

        return isset($settings[$key]) && ($settings[$key] === true || $settings[$key] === '1');
    }
}

if (! function_exists('getPaymentMethodConfig')) {
    /**
     * Get configuration for a specific payment method
     *
     * @param string $method (stripe, paypal, razorpay, mercadopago)
     * @param int|null $userId
     * @return array
     */
    function getPaymentMethodConfig($method, $userId = null)
    {
        $settings = getPaymentSettings($userId);

        switch ($method) {
            case 'stripe':
                return [
                    'enabled' => isPaymentMethodEnabled('stripe', $userId),
                    'key' => $settings['stripe_key'] ?? null,
                    'secret' => $settings['stripe_secret'] ?? null,
                ];

            case 'paypal':
                return [
                    'enabled' => isPaymentMethodEnabled('paypal', $userId),
                    'mode' => $settings['paypal_mode'] ?? 'sandbox',
                    'client_id' => $settings['paypal_client_id'] ?? null,
                    'secret' => $settings['paypal_secret_key'] ?? null,
                ];

            case 'razorpay':
                return [
                    'enabled' => isPaymentMethodEnabled('razorpay', $userId),
                    'key' => $settings['razorpay_key'] ?? null,
                    'secret' => $settings['razorpay_secret'] ?? null,
                ];

            case 'mercadopago':
                return [
                    'enabled' => isPaymentMethodEnabled('mercadopago', $userId),
                    'mode' => $settings['mercadopago_mode'] ?? 'sandbox',
                    'access_token' => $settings['mercadopago_access_token'] ?? null,
                ];

            case 'paystack':
                return [
                    'enabled' => isPaymentMethodEnabled('paystack', $userId),
                    'public_key' => $settings['paystack_public_key'] ?? null,
                    'secret_key' => $settings['paystack_secret_key'] ?? null,
                ];

            case 'flutterwave':
                return [
                    'enabled' => isPaymentMethodEnabled('flutterwave', $userId),
                    'public_key' => $settings['flutterwave_public_key'] ?? null,
                    'secret_key' => $settings['flutterwave_secret_key'] ?? null,
                ];

            case 'bank':
                return [
                    'enabled' => isPaymentMethodEnabled('bank', $userId),
                    'details' => $settings['bank_detail'] ?? null,
                ];

            case 'paytabs':
                return [
                    'enabled' => isPaymentMethodEnabled('paytabs', $userId),
                    'mode' => $settings['paytabs_mode'] ?? 'sandbox',
                    'profile_id' => $settings['paytabs_profile_id'] ?? null,
                    'server_key' => $settings['paytabs_server_key'] ?? null,
                    'region' => $settings['paytabs_region'] ?? 'ARE',
                ];

            case 'skrill':
                return [
                    'enabled' => isPaymentMethodEnabled('skrill', $userId),
                    'merchant_id' => $settings['skrill_merchant_id'] ?? null,
                    'secret_word' => $settings['skrill_secret_word'] ?? null,
                ];

            case 'coingate':
                return [
                    'enabled' => isPaymentMethodEnabled('coingate', $userId),
                    'mode' => $settings['coingate_mode'] ?? 'sandbox',
                    'api_token' => $settings['coingate_api_token'] ?? null,
                ];

            case 'payfast':
                return [
                    'enabled' => isPaymentMethodEnabled('payfast', $userId),
                    'mode' => $settings['payfast_mode'] ?? 'sandbox',
                    'merchant_id' => $settings['payfast_merchant_id'] ?? null,
                    'merchant_key' => $settings['payfast_merchant_key'] ?? null,
                    'passphrase' => $settings['payfast_passphrase'] ?? null,
                ];

            case 'tap':
                return [
                    'enabled' => isPaymentMethodEnabled('tap', $userId),
                    'secret_key' => $settings['tap_secret_key'] ?? null,
                ];

            case 'xendit':
                return [
                    'enabled' => isPaymentMethodEnabled('xendit', $userId),
                    'api_key' => $settings['xendit_api_key'] ?? null,
                ];

            case 'paytr':
                return [
                    'enabled' => isPaymentMethodEnabled('paytr', $userId),
                    'merchant_id' => $settings['paytr_merchant_id'] ?? null,
                    'merchant_key' => $settings['paytr_merchant_key'] ?? null,
                    'merchant_salt' => $settings['paytr_merchant_salt'] ?? null,
                ];

            case 'mollie':
                return [
                    'enabled' => isPaymentMethodEnabled('mollie', $userId),
                    'api_key' => $settings['mollie_api_key'] ?? null,
                ];

            case 'toyyibpay':
                return [
                    'enabled' => isPaymentMethodEnabled('toyyibpay', $userId),
                    'category_code' => $settings['toyyibpay_category_code'] ?? null,
                    'secret_key' => $settings['toyyibpay_secret_key'] ?? null,
                    'mode' => $settings['toyyibpay_mode'] ?? 'sandbox',
                ];

            case 'cashfree':
                return [
                    'enabled' => isPaymentMethodEnabled('cashfree', $userId),
                    'mode' => $settings['cashfree_mode'] ?? 'sandbox',
                    'public_key' => $settings['cashfree_public_key'] ?? null,
                    'secret_key' => $settings['cashfree_secret_key'] ?? null,
                ];

            case 'iyzipay':
                return [
                    'enabled' => isPaymentMethodEnabled('iyzipay', $userId),
                    'mode' => $settings['iyzipay_mode'] ?? 'sandbox',
                    'public_key' => $settings['iyzipay_public_key'] ?? null,
                    'secret_key' => $settings['iyzipay_secret_key'] ?? null,
                ];

            case 'benefit':
                return [
                    'enabled' => isPaymentMethodEnabled('benefit', $userId),
                    'mode' => $settings['benefit_mode'] ?? 'sandbox',
                    'public_key' => $settings['benefit_public_key'] ?? null,
                    'secret_key' => $settings['benefit_secret_key'] ?? null,
                ];

            case 'ozow':
                return [
                    'enabled' => isPaymentMethodEnabled('ozow', $userId),
                    'mode' => $settings['ozow_mode'] ?? 'sandbox',
                    'site_key' => $settings['ozow_site_key'] ?? null,
                    'private_key' => $settings['ozow_private_key'] ?? null,
                    'api_key' => $settings['ozow_api_key'] ?? null,
                ];

            case 'easebuzz':
                return [
                    'enabled' => isPaymentMethodEnabled('easebuzz', $userId),
                    'merchant_key' => $settings['easebuzz_merchant_key'] ?? null,
                    'salt_key' => $settings['easebuzz_salt_key'] ?? null,
                    'environment' => $settings['easebuzz_environment'] ?? 'test',
                ];

            case 'khalti':
                return [
                    'enabled' => isPaymentMethodEnabled('khalti', $userId),
                    'public_key' => $settings['khalti_public_key'] ?? null,
                    'secret_key' => $settings['khalti_secret_key'] ?? null,
                ];

            case 'authorizenet':
                return [
                    'enabled' => isPaymentMethodEnabled('authorizenet', $userId),
                    'mode' => $settings['authorizenet_mode'] ?? 'sandbox',
                    'merchant_id' => $settings['authorizenet_merchant_id'] ?? null,
                    'transaction_key' => $settings['authorizenet_transaction_key'] ?? null,
                    'supported_countries' => ['US', 'CA', 'GB', 'AU'],
                    'supported_currencies' => ['USD', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'NOK', 'PLN', 'SEK', 'AUD', 'NZD'],
                ];

            case 'fedapay':
                return [
                    'enabled' => isPaymentMethodEnabled('fedapay', $userId),
                    'mode' => $settings['fedapay_mode'] ?? 'sandbox',
                    'public_key' => $settings['fedapay_public_key'] ?? null,
                    'secret_key' => $settings['fedapay_secret_key'] ?? null,
                ];

            case 'payhere':
                return [
                    'enabled' => isPaymentMethodEnabled('payhere', $userId),
                    'mode' => $settings['payhere_mode'] ?? 'sandbox',
                    'merchant_id' => $settings['payhere_merchant_id'] ?? null,
                    'merchant_secret' => $settings['payhere_merchant_secret'] ?? null,
                    'app_id' => $settings['payhere_app_id'] ?? null,
                    'app_secret' => $settings['payhere_app_secret'] ?? null,
                ];

            case 'cinetpay':
                return [
                    'enabled' => isPaymentMethodEnabled('cinetpay', $userId),
                    'site_id' => $settings['cinetpay_site_id'] ?? null,
                    'api_key' => $settings['cinetpay_api_key'] ?? null,
                    'secret_key' => $settings['cinetpay_secret_key'] ?? null,
                ];

            case 'paymentwall':
                return [
                    'enabled' => isPaymentMethodEnabled('paymentwall', $userId),
                    'mode' => $settings['paymentwall_mode'] ?? 'sandbox',
                    'public_key' => $settings['paymentwall_public_key'] ?? null,
                    'private_key' => $settings['paymentwall_private_key'] ?? null,
                ];

            default:
                return [];
        }
    }
}

if (! function_exists('getEnabledPaymentMethods')) {
    /**
     * Get all enabled payment methods
     *
     * @param int|null $userId
     * @return array
     */
    function getEnabledPaymentMethods($userId = null)
    {
        $methods = ['stripe', 'paypal', 'razorpay', 'mercadopago', 'paystack', 'flutterwave', 'bank', 'paytabs', 'skrill', 'coingate', 'payfast', 'tap', 'xendit', 'paytr', 'mollie', 'toyyibpay', 'cashfree', 'iyzipay', 'benefit', 'ozow', 'easebuzz', 'khalti', 'authorizenet', 'fedapay', 'payhere', 'cinetpay', 'paymentwall'];
        $enabled = [];

        foreach ($methods as $method) {
            if (isPaymentMethodEnabled($method, $userId)) {
                $enabled[$method] = getPaymentMethodConfig($method, $userId);
            }
        }

        return $enabled;
    }
}

if (! function_exists('validatePaymentMethodConfig')) {
    /**
     * Validate payment method configuration
     *
     * @param string $method
     * @param array $config
     * @return array [valid => bool, errors => array]
     */
    function validatePaymentMethodConfig($method, $config)
    {
        $errors = [];

        switch ($method) {
            case 'stripe':
                if (empty($config['key'])) {
                    $errors[] = 'Stripe publishable key is required';
                }
                if (empty($config['secret'])) {
                    $errors[] = 'Stripe secret key is required';
                }
                break;

            case 'paypal':
                if (empty($config['client_id'])) {
                    $errors[] = 'PayPal client ID is required';
                }
                if (empty($config['secret'])) {
                    $errors[] = 'PayPal secret key is required';
                }
                break;

            case 'razorpay':
                if (empty($config['key'])) {
                    $errors[] = 'Razorpay key ID is required';
                }
                if (empty($config['secret'])) {
                    $errors[] = 'Razorpay secret key is required';
                }
                break;

            case 'mercadopago':
                if (empty($config['access_token'])) {
                    $errors[] = 'MercadoPago access token is required';
                }
                break;

            case 'bank':
                if (empty($config['details'])) {
                    $errors[] = 'Bank details are required';
                }
                break;

            case 'paytabs':
                if (empty($config['server_key'])) {
                    $errors[] = 'PayTabs server key is required';
                }
                if (empty($config['profile_id'])) {
                    $errors[] = 'PayTabs profile id is required';
                }
                if (empty($config['region'])) {
                    $errors[] = 'PayTabs region is required';
                }
                break;

            case 'skrill':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'Skrill merchant ID is required';
                }
                if (empty($config['secret_word'])) {
                    $errors[] = 'Skrill secret word is required';
                }
                break;

            case 'coingate':
                if (empty($config['api_token'])) {
                    $errors[] = 'CoinGate API token is required';
                }
                break;

            case 'payfast':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'Payfast merchant ID is required';
                }
                if (empty($config['merchant_key'])) {
                    $errors[] = 'Payfast merchant key is required';
                }
                break;

            case 'tap':
                if (empty($config['secret_key'])) {
                    $errors[] = 'Tap secret key is required';
                }
                break;

            case 'xendit':
                if (empty($config['api_key'])) {
                    $errors[] = 'Xendit api key is required';
                }
                break;

            case 'paytr':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'PayTR merchant ID is required';
                }
                if (empty($config['merchant_key'])) {
                    $errors[] = 'PayTR merchant key is required';
                }
                if (empty($config['merchant_salt'])) {
                    $errors[] = 'PayTR merchant salt is required';
                }
                break;

            case 'mollie':
                if (empty($config['api_key'])) {
                    $errors[] = 'Mollie API key is required';
                }
                break;

            case 'toyyibpay':
                if (empty($config['category_code'])) {
                    $errors[] = 'toyyibPay category code is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'toyyibPay secret key is required';
                }
                break;

            case 'cashfree':
                if (empty($config['public_key'])) {
                    $errors[] = 'Cashfree App ID is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'Cashfree Secret Key is required';
                }
                break;

            case 'iyzipay':
                if (empty($config['public_key'])) {
                    $errors[] = 'Iyzipay API key is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'Iyzipay secret key is required';
                }
                break;

            case 'benefit':
                if (empty($config['public_key'])) {
                    $errors[] = 'Benefit API key is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'Benefit secret key is required';
                }
                break;

            case 'ozow':
                if (empty($config['site_key'])) {
                    $errors[] = 'Ozow site key is required';
                }
                if (empty($config['private_key'])) {
                    $errors[] = 'Ozow private key is required';
                }
                break;

            case 'easebuzz':
                if (empty($config['merchant_key'])) {
                    $errors[] = 'Easebuzz merchant key is required';
                }
                if (empty($config['salt_key'])) {
                    $errors[] = 'Easebuzz salt key is required';
                }
                break;

            case 'khalti':
                if (empty($config['public_key'])) {
                    $errors[] = 'Khalti public key is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'Khalti secret key is required';
                }
                break;

            case 'authorizenet':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'AuthorizeNet merchant ID is required';
                }
                if (empty($config['transaction_key'])) {
                    $errors[] = 'AuthorizeNet transaction key is required';
                }
                break;

            case 'fedapay':
                if (empty($config['public_key'])) {
                    $errors[] = 'FedaPay public key is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'FedaPay secret key is required';
                }
                break;

            case 'payhere':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'PayHere merchant ID is required';
                }
                if (empty($config['merchant_secret'])) {
                    $errors[] = 'PayHere merchant secret is required';
                }
                break;

            case 'cinetpay':
                if (empty($config['site_id'])) {
                    $errors[] = 'CinetPay site ID is required';
                }
                if (empty($config['api_key'])) {
                    $errors[] = 'CinetPay API key is required';
                }
                break;

            case 'paiement':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'Paiement Pro merchant ID is required';
                }
                break;

            case 'nepalste':
                if (empty($config['public_key'])) {
                    $errors[] = 'Nepalste public key is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'Nepalste secret key is required';
                }
                break;

            case 'yookassa':
                if (empty($config['shop_id'])) {
                    $errors[] = 'YooKassa shop ID is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'YooKassa secret key is required';
                }
                break;

            case 'midtrans':
                if (empty($config['secret_key'])) {
                    $errors[] = 'Midtrans secret key is required';
                }
                break;

            case 'aamarpay':
                if (empty($config['store_id'])) {
                    $errors[] = 'Aamarpay store ID is required';
                }
                if (empty($config['signature'])) {
                    $errors[] = 'Aamarpay signature is required';
                }
                break;

            case 'paymentwall':
                if (empty($config['public_key'])) {
                    $errors[] = 'PaymentWall public key is required';
                }
                if (empty($config['private_key'])) {
                    $errors[] = 'PaymentWall private key is required';
                }
                break;

            case 'sspay':
                if (empty($config['secret_key'])) {
                    $errors[] = 'SSPay secret key is required';
                }
                break;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

if (! function_exists('calculatePlanPricing')) {
    function calculatePlanPricing($plan, $couponCode = null, $billingCycle = 'monthly')
    {
        $originalPrice = $plan->getPriceForCycle($billingCycle);
        $discountAmount = 0;
        $finalPrice = $originalPrice;
        $couponId = null;

        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)
                ->where('status', 1)
                ->first();

            if ($coupon) {
                if ($coupon->type === 'percentage') {
                    $discountAmount = ($originalPrice * $coupon->discount_amount) / 100;
                } else {
                    $discountAmount = min($coupon->discount_amount, $originalPrice);
                }
                $finalPrice = max(0, $originalPrice - $discountAmount);
                $couponId = $coupon->id;
            }
        }

        return [
            'original_price' => $originalPrice,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'coupon_id' => $couponId
        ];
    }
}

if (! function_exists('createPlanOrder')) {
    function createPlanOrder($data)
    {
        $plan = Plan::findOrFail($data['plan_id']);
        $pricing = calculatePlanPricing($plan, $data['coupon_code'] ?? null, $data['billing_cycle'] ?? 'monthly');

        return PlanOrder::create([
            'user_id' => $data['user_id'],
            'plan_id' => $plan->id,
            'coupon_id' => $pricing['coupon_id'],
            'billing_cycle' => $data['billing_cycle'],
            'payment_method' => $data['payment_method'],
            'coupon_code' => $data['coupon_code'] ?? null,
            'original_price' => $pricing['original_price'],
            'discount_amount' => $pricing['discount_amount'],
            'final_price' => $pricing['final_price'],
            'payment_id' => $data['payment_id'],
            'status' => $data['status'] ?? 'pending',
            'ordered_at' => now(),
        ]);
    }
}

if (! function_exists('assignPlanToUser')) {
    function assignPlanToUser($user, $plan, $billingCycle)
    {
        $expiresAt = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();

        \Log::info('Assigning plan ' . $plan->id . ' to user ' . $user->id . ' with billing cycle ' . $billingCycle);

        $updated = $user->update([
            'plan_id' => $plan->id,
            'plan_expire_date' => $expiresAt,
            'plan_is_active' => 1,
        ]);

        \Log::info('Plan assignment result: ' . ($updated ? 'success' : 'failed'));
    }
}

if (! function_exists('processPaymentSuccess')) {
    function processPaymentSuccess($data)
    {
        $plan = Plan::findOrFail($data['plan_id']);
        $user = User::findOrFail($data['user_id']);

        $planOrder = createPlanOrder(array_merge($data, ['status' => 'approved']));
        assignPlanToUser($user, $plan, $data['billing_cycle']);

        // Verify the plan was assigned
        $user->refresh();

        // Create referral record if user was referred
        \App\Http\Controllers\ReferralController::createReferralRecord($user);

        return $planOrder;
    }
}

if (! function_exists('getPaymentGatewaySettings')) {
    function getPaymentGatewaySettings()
    {
        $superAdminId = User::where('type', 'superadmin')->first()?->id;

        return [
            'payment_settings' => PaymentSetting::getUserSettings($superAdminId),
            'general_settings' => Setting::getUserSettings($superAdminId),
            'super_admin_id' => $superAdminId
        ];
    }
}

if (! function_exists('validatePaymentRequest')) {
    function validatePaymentRequest($request, $additionalRules = [])
    {
        $baseRules = [
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'coupon_code' => 'nullable|string',
        ];

        return $request->validate(array_merge($baseRules, $additionalRules));
    }
}

if (! function_exists('handlePaymentError')) {
    function handlePaymentError($e, $method = 'payment')
    {
        return back()->withErrors(['error' => __('Payment processing failed: :message', ['message' => $e->getMessage()])]);
    }
}

if (! function_exists('defaultSettings')) {
    /**
     * Get default settings for System, Brand, Storage, and Currency configurations
     *
     * @return array
     */
    function defaultSettings()
    {
        return [
            // System Settings
            'defaultLanguage' => 'en',
            'dateFormat' => 'Y-m-d',
            'timeFormat' => 'H:i',
            'calendarStartDay' => 'sunday',
            'defaultTimezone' => 'UTC',
            'emailVerification' => false,
            'landingPageEnabled' => true,
            'registrationEnabled' => true,

            // Brand Settings
            'logoDark' => 'logo/logo-dark.png',
            'logoLight' => 'logo/logo-light.png',
            'favicon' => 'logo/favicon.png',
            'titleText' => 'Sales SaaS',
            'footerText' => '© 2026 Sales SaaS. All rights reserved.',
            'themeColor' => 'green',
            'customColor' => '#10b77f',
            'sidebarVariant' => 'inset',
            'sidebarStyle' => 'plain',
            'layoutDirection' => 'left',
            'themeMode' => 'light',

            // Storage Settings
            'storage_type' => 'local',
            'storage_file_types' => 'jpg,png,webp,gif,pdf,doc,docx,txt,csv',
            'storage_max_upload_size' => '2048',
            'aws_access_key_id' => '',
            'aws_secret_access_key' => '',
            'aws_default_region' => 'us-east-1',
            'aws_bucket' => '',
            'aws_url' => '',
            'aws_endpoint' => '',
            'wasabi_access_key' => '',
            'wasabi_secret_key' => '',
            'wasabi_region' => 'us-east-1',
            'wasabi_bucket' => '',
            'wasabi_url' => '',
            'wasabi_root' => '',

            // Currency Settings
            'decimalFormat' => '2',
            'defaultCurrency' => 'USD',
            'decimalSeparator' => '.',
            'thousandsSeparator' => ',',
            'floatNumber' => true,
            'currencySymbolSpace' => false,
            'currencySymbolPosition' => 'before',

            // Cookie Settings
            'enableLogging' => false,
            'strictlyNecessaryCookies' => true,
            'cookieTitle' => 'Cookie Consent',
            'strictlyCookieTitle' => 'Strictly Necessary Cookies',
            'cookieDescription' => 'We use cookies to enhance your browsing experience and provide personalized content.',
            'strictlyCookieDescription' => 'These cookies are essential for the website to function properly.',
            'contactUsDescription' => 'If you have any questions about our cookie policy, please contact us.',
            'contactUsUrl' => 'https://example.com/contact',
        ];
    }
}

if (! function_exists('createDefaultSettings')) {
    /**
     * Create default settings for a user
     *
     * @param int $userId
     * @return void
     */
    function createDefaultSettings($userId)
    {
        $defaults = defaultSettings();
        $settingsData = [];

        foreach ($defaults as $key => $value) {
            $settingsData[] = [
                'user_id' => $userId,
                'key' => $key,
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string)$value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Setting::insert($settingsData);
    }
}

if (! function_exists('copySettingsFromSuperAdmin')) {
    /**
     * Copy system and brand settings from superadmin to company user
     *
     * @param int $companyUserId
     * @return void
     */
    function copySettingsFromSuperAdmin($companyUserId)
    {
        $superAdmin = User::where('type', 'superadmin')->first();
        if (!$superAdmin) {
            createDefaultSettings($companyUserId);
            return;
        }

        // Settings to copy from superadmin (system and brand settings only)
        $settingsToCopy = [
            'defaultLanguage',
            'dateFormat',
            'timeFormat',
            'calendarStartDay',
            'defaultTimezone',
            'emailVerification',
            'landingPageEnabled',
            'registrationEnabled',
            'logoDark',
            'logoLight',
            'favicon',
            'titleText',
            'footerText',
            'themeColor',
            'customColor',
            'sidebarVariant',
            'sidebarStyle',
            'layoutDirection',
            'themeMode',
            'enableLogging',
            'strictlyNecessaryCookies',
            'cookieTitle',
            'strictlyCookieTitle',
            'cookieDescription',
            'strictlyCookieDescription',
            'contactUsDescription',
            'contactUsUrl'
        ];

        // Currency settings - use defaults for company (not copied from superadmin)
        $currencyDefaults = [
            'decimalFormat' => '2',
            'defaultCurrency' => 'USD',
            'decimalSeparator' => '.',
            'thousandsSeparator' => ',',
            'floatNumber' => '1',
            'currencySymbolSpace' => '0',
            'currencySymbolPosition' => 'before'
        ];

        $superAdminSettings = Setting::where('user_id', $superAdmin->id)
            ->whereIn('key', $settingsToCopy)
            ->get();

        $settingsData = [];

        // Only copy existing superadmin settings
        foreach ($superAdminSettings as $setting) {
            $settingsData[] = [
                'user_id' => $companyUserId,
                'key' => $setting->key,
                'value' => $setting->value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Add currency defaults for company
        foreach ($currencyDefaults as $key => $value) {
            $settingsData[] = [
                'user_id' => $companyUserId,
                'key' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Setting::insertOrIgnore($settingsData);
    }
}

if (! function_exists('getCompanyName')) {
    function getCompanyName()
    {
        $company = User::find(createdBy());
        if ($company) {
            return $company->name;
        } else {
            return 'Sales';
        }
    }
}

if (! function_exists('getCompanyLogo')) {
    function getCompanyLogo()
    {
        $company = getSetting('logoDark', 'logo/logo-dark.png', createdBy());
        if ($company) {
            return $company;
        } else {
            return 'logo/logo-dark.png';
        }
    }
}

if (! function_exists('createdBy')) {
    function createdBy()
    {
        if (Auth::user()->type == 'superadmin') {
            return Auth::user()->id;
        } else if (Auth::user()->type == 'company') {
            return Auth::user()->id;
        } else {
            return  Auth::user()->created_by;
        }
    }
}

if (! function_exists('IsDemo')) {
    function IsDemo()
    {
        if (config('app.is_demo')) {
            return true;
        } else {
            return false;
        }
    }
}

if (! function_exists('createDefaultNotificationTemplates')) {
    /**
     * Create default notification templates for a new company
     *
     * @param int $companyId
     * @return void
     */
    function createDefaultNotificationTemplates($companyId)
    {
        $languages = json_decode(file_get_contents(resource_path('lang/language.json')), true);
        $langCodes = collect($languages)->pluck('code')->toArray();

        $templates = \App\Models\NotificationTemplate::get();

        foreach ($templates as $template) {
            foreach ($langCodes as $langCode) {
                $existingContent = \App\Models\NotificationTemplateLang::where('parent_id', $template->id)
                    ->where('lang', $langCode)
                    ->where('created_by', $companyId)
                    ->first();

                if ($existingContent) {
                    continue;
                }

                $globalContent = \App\Models\NotificationTemplateLang::where('parent_id', $template->id)
                    ->where('lang', $langCode)
                    ->where('created_by', 1)
                    ->first();

                if ($globalContent) {
                    \App\Models\NotificationTemplateLang::create([
                        'parent_id' => $template->id,
                        'lang' => $langCode,
                        'title' => $globalContent->title,
                        'content' => $globalContent->content,
                        'created_by' => $companyId
                    ]);
                }
            }
        }
    }
}

if (! function_exists('isEmailTemplateEnabled')) {
    /**
     * Check if an email template is enabled for a user
     *
     * @param string $templateName
     * @param int|null $userId
     * @return bool
     */
    function isEmailTemplateEnabled($templateName, $userId = null)
    {
        if (is_null($userId)) {
            $userId = createdBy();
        }

        $template = \App\Models\EmailTemplate::where('name', $templateName)->first();
        if (!$template) {
            return false;
        }

        $userTemplate = \App\Models\UserEmailTemplate::where('user_id', $userId)
            ->where('template_id', $template->id)
            ->first();

        return $userTemplate ? $userTemplate->is_active : false;
    }
}

if (! function_exists('getTwilioConfig')) {
    function getTwilioConfig()
    {
        return [
            'twilio_sid' => getSetting('twilio_sid', ''),
            'twilio_token' => getSetting('twilio_token', ''),
            'twilio_from' => getSetting('twilio_from', '')
        ];
    }
}

if (!function_exists('parseBrowserData')) {
    function parseBrowserData(string $userAgent): array
    {
        $browser = 'Unknown';
        $os = 'Unknown';
        $deviceType = 'desktop';

        // Browser detection
        if (preg_match('/Chrome\/([0-9.]+)/', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent) && !preg_match('/Chrome/', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge\/([0-9.]+)/', $userAgent)) {
            $browser = 'Edge';
        }

        // OS detection
        if (preg_match('/Windows NT/', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X/', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/', $userAgent)) {
            $os = 'Android';
            $deviceType = 'mobile';
        } elseif (preg_match('/iPhone|iPad/', $userAgent)) {
            $os = 'iOS';
            $deviceType = preg_match('/iPad/', $userAgent) ? 'tablet' : 'mobile';
        }

        return [
            'browser_name' => $browser,
            'os_name' => $os,
            'browser_language' => 'en',
            'device_type' => $deviceType,
        ];
    }
}

if (!function_exists('getDemoCalendarData')) {
    function getDemoCalendarData()
    {
        $events = [];

        $rangeStart = Carbon::create(2025, 12, 1);
        $rangeEnd   = Carbon::create(2026, 12, 31);

        // Fixed templates (no randomness)
        $demoEvents = [
            ['type' => 'meeting', 'title' => 'Team Standup',        'duration' => 30],
            ['type' => 'meeting', 'title' => 'Client Presentation', 'duration' => 60],
            ['type' => 'meeting', 'title' => 'Product Review',      'duration' => 45],
            ['type' => 'call',    'title' => 'Sales Call',          'duration' => 30],
            ['type' => 'call',    'title' => 'Follow-up Call',      'duration' => 20],
            ['type' => 'call',    'title' => 'Support Call',        'duration' => 25],
            ['type' => 'task',    'title' => 'Prepare Report',      'duration' => 0],
            ['type' => 'task',    'title' => 'Review Documents',    'duration' => 0],
            ['type' => 'task',    'title' => 'Update Website',      'duration' => 0],
        ];

        // Predefined static values
        $statusesTask    = ['pending', 'in_progress', 'completed'];
        $statusesEvent   = ['scheduled', 'completed', 'cancelled'];
        $timeSlots       = ['09:00', '10:00', '11:30', '14:00', '15:30', '16:30'];
        $locations       = ['Conference Room A', 'Zoom', 'Office'];

        $eventId = 1;
        $currentDate = $rangeStart->copy();

        while ($currentDate->lte($rangeEnd)) {

            // Rotate event templates deterministically
            $template = $demoEvents[$eventId % count($demoEvents)];
            $type     = $template['type'];

            if ($type === 'task') {
                $events[] = [
                    'id'              => "task-{$eventId}",
                    'title'           => $template['title'],
                    'start'           => $currentDate->format('Y-m-d'),
                    'type'            => 'task',
                    'backgroundColor' => '#f59e0b',
                    'borderColor'     => '#d97706',
                    'task_id'         => $eventId,
                    'project_id'      => ($eventId % 5) + 1,
                    'description'     => 'Demo task description',
                    'status'          => $statusesTask[$eventId % count($statusesTask)],
                    'parent_name'     => 'Demo Project ' . (($eventId % 3) + 1),
                ];
            } else {
                $time = $timeSlots[$eventId % count($timeSlots)];

                $startDateTime = Carbon::parse(
                    $currentDate->format('Y-m-d') . ' ' . $time
                );

                $endDateTime = $startDateTime->copy()->addMinutes($template['duration']);

                $event = [
                    'id'              => "{$type}-{$eventId}",
                    'title'           => $template['title'],
                    'start'           => $startDateTime->format('Y-m-d H:i:s'),
                    'end'             => $endDateTime->format('Y-m-d H:i:s'),
                    'type'            => $type,
                    'description'     => "Demo {$type} description",
                    'status'          => $statusesEvent[$eventId % count($statusesEvent)],
                    'parent_name'     => $type === 'meeting'
                        ? 'Demo Lead ' . (($eventId % 5) + 1)
                        : 'Demo Contact ' . (($eventId % 5) + 1),
                    'startDateTime'   => $startDateTime->format('H:i:s'),
                    'endDateTime'     => $endDateTime->format('H:i:s'),
                ];

                if ($type === 'meeting') {
                    $event['backgroundColor'] = '#3b82f6';
                    $event['borderColor']     = '#2563eb';
                    $event['meeting_id']      = $eventId;
                    $event['location']        = $locations[$eventId % count($locations)];
                } else {
                    $event['backgroundColor'] = '#10b77f';
                    $event['borderColor']     = '#059669';
                    $event['call_id']         = $eventId;
                }

                $events[] = $event;
            }

            $eventId++;

            // Move forward in a fixed pattern (every 3 days)
            $currentDate->addDays(3);
        }

        return $events;
    }
}

if (!function_exists('isDisabledDeleteRole')) {
    function isDisabledDeleteRole()
    {
        return ['sales-manager'];
    }
}

if (!function_exists('isDisabledEditRole')) {
    function isDisabledEditRole()
    {
        return ['sales-manager'];
    }
}

if (!function_exists('getSuperAdminSettings')) {
    function getSuperAdminSettings()
    {
        $superAdmin = User::where('type', 'superadmin')->first();
        if ($superAdmin) {
            $superAdminSettings = Setting::where('user_id', $superAdmin->id)
                ->pluck('value', 'key')
                ->toArray();
            return $superAdminSettings;
        }
    }
}

if (! function_exists('upload_file')) {
    function upload_file($request, $key_name, $name, $path, $custom_validation = [])
    {
        try {
            $storage_settings = getSuperAdminSettings();

            if (isset($storage_settings['storage_type'])) {
                if ($storage_settings['storage_type'] == 'wasabi') {
                    config(
                        [
                            'filesystems.disks.wasabi.driver' => 's3',
                            'filesystems.disks.wasabi.key' => $storage_settings['wasabi_access_key'],
                            'filesystems.disks.wasabi.secret' => $storage_settings['wasabi_secret_key'],
                            'filesystems.disks.wasabi.region' => $storage_settings['wasabi_region'] ?? 'us-east-1',
                            'filesystems.disks.wasabi.bucket' => $storage_settings['wasabi_bucket'],
                            'filesystems.disks.wasabi.endpoint' => $storage_settings['wasabi_url'],
                            'filesystems.disks.wasabi.root' => $storage_settings['wasabi_root'],
                            'filesystems.disks.use_path_style_endpoint' => false,
                            'filesystems.disks.wasabi.visibility' => 'public',
                        ]
                    );
                    $max_size = ! empty($storage_settings['storage_max_upload_size']) ? $storage_settings['storage_max_upload_size'] : '2048';
                    $mimes = ! empty($storage_settings['storage_file_types']) ? $storage_settings['storage_file_types'] : 'jpeg,jpg,png,svg,zip,txt,gif,docx';
                } elseif ($storage_settings['storage_type'] == 'aws_s3') {
                    config(
                        [
                            'filesystems.disks.s3.driver' => 's3',
                            'filesystems.disks.s3.key' => $storage_settings['aws_access_key_id'],
                            'filesystems.disks.s3.secret' => $storage_settings['aws_secret_access_key'],
                            'filesystems.disks.s3.region' => $storage_settings['aws_default_region'] ?? 'us-east-1',
                            'filesystems.disks.s3.bucket' => $storage_settings['aws_bucket'],
                            'filesystems.disks.s3.url' => $storage_settings['aws_url'],
                            'filesystems.disks.s3.endpoint' => $storage_settings['aws_endpoint'],
                            'filesystems.disks.s3.use_path_style_endpoint' => false,
                            'filesystems.disks.s3.visibility' => 'public',
                        ]
                    );
                    $max_size = ! empty($storage_settings['storage_max_upload_size']) ? $storage_settings['storage_max_upload_size'] : '2048';
                    $mimes = ! empty($storage_settings['storage_file_types']) ? $storage_settings['storage_file_types'] : 'jpeg,jpg,png,svg,zip,txt,gif,docx';
                } else {
                    $max_size = ! empty($storage_settings['storage_max_upload_size']) ? $storage_settings['storage_max_upload_size'] : '2048';
                    $mimes = ! empty($storage_settings['storage_file_types']) ? $storage_settings['storage_file_types'] : 'jpeg,jpg,png,svg,zip,txt,gif,docx';
                }
                $file = $request->$key_name;

                $extension = strtolower($file->getClientOriginalExtension());
                $allowed_extensions = explode(',', $mimes);

                if (empty($extension) || ! in_array($extension, $allowed_extensions)) {
                    return [
                        'status' => false,
                        'msg' => 'The ' . $key_name . ' must be a file of type: ' . implode(', ', $allowed_extensions) . '.',
                    ];
                }

                if (count($custom_validation) > 0) {
                    $validation = $custom_validation;
                } else {
                    $validation = [
                        'mimes:' . $mimes,
                        'max:' . $max_size,
                    ];
                }
                $validator = Validator::make($request->all(), [
                    $key_name => $validation,
                ]);
                if ($validator->fails()) {
                    $res = [
                        'status' => false,
                        'msg' => $validator->messages()->first(),
                    ];

                    return $res;
                } else {
                    $storageType = $settings['storage_type'] ?? 'local';
                    $diskName = match ($storageType) {
                        'local' => 'public',
                        'aws_s3' => 's3',
                        'wasabi' => 'wasabi',
                        default => 'public'
                    };

                    // Store file directly to storage
                    $file->storeAs('media/' . $path, $name, $diskName);

                    $res = [
                        'status' => true,
                        'msg' => 'success',
                        'url' => $path . '/' . $name,
                    ];

                    return $res;
                }
            } else {
                $res = [
                    'status' => false,
                    'msg' => __('Not set configurations'),
                ];

                return $res;
            }
        } catch (\Exception $e) {
            $res = [
                'status' => false,
                'msg' => $e->getMessage(),
            ];

            return $res;
        }
    }
}

if (!function_exists('check_file')) {
    function check_file($path)
    {
        try {
            if (empty($path)) {
                return false;
            }
            $storage_settings = getSuperAdminSettings();
            if (!isset($storage_settings['storage_type'])) {
                return false;
            }

            $storageType = $storage_settings['storage_type'];

            // Handle local storage
            if ($storageType === 'local' || $storageType === null) {
                // Check in public storage path
                $publicPath = storage_path('app/public/media/' . ltrim($path, '/'));
                if (file_exists($publicPath)) {
                    return true;
                }

                // Check in base path as fallback
                $basePath = base_path($path);

                return file_exists($basePath);
            }

            // Handle AWS S3 storage
            if ($storageType === 'aws_s3') {
                if (
                    empty($storage_settings['aws_access_key_id']) ||
                    empty($storage_settings['aws_secret_access_key']) ||
                    empty($storage_settings['aws_default_region']) ||
                    empty($storage_settings['aws_bucket'])
                ) {
                    return false;
                }

                config([
                    'filesystems.disks.s3.key' => $storage_settings['aws_access_key_id'],
                    'filesystems.disks.s3.secret' => $storage_settings['aws_secret_access_key'],
                    'filesystems.disks.s3.region' => $storage_settings['aws_default_region'] ?? 'us-east-1',
                    'filesystems.disks.s3.bucket' => $storage_settings['aws_bucket'],
                ]);

                // Normalize path for S3
                $s3Path = 'media/' . ltrim($path, '/');

                return Storage::disk('s3')->exists($s3Path);
            }

            // Handle Wasabi storage
            if ($storageType === 'wasabi') {
                if (
                    empty($storage_settings['wasabi_access_key']) ||
                    empty($storage_settings['wasabi_secret_key']) ||
                    empty($storage_settings['wasabi_region']) ||
                    empty($storage_settings['wasabi_bucket']) ||
                    empty($storage_settings['wasabi_url']) ||
                    empty($storage_settings['wasabi_root'])
                ) {
                    return false;
                }

                config([
                    'filesystems.disks.wasabi.key' => $storage_settings['wasabi_access_key'],
                    'filesystems.disks.wasabi.secret' => $storage_settings['wasabi_secret_key'],
                    'filesystems.disks.wasabi.region' => $storage_settings['wasabi_region'] ?? 'us-east-1',
                    'filesystems.disks.wasabi.bucket' => $storage_settings['wasabi_bucket'],
                    'filesystems.disks.wasabi.endpoint' => $storage_settings['wasabi_url'] ?? null,
                    'filesystems.disks.wasabi.root' => $storage_settings['wasabi_root'] ?? '',
                ]);

                // Normalize path for Wasabi
                $wasabiPath = 'media/' . ltrim($path, '/');

                return Storage::disk('wasabi')->exists($wasabiPath);
            }

            // Unknown storage type
            return false;
        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('check_file error: ' . $e->getMessage(), [
                'path' => $path,
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}

if (!function_exists('get_file')) {
    function get_file($path)
    {
        try {
            // Return empty string if path is empty
            if (empty($path)) {
                return '';
            }

            $storage_settings = getSuperAdminSettings();

            // Check if storage settings exist, fallback to local
            if (!isset($storage_settings['storage_type'])) {
                return url('storage/media/' . ltrim($path, '/'));
            }

            $storageType = $storage_settings['storage_type'];

            // Handle AWS S3 storage
            if ($storageType === 'aws_s3' || $storageType === 's3') {
                if (
                    empty($storage_settings['s3_key']) ||
                    empty($storage_settings['s3_secret']) ||
                    empty($storage_settings['s3_region']) ||
                    empty($storage_settings['s3_bucket'])
                ) {
                    return url('storage/media/' . ltrim($path, '/'));
                }

                config([
                    'filesystems.disks.s3.key' => $storage_settings['s3_key'],
                    'filesystems.disks.s3.secret' => $storage_settings['s3_secret'],
                    'filesystems.disks.s3.region' => $storage_settings['s3_region'],
                    'filesystems.disks.s3.bucket' => $storage_settings['s3_bucket'],
                ]);

                // Normalize path for S3
                $s3Path = 'media/' . ltrim($path, '/');
                return Storage::disk('s3')->url($s3Path);
            }

            // Handle Wasabi storage
            if ($storageType === 'wasabi') {
                if (
                    empty($storage_settings['wasabi_key']) ||
                    empty($storage_settings['wasabi_secret']) ||
                    empty($storage_settings['wasabi_region']) ||
                    empty($storage_settings['wasabi_bucket']) ||
                    empty($storage_settings['wasabi_root']) ||
                    empty($storage_settings['wasabi_url'])
                ) {
                    return url('storage/media/' . ltrim($path, '/'));
                }

                config([
                    'filesystems.disks.wasabi.key' => $storage_settings['wasabi_key'],
                    'filesystems.disks.wasabi.secret' => $storage_settings['wasabi_secret'],
                    'filesystems.disks.wasabi.region' => $storage_settings['wasabi_region'],
                    'filesystems.disks.wasabi.bucket' => $storage_settings['wasabi_bucket'],
                    'filesystems.disks.wasabi.root' => $storage_settings['wasabi_root'],
                    'filesystems.disks.wasabi.endpoint' => $storage_settings['wasabi_url'],
                ]);

                // Normalize path for Wasabi
                $wasabiPath = 'media/' . ltrim($path, '/');
                return Storage::disk('wasabi')->url($wasabiPath);
            }

            // Handle local storage (default)
            return url('storage/media/' . ltrim($path, '/'));
        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('get_file error: ' . $e->getMessage(), [
                'path' => $path,
                'trace' => $e->getTraceAsString()
            ]);
            // Return asset path as fallback
            return asset($path);
        }
    }
}

if (!function_exists('delete_file')) {
    function delete_file($path)
    {
        try {
            // Return false if path is empty
            if (empty($path)) {
                return false;
            }

            // Check if file exists first
            if (!check_file($path)) {
                return false;
            }

            $storage_settings = getSuperAdminSettings();

            // Check if storage settings exist
            if (!isset($storage_settings['storage_type'])) {
                return false;
            }

            $storageType = $storage_settings['storage_type'];

            // Handle local storage
            if ($storageType === 'local' || $storageType === null) {
                $publicPath = storage_path('app/public/media/' . ltrim($path, '/'));
                if (file_exists($publicPath)) {
                    return unlink($publicPath);
                }
                return false;
            }

            // Handle AWS S3 storage
            if ($storageType === 'aws_s3' || $storageType === 's3') {
                if (
                    empty($storage_settings['s3_key']) ||
                    empty($storage_settings['s3_secret']) ||
                    empty($storage_settings['s3_region']) ||
                    empty($storage_settings['s3_bucket'])
                ) {
                    return false;
                }

                config([
                    'filesystems.disks.s3.key' => $storage_settings['s3_key'],
                    'filesystems.disks.s3.secret' => $storage_settings['s3_secret'],
                    'filesystems.disks.s3.region' => $storage_settings['s3_region'],
                    'filesystems.disks.s3.bucket' => $storage_settings['s3_bucket'],
                ]);

                // Normalize path for S3
                $s3Path = 'media/' . ltrim($path, '/');
                return Storage::disk('s3')->delete($s3Path);
            }

            // Handle Wasabi storage
            if ($storageType === 'wasabi') {
                if (
                    empty($storage_settings['wasabi_key']) ||
                    empty($storage_settings['wasabi_secret']) ||
                    empty($storage_settings['wasabi_region']) ||
                    empty($storage_settings['wasabi_bucket']) ||
                    empty($storage_settings['wasabi_root']) ||
                    empty($storage_settings['wasabi_url'])
                ) {
                    return false;
                }

                config([
                    'filesystems.disks.wasabi.key' => $storage_settings['wasabi_key'],
                    'filesystems.disks.wasabi.secret' => $storage_settings['wasabi_secret'],
                    'filesystems.disks.wasabi.region' => $storage_settings['wasabi_region'],
                    'filesystems.disks.wasabi.bucket' => $storage_settings['wasabi_bucket'],
                    'filesystems.disks.wasabi.root' => $storage_settings['wasabi_root'],
                    'filesystems.disks.wasabi.endpoint' => $storage_settings['wasabi_url'],
                ]);

                // Normalize path for Wasabi
                $wasabiPath = 'media/' . ltrim($path, '/');
                return Storage::disk('wasabi')->delete($wasabiPath);
            }

            // Unknown storage type
            return false;
        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('delete_file error: ' . $e->getMessage(), [
                'path' => $path,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}

if (!function_exists('getImageUrlPrefix')) {
    function getImageUrlPrefix(): string
    {
        $storageType = getSetting('storage_type', 'local');

        switch ($storageType) {
            case 's3':
            case 'aws_s3':
                $url = getSetting('aws_url');
                if ($url) {
                    return rtrim($url, '/');
                }
                $endpoint = getSetting('aws_endpoint');
                if ($endpoint) {
                    return rtrim($endpoint, '/');
                }
                $bucket = getSetting('aws_bucket');
                $region = getSetting('aws_default_region', 'us-east-1');
                return "https://{$bucket}.s3.{$region}.amazonaws.com";

            case 'wasabi':
                $url = getSetting('wasabi_url');
                $bucket = getSetting('wasabi_bucket');
                if ($url) {
                    // Check if URL already includes bucket name
                    if ($bucket && !str_contains($url, $bucket)) {
                        return rtrim($url, '/') . '/' . $bucket;
                    }
                    return rtrim($url, '/');
                }
                $region = getSetting('wasabi_region', 'us-east-1');
                return "https://s3.{$region}.wasabisys.com/{$bucket}";

            case 'local':
            default:
                return url('');
        }
    }

    if (!function_exists('getSuperAdminCompanyId')) {
        /**
         * Get the super admin company ID (equivalent of getSuperAdminStoreId in old project)
         * In the new CRM, the super admin's own user ID serves as the "store ID" for shared products
         *
         * @return int
         */
        function getSuperAdminCompanyId()
        {
            return \App\Models\User::where('type', 'superadmin')
                ->orWhere('type', 'super admin')
                ->value('id') ?? 1;
        }
    }

    if (!function_exists('getVisibleCompanyIds')) {
        /**
         * Get the list of company IDs whose products should be visible to the current user.
         * - Super admin sees all companies + own products
         * - Company user sees own products + super admin products
         * - Staff user sees products assigned to them + super admin products
         *
         * @return array
         */
        function getVisibleCompanyIds()
        {
            $user = auth()->user();

            if (!$user) {
                return [];
            }

            if ($user->isSuperAdmin()) {
                return \App\Models\User::where('type', 'company')
                    ->pluck('id')
                    ->push($user->id)
                    ->toArray();
            }

            $superAdminId = getSuperAdminCompanyId();

            if ($user->type === 'company') {
                return [$user->id, $superAdminId];
            }

            $companyId = $user->created_by ?? createdBy();
            return [$companyId, $superAdminId];
        }
    }

    if (!function_exists('isCompanyUser')) {
        function isCompanyUser($user = null)
        {
            $user = $user ?? auth()->user();
            return $user && $user->type === 'company';
        }
    }

    if (!function_exists('getProductLimit')) {
        function getProductLimit($user = null)
        {
            $user = $user ?? auth()->user();

            if ($user->isSuperAdmin()) {
                return null;
            }

            return $user->product_limit ?? 10;
        }
    }

    if (!function_exists('hasReachedProductLimit')) {
        function hasReachedProductLimit($user = null)
        {
            $user = $user ?? auth()->user();

            if ($user->isSuperAdmin()) {
                return false;
            }

            $limit = getProductLimit($user);
            if ($limit === null) {
                return false;
            }

            $currentCount = \App\Models\Product::where('created_by', $user->type === 'company' ? $user->id : createdBy())
                ->count();

            return $currentCount >= $limit;
        }
    }

    if (!function_exists('isSuperAdminProduct')) {
        function isSuperAdminProduct($product)
        {
            return $product->created_by == getSuperAdminCompanyId();
        }
    }

    if (!function_exists('canAccessProduct')) {
        function canAccessProduct($product)
        {
            if (auth()->user()->isSuperAdmin()) {
                return true;
            }
            return in_array($product->created_by, getVisibleCompanyIds());
        }
    }

    if (!function_exists('canEditProduct')) {
        function canEditProduct($product)
        {
            if (auth()->user()->isSuperAdmin()) {
                return true;
            }
            return $product->created_by == createdBy();
        }
    }

    if (!function_exists('canDeleteProduct')) {
        function canDeleteProduct($product)
        {
            if (auth()->user()->isSuperAdmin()) {
                return true;
            }
            return $product->created_by == createdBy();
        }
    }

    if (!function_exists('getCompanyBySlug')) {
        /**
         * Get company (User with type='company') by slug.
         *
         * دالة مساعدة للـ API العام
         * القديم: Store::where('slug', $slug)
         * الجديد: User::where('slug', $slug)->where('type', 'company')
         *
         * @param string $slug
         * @return \App\Models\User|null
         */
        function getCompanyBySlug($slug)
        {
            return \App\Models\User::where('slug', $slug)
                ->where('type', 'company')
                ->first();
        }
    }

    if (!function_exists('generateCompanySlug')) {
        /**
         * Generate a unique slug for a company user.
         *
         * توليد slug فريد للشركة (يُستخدم عند إنشاء شركة جديدة)
         *
         * @param string $name
         * @return string
         */
        function generateCompanySlug($name)
        {
            $slug = \Illuminate\Support\Str::slug($name);
            $originalSlug = $slug;
            $count = 1;

            while (\App\Models\User::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }

            return $slug;
        }
    }

    if (!function_exists('getMergedProductData')) {
        /**
         * Merge product base data with health product data and company overrides.
         *
         * مطابقة تماماً لـ getMergedProductData في المشروع القديم
         *
         * Architecture:
         * - health_products: بيانات صحية (سوبر أدمن أو الشركة)
         * - product_company_overrides: تعديلات الشركة على منتجات السوبر أدمن
         *
         * أولوية البيانات:
         * 1. Product (الأساس)
         * 2. HealthProduct (بيانات صحية إضافية)
         * 3. ProductCompanyOverride (تعديلات الشركة على منتجات السوبر أدمن)
         * 4. Tags من pivot (أولوية تاجات الشركة)
         *
         * @param \App\Models\Product $product
         * @param int|null $companyId معرف الشركة (إذا null يستخدم createdBy())
         * @return object|null
         */
        if (!function_exists('getMergedProductData')) {
            /**
             * Merge product base data with health product data and company overrides.
             *
             * مطابقة تماماً لـ getMergedProductData في المشروع القديم
             * مع تحديث الـ primary_indications للقراءة من belongsToMany.
             *
             * @param \App\Models\Product $product
             * @param int|null $companyId
             * @return object|null
             */
            function getMergedProductData($product, $companyId = null)
            {
                if (!$product) {
                    return null;
                }

                $companyId = $companyId ?? createdBy();
                $superAdminId = getSuperAdminCompanyId();

                // ========================================================================
                // 1. تحميل HealthProduct مع أولوية الشركة
                // ========================================================================
                $health = \App\Models\HealthProduct::getForProduct($product->id, $companyId);

                // ========================================================================
                // 2. تحميل Override للشركة
                // ========================================================================
                $override = null;
                if ($product->created_by == $superAdminId) {
                    $override = \App\Models\ProductCompanyOverride::where('product_id', $product->id)
                        ->where('company_id', $companyId)
                        ->first();
                }

                // ========================================================================
                // 3. بناء البيانات المدمجة
                // ========================================================================
                $mergedProduct = $product->toArray();
                $mergedHealth = $health ? $health->toArray() : [];

                // ========================================================================
                // 4. تطبيق Override على الحقول القابلة للتعديل
                // ========================================================================
                if ($override) {
                    // ──── Product overrides ────
                    if ($override->description !== null) {
                        $mergedProduct['description'] = $override->description;
                    }
                    if ($override->price_override !== null) {
                        $mergedProduct['price'] = $override->price_override;
                    }
                    if ($override->sale_price_override !== null) {
                        $mergedProduct['sale_price'] = $override->sale_price_override;
                    }
                    if ($override->stock_quantity_override !== null) {
                        $mergedProduct['stock_quantity'] = $override->stock_quantity_override;
                    }
                    if ($override->stock_status_override !== null) {
                        $mergedProduct['stock_status'] = $override->stock_status_override;
                    }
                    if ($override->category_id !== null) {
                        $mergedProduct['category_id'] = $override->category_id;
                    }

                    // ──── Health Product overrides ────
                    if ($override->contraindications !== null) {
                        $mergedHealth['contraindications'] = $override->contraindications;
                    }
                    if ($override->research_links !== null) {
                        $mergedHealth['research_links'] = $override->research_links;
                    }

                    // ═══════════════════════════════════════════════════════════════════════
                    // ⚡ UPDATED: Primary Indications — لم نعد ننسخ الـ JSON القديم
                    // ═══════════════════════════════════════════════════════════════════════
                    // القديم: كان ينسخ override->primary_indications إلى $mergedHealth['primary_indications']
                    // الجديد: نقرأ من العلاقة belongsToMany عبر $product->getPrimaryIndicationNames()
                    //         مع دعم override->primary_indications كـ fallback أول.
                    // تم تطبيق ذلك في الخطوة 5 أدناه.
                    // ═══════════════════════════════════════════════════════════════════════

                    // ──── Dosing overrides ────
                    if ($override->dosing_na) {
                        $mergedHealth['dosing_upon_rising']      = null;
                        $mergedHealth['dosing_breakfast']         = null;
                        $mergedHealth['dosing_between_meals_am']  = null;
                        $mergedHealth['dosing_lunch']             = null;
                        $mergedHealth['dosing_between_meals_pm']  = null;
                        $mergedHealth['dosing_dinner']            = null;
                        $mergedHealth['dosing_before_sleep']      = null;
                        $mergedHealth['dosing_na']                = true;
                    } else {
                        if ($override->dosing_upon_rising !== null)      $mergedHealth['dosing_upon_rising']      = $override->dosing_upon_rising;
                        if ($override->dosing_breakfast !== null)         $mergedHealth['dosing_breakfast']         = $override->dosing_breakfast;
                        if ($override->dosing_between_meals_am !== null)  $mergedHealth['dosing_between_meals_am']  = $override->dosing_between_meals_am;
                        if ($override->dosing_lunch !== null)             $mergedHealth['dosing_lunch']             = $override->dosing_lunch;
                        if ($override->dosing_between_meals_pm !== null)  $mergedHealth['dosing_between_meals_pm']  = $override->dosing_between_meals_pm;
                        if ($override->dosing_dinner !== null)            $mergedHealth['dosing_dinner']            = $override->dosing_dinner;
                        if ($override->dosing_before_sleep !== null)      $mergedHealth['dosing_before_sleep']      = $override->dosing_before_sleep;
                        $mergedHealth['dosing_na'] = $override->dosing_na ?? ($health?->dosing_na ?? false);
                    }

                    // ──── Practitioner exclusive fields ────
                    $mergedHealth['practitioner_notes']         = $override->practitioner_notes;
                    $mergedHealth['custom_primary_indications'] = $override->custom_primary_indications;
                    $mergedHealth['custom_dosing_notes']        = $override->custom_dosing_notes;
                } else {
                    $mergedHealth['practitioner_notes']         = null;
                    $mergedHealth['custom_primary_indications'] = null;
                    $mergedHealth['custom_dosing_notes']        = null;
                }

                // ═══════════════════════════════════════════════════════════════════════
                // ⚡ NEW: Primary Indications من العلاقة belongsToMany
                // ═══════════════════════════════════════════════════════════════════════
                // $product->getPrimaryIndicationNames($companyId) يرجع:
                //   - override->primary_indications لو الشركة عندها override
                //   - أو العلاقة belongsToMany لو ما في override
                $mergedHealth['primary_indications'] = $product->getPrimaryIndicationNames($companyId);
                // ═══════════════════════════════════════════════════════════════════════

                // ========================================================================
                // 5. Tags من Pivot (أولوية تاجات الشركة)
                // ========================================================================
                $mergedProduct['tag_names'] = $product->getTagNames($companyId);

                // ========================================================================
                // 6. معلومات الصورة
                // ========================================================================
                $mergedProduct['image_url'] = !empty($mergedHealth['product_image_url'])
                    ? $mergedHealth['product_image_url']
                    : ($product->getFirstMediaUrl('main') ?: null);

                // ========================================================================
                // 7. معلومات التصنيف والعلامة التجارية
                // ========================================================================
                $mergedProduct['category_name'] = $product->category->name ?? null;
                $mergedProduct['brand_name'] = $product->brand->name ?? null;

                // ========================================================================
                // 8. حساب التسعير
                // ========================================================================
                $price = (float) ($mergedProduct['price'] ?? 0);
                $salePrice = (float) ($mergedProduct['sale_price'] ?? 0);
                $hasDiscount = ($salePrice > 0 && $salePrice < $price);
                $mergedProduct['has_discount'] = $hasDiscount;
                $mergedProduct['discount_percentage'] = $hasDiscount
                    ? round((($price - $salePrice) / $price) * 100, 2)
                    : 0;
                $mergedProduct['final_price'] = $hasDiscount ? $salePrice : $price;

                // ========================================================================
                // 9. هل المنتج مشترك؟
                // ========================================================================
                $mergedProduct['is_shared_product'] = $product->created_by == $superAdminId;

                return (object) array_merge($mergedProduct, ['health' => (object) $mergedHealth]);
            }
        }
    }
}
