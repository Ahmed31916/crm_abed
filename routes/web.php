<?php


use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PlanOrderController;
use App\Http\Controllers\PlanRequestController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\LandingPage\CustomPageController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ZeroPaymentController;
use App\Http\Controllers\RazorpayController;
use App\Http\Controllers\MercadoPagoController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\PayPalPaymentController;
use App\Http\Controllers\BankPaymentController;
use App\Http\Controllers\PaystackPaymentController;
use App\Http\Controllers\FlutterwavePaymentController;
use App\Http\Controllers\PayTabsPaymentController;
use App\Http\Controllers\SkrillPaymentController;
use App\Http\Controllers\CoinGatePaymentController;
use App\Http\Controllers\PayfastPaymentController;
use App\Http\Controllers\TapPaymentController;
use App\Http\Controllers\XenditPaymentController;
use App\Http\Controllers\PayTRPaymentController;
use App\Http\Controllers\MolliePaymentController;
use App\Http\Controllers\ToyyibPayPaymentController;
use App\Http\Controllers\CashfreeController;
use App\Http\Controllers\IyzipayPaymentController;
use App\Http\Controllers\BenefitPaymentController;
use App\Http\Controllers\OzowPaymentController;
use App\Http\Controllers\EasebuzzPaymentController;
use App\Http\Controllers\KhaltiPaymentController;
use App\Http\Controllers\AuthorizeNetPaymentController;
use App\Http\Controllers\FedaPayPaymentController;
use App\Http\Controllers\PayHerePaymentController;
use App\Http\Controllers\CinetPayPaymentController;
use App\Http\Controllers\PaiementPaymentController;
use App\Http\Controllers\NepalstePaymentController;
use App\Http\Controllers\YooKassaPaymentController;
use App\Http\Controllers\AamarpayPaymentController;
use App\Http\Controllers\MidtransPaymentController;
use App\Http\Controllers\PaymentWallPaymentController;
use App\Http\Controllers\SSPayPaymentController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseOrderCommentController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceReminderController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\ReturnOrderController;
use App\Http\Controllers\ReceiptOrderController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\AccountIndustryController;
use App\Http\Controllers\LeadStatusController;
use App\Http\Controllers\LeadSourceController;
use App\Http\Controllers\OpportunityStageController;
use App\Http\Controllers\OpportunitySourceController;
use App\Http\Controllers\CampaignTypeController;
use App\Http\Controllers\TargetListController;
use App\Http\Controllers\ShippingProviderTypeController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\ChatGptController;
use App\Http\Controllers\QuoteCommentController;
use App\Http\Controllers\SalesOrderCommentController;
use App\Http\Controllers\AccountCommentController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentFolderController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\LeadCommentController;
use App\Http\Controllers\OpportunityCommentController;
use App\Http\Controllers\InvoiceStripePaymentController;
use App\Http\Controllers\InvoicePayPalPaymentController;
use App\Http\Controllers\InvoiceBankPaymentController;
use App\Http\Controllers\InvoiceBenefitPaymentController;
use App\Http\Controllers\InvoiceCommentController;
use App\Http\Controllers\InvoiceRazorpayPaymentController;
use App\Http\Controllers\InvoiceSkrillPaymentController;
use App\Http\Controllers\InvoiceCoingatePaymentController;
use App\Http\Controllers\InvoicePayfastPaymentController;
use App\Http\Controllers\InvoiceTapPaymentController;
use App\Http\Controllers\InvoiceXenditPaymentController;
use App\Http\Controllers\InvoicePayTRPaymentController;
use App\Http\Controllers\InvoiceMolliePaymentController;
use App\Http\Controllers\InvoiceToyyibPayPaymentController;
use App\Http\Controllers\InvoicePaymentWallPaymentController;
use App\Http\Controllers\InvoiceSSPayPaymentController;
use App\Http\Controllers\InvoiceIyzipayPaymentController;
use App\Http\Controllers\InvoiceAamarpayPaymentController;
use App\Http\Controllers\InvoiceMidtransPaymentController;
use App\Http\Controllers\InvoiceYooKassaPaymentController;
use App\Http\Controllers\InvoicePaiementPaymentController;
use App\Http\Controllers\InvoiceCinetPayPaymentController;
use App\Http\Controllers\InvoicePayHerePaymentController;
use App\Http\Controllers\InvoiceFedaPayPaymentController;
use App\Http\Controllers\InvoiceAuthorizeNetPaymentController;
use App\Http\Controllers\InvoiceKhaltiPaymentController;
use App\Http\Controllers\InvoiceEasebuzzPaymentController;
use App\Http\Controllers\InvoiceOzowPaymentController;
use App\Http\Controllers\InvoiceCashfreePaymentController;
use App\Http\Controllers\LoginHistoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\Settings\SystemSettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::match(['GET', 'HEAD'], '/', [LandingPageController::class, 'show'])->name('home');
Route::post('/landing-page/contact', [LandingPageController::class, 'submitContact'])->name('landing-page.contact');
Route::post('/landing-page/subscribe', [LandingPageController::class, 'subscribe'])->name('landing-page.subscribe');

// Public form submission routes

// Cashfree webhook (public route)
Route::post('cashfree/webhook', [CashfreeController::class, 'webhook'])->name('cashfree.webhook');

// Benefit webhook (public route)
Route::post('benefit/webhook', [BenefitPaymentController::class, 'webhook'])->name('benefit.webhook');
Route::get('payments/benefit/success', [BenefitPaymentController::class, 'success'])->name('benefit.success');
Route::post('payments/benefit/callback', [BenefitPaymentController::class, 'callback'])->name('benefit.callback');

// FedaPay callback (public route)
Route::match(['GET', 'POST'], 'payments/fedapay/callback', [FedaPayPaymentController::class, 'callback'])->name('fedapay.callback');

// YooKassa success/callback (public routes)
Route::get('payments/yookassa/success', [YooKassaPaymentController::class, 'success'])->name('yookassa.success');
Route::post('payments/yookassa/callback', [YooKassaPaymentController::class, 'callback'])->name('yookassa.callback');

// Nepalste success/callback (public routes)
Route::get('payments/nepalste/success', [NepalstePaymentController::class, 'success'])->name('nepalste.success');
Route::post('payments/nepalste/callback', [NepalstePaymentController::class, 'callback'])->name('nepalste.callback');

// PayTR callback (public route)
Route::post('payments/paytr/callback', [PayTRPaymentController::class, 'callback'])->name('paytr.callback');

// PayTabs callback (public route)
Route::match(['GET', 'POST'], 'payments/paytabs/callback', [PayTabsPaymentController::class, 'callback'])->name('paytabs.callback');
Route::get('payments/paytabs/success', [PayTabsPaymentController::class, 'success'])->name('paytabs.success');

// Tap payment routes (public routes)
Route::get('payments/tap/success', [TapPaymentController::class, 'success'])->name('tap.success');
Route::post('payments/tap/callback', [TapPaymentController::class, 'callback'])->name('tap.callback');

// Aamarpay payment routes (public routes)
Route::match(['GET', 'POST'], 'payments/aamarpay/success', [AamarpayPaymentController::class, 'success'])->name('aamarpay.success');
Route::post('payments/aamarpay/callback', [AamarpayPaymentController::class, 'callback'])->name('aamarpay.callback');

// Iyzipay payment routes (public routes)
Route::post('payments/iyzipay/callback', [IyzipayPaymentController::class, 'callback'])->name('iyzipay.callback');
Route::match(['GET', 'POST'], 'payments/iyzipay/success', [IyzipayPaymentController::class, 'success'])->name('iyzipay.success');

// Invoice Iyzipay payment routes (public routes)
Route::match(['GET', 'POST'], 'invoices/payment/iyzipay/callback', [InvoiceIyzipayPaymentController::class, 'callback'])->name('invoice.iyzipay.callback')->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

// PaymentWall callback (public route)
Route::match(['GET', 'POST'], 'payments/paymentwall/callback', [PaymentWallPaymentController::class, 'callback'])->name('paymentwall.callback');
Route::get('payments/paymentwall/success', [PaymentWallPaymentController::class, 'success'])->name('paymentwall.success');

// PayFast payment routes (public routes)
Route::get('payments/payfast/success', [PayfastPaymentController::class, 'success'])->name('payfast.success');
Route::post('payments/payfast/callback', [PayfastPaymentController::class, 'callback'])->name('payfast.callback');

// CoinGate callback (public route)
Route::match(['GET', 'POST'], 'payments/coingate/callback', [CoinGatePaymentController::class, 'callback'])->name('coingate.callback');

// Xendit payment routes (public routes)
Route::get('payments/xendit/success', [XenditPaymentController::class, 'success'])->name('xendit.success');
Route::post('payments/xendit/callback', [XenditPaymentController::class, 'callback'])->name('xendit.callback');

// PWA Manifest routes removed


Route::get('/translations/{locale}', [TranslationController::class, 'getTranslations'])->name('translations');
Route::get('/refresh-language/{locale}', [TranslationController::class, 'refreshLanguage'])->name('refresh-language');
Route::get('/initial-locale', [TranslationController::class, 'getInitialLocale'])->name('initial-locale');
Route::post('/change-language', [TranslationController::class, 'changeLanguage'])->name('change-language');

// Custom Page Route
Route::get('/page/{slug}', [CustomPageController::class, 'show'])->name('custom-page.show');

Route::middleware(['auth', 'verified'])->group(function () {
    // Plans routes - accessible without plan check
    Route::get('plans', [PlanController::class, 'index'])->name('plans.index');
    Route::post('plans/request', [PlanController::class, 'requestPlan'])->name('plans.request');
    Route::post('plans/trial', [PlanController::class, 'startTrial'])->name('plans.trial');
    Route::post('plans/subscribe', [PlanController::class, 'subscribe'])->name('plans.subscribe');
    Route::post('plans/coupons/validate', [CouponController::class, 'validate'])->name('coupons.validate');

    // Payment routes - accessible without plan check
    Route::post('payments/zero', [ZeroPaymentController::class, 'processPayment'])->name('zero.payment');
    Route::post('payments/stripe', [StripePaymentController::class, 'processPayment'])->name('stripe.payment');
    Route::post('payments/paypal', [PayPalPaymentController::class, 'processPayment'])->name('paypal.payment');
    Route::post('payments/bank', [BankPaymentController::class, 'processPayment'])->name('bank.payment');
    Route::post('payments/paystack', [PaystackPaymentController::class, 'processPayment'])->name('paystack.payment');
    Route::post('payments/flutterwave', [FlutterwavePaymentController::class, 'processPayment'])->name('flutterwave.payment');
    Route::post('payments/paytabs', [PayTabsPaymentController::class, 'processPayment'])->name('paytabs.payment');
    Route::post('payments/skrill', [SkrillPaymentController::class, 'processPayment'])->name('skrill.payment');
    Route::post('payments/coingate', [CoinGatePaymentController::class, 'processPayment'])->name('coingate.payment');
    Route::post('payments/payfast', [PayfastPaymentController::class, 'processPayment'])->name('payfast.payment');
    Route::post('payments/mollie', [MolliePaymentController::class, 'processPayment'])->name('mollie.payment');
    Route::post('payments/toyyibpay', [ToyyibPayPaymentController::class, 'processPayment'])->name('toyyibpay.payment');
    Route::post('payments/iyzipay', [IyzipayPaymentController::class, 'processPayment'])->name('iyzipay.payment');
    Route::post('payments/benefit', [BenefitPaymentController::class, 'processPayment'])->name('benefit.payment');
    Route::post('payments/ozow', [OzowPaymentController::class, 'processPayment'])->name('ozow.payment');
    Route::post('payments/easebuzz', [EasebuzzPaymentController::class, 'processPayment'])->name('easebuzz.payment');
    Route::post('payments/khalti', [KhaltiPaymentController::class, 'processPayment'])->name('khalti.payment');
    Route::post('payments/authorizenet', [AuthorizeNetPaymentController::class, 'processPayment'])->name('authorizenet.payment');
    Route::post('payments/fedapay', [FedaPayPaymentController::class, 'processPayment'])->name('fedapay.payment');
    Route::post('payments/payhere', [PayHerePaymentController::class, 'processPayment'])->name('payhere.payment');
    Route::post('payments/cinetpay', [CinetPayPaymentController::class, 'processPayment'])->name('cinetpay.payment');
    Route::post('payments/paiement', [PaiementPaymentController::class, 'processPayment'])->name('paiement.payment');
    Route::post('payments/nepalste', [NepalstePaymentController::class, 'processPayment'])->name('nepalste.payment');
    Route::post('payments/yookassa', [YooKassaPaymentController::class, 'processPayment'])->name('yookassa.payment');
    Route::post('payments/aamarpay', [AamarpayPaymentController::class, 'processPayment'])->name('aamarpay.payment');
    Route::post('payments/midtrans', [MidtransPaymentController::class, 'processPayment'])->name('midtrans.payment');
    Route::post('payments/paymentwall', [PaymentWallPaymentController::class, 'processPayment'])->name('paymentwall.payment');
    Route::post('payments/sspay', [SSPayPaymentController::class, 'processPayment'])->name('sspay.payment');

    // Payment gateway specific routes
    Route::post('razorpay/create-order', [RazorpayController::class, 'createOrder'])->name('razorpay.create-order');
    Route::post('razorpay/verify-payment', [RazorpayController::class, 'verifyPayment'])->name('razorpay.verify-payment');
    Route::post('cashfree/create-session', [CashfreeController::class, 'createPaymentSession'])->name('cashfree.create-session');
    Route::post('cashfree/verify-payment', [CashfreeController::class, 'verifyPayment'])->name('cashfree.verify-payment');
    Route::post('mercadopago/create-preference', [MercadoPagoController::class, 'createPreference'])->name('mercadopago.create-preference');
    Route::post('mercadopago/process-payment', [MercadoPagoController::class, 'processPayment'])->name('mercadopago.process-payment');

    // Other payment creation routes
    Route::post('tap/create-payment', [TapPaymentController::class, 'createPayment'])->name('tap.create-payment');
    Route::post('xendit/create-payment', [XenditPaymentController::class, 'createPayment'])->name('xendit.create-payment');
    Route::post('payments/paytr/create-token', [PayTRPaymentController::class, 'createPaymentToken'])->name('paytr.create-token');
    Route::post('iyzipay/create-form', [IyzipayPaymentController::class, 'createPaymentForm'])->name('iyzipay.create-form');
    Route::post('benefit/create-session', [BenefitPaymentController::class, 'createPaymentSession'])->name('benefit.create-session');
    Route::post('ozow/create-payment', [OzowPaymentController::class, 'createPayment'])->name('ozow.create-payment');
    Route::post('easebuzz/create-payment', [EasebuzzPaymentController::class, 'createPayment'])->name('easebuzz.create-payment');
    Route::post('khalti/create-payment', [KhaltiPaymentController::class, 'createPayment'])->name('khalti.create-payment');
    Route::post('authorizenet/create-form', [AuthorizeNetPaymentController::class, 'createPaymentForm'])->name('authorizenet.create-form');
    Route::post('fedapay/create-payment', [FedaPayPaymentController::class, 'createPayment'])->name('fedapay.create-payment');
    Route::post('payhere/create-payment', [PayHerePaymentController::class, 'createPayment'])->name('payhere.create-payment');
    Route::post('cinetpay/create-payment', [CinetPayPaymentController::class, 'createPayment'])->name('cinetpay.create-payment');
    Route::post('paiement/create-payment', [PaiementPaymentController::class, 'createPayment'])->name('paiement.create-payment');
    Route::post('nepalste/create-payment', [NepalstePaymentController::class, 'createPayment'])->name('nepalste.create-payment');
    Route::post('yookassa/create-payment', [YooKassaPaymentController::class, 'createPayment'])->name('yookassa.create-payment');
    Route::post('aamarpay/create-payment', [AamarpayPaymentController::class, 'createPayment'])->name('aamarpay.create-payment');
    Route::post('midtrans/create-payment', [MidtransPaymentController::class, 'createPayment'])->name('midtrans.create-payment');
    Route::post('paymentwall/create-payment', [PaymentWallPaymentController::class, 'createPayment'])->name('paymentwall.create-payment');
    Route::post('sspay/create-payment', [SSPayPaymentController::class, 'createPayment'])->name('sspay.create-payment');

    // Payment success/callback routes
    Route::post('payments/skrill/callback', [SkrillPaymentController::class, 'callback'])->name('skrill.callback');
    Route::get('payments/paytr/success', [PayTRPaymentController::class, 'success'])->name('paytr.success');
    Route::get('payments/paytr/failure', [PayTRPaymentController::class, 'failure'])->name('paytr.failure');
    Route::get('payments/mollie/success', [MolliePaymentController::class, 'success'])->name('mollie.success');
    Route::post('payments/mollie/callback', [MolliePaymentController::class, 'callback'])->name('mollie.callback');
    Route::match(['GET', 'POST'], 'payments/toyyibpay/success', [ToyyibPayPaymentController::class, 'success'])->name('toyyibpay.success');
    Route::post('payments/toyyibpay/callback', [ToyyibPayPaymentController::class, 'callback'])->name('toyyibpay.callback');

    Route::get('payments/ozow/success', [OzowPaymentController::class, 'success'])->name('ozow.success');
    Route::post('payments/ozow/callback', [OzowPaymentController::class, 'callback'])->name('ozow.callback');
    Route::get('payments/payhere/success', [PayHerePaymentController::class, 'success'])->name('payhere.success');
    Route::post('payments/payhere/callback', [PayHerePaymentController::class, 'callback'])->name('payhere.callback');
    Route::get('payments/cinetpay/success', [CinetPayPaymentController::class, 'success'])->name('cinetpay.success');
    Route::post('payments/cinetpay/callback', [CinetPayPaymentController::class, 'callback'])->name('cinetpay.callback');
    Route::get('payments/paiement/success', [PaiementPaymentController::class, 'success'])->name('paiement.success');
    Route::post('payments/paiement/callback', [PaiementPaymentController::class, 'callback'])->name('paiement.callback');
    Route::post('payments/midtrans/callback', [MidtransPaymentController::class, 'callback'])->name('midtrans.callback');
    Route::post('paymentwall/process', [PaymentWallPaymentController::class, 'processPayment'])->name('paymentwall.process');
    Route::get('payments/sspay/success', [SSPayPaymentController::class, 'success'])->name('sspay.success');
    Route::post('payments/sspay/callback', [SSPayPaymentController::class, 'callback'])->name('sspay.callback');
    Route::get('mercadopago/success', [MercadoPagoController::class, 'success'])->name('mercadopago.success');
    Route::get('mercadopago/failure', [MercadoPagoController::class, 'failure'])->name('mercadopago.failure');
    Route::get('mercadopago/pending', [MercadoPagoController::class, 'pending'])->name('mercadopago.pending');
    Route::post('mercadopago/webhook', [MercadoPagoController::class, 'webhook'])->name('mercadopago.webhook');
    Route::post('authorizenet/test-connection', [AuthorizeNetPaymentController::class, 'testConnection'])->name('authorizenet.test-connection');

    // All other routes require plan access check
    Route::middleware('plan.access')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/redirect', [DashboardController::class, 'redirectToFirstAvailablePage'])->name('dashboard.redirect');

        Route::middleware('permission:manage-products')->group(function () {
            Route::resource('tags', App\Http\Controllers\TagController::class);

            // AJAX endpoints لربط/فصل التاجات بالمنتجات
            Route::post('tags/{tag}/attach-product', [App\Http\Controllers\TagController::class, 'attachProduct'])
                ->name('tags.attach-product');
            Route::post('tags/{tag}/detach-product', [App\Http\Controllers\TagController::class, 'detachProduct'])
                ->name('tags.detach-product');
            Route::post('tags/sync-product-tags', [App\Http\Controllers\TagController::class, 'syncProductTags'])
                ->name('tags.sync-product-tags');

            // JSON endpoints لـ dropdowns و API
            Route::get('tags/list', [App\Http\Controllers\TagController::class, 'list'])
                ->name('tags.list');
            Route::get('tags/product/{productId}', [App\Http\Controllers\TagController::class, 'productTags'])
                ->name('tags.product-tags');
        });

        Route::get('media-library', function () {
            $planLimits = null;
            if (auth()->user()->type === 'company') {
                $user = auth()->user();
                $plan = $user->getCurrentPlan();

                if ($plan && $plan->storage_limit > 0) {
                    $companyUsers = \App\Models\User::where('created_by', $user->id)->pluck('id')->push($user->id);
                    $currentStorageUsage = \Spatie\MediaLibrary\MediaCollections\Models\Media::whereIn('user_id', $companyUsers)->sum('size');
                    $storageLimit = $plan->storage_limit * 1024 * 1024 * 1024;
                    $planLimits = [
                        'current_storage' => $currentStorageUsage,
                        'max_storage' => $storageLimit,
                        'can_create' => $currentStorageUsage < $storageLimit
                    ];
                }
            }

            return Inertia::render('media-library', [
                'planLimits' => $planLimits
            ]);
        })->middleware('permission:manage-media')->name('media-library');

        Route::prefix('company-notifications')->name('company-notifications.')->group(function () {
            Route::get('/', [App\Http\Controllers\CompanyNotificationController::class, 'index'])->name('index');
            Route::get('/unread-count', [App\Http\Controllers\CompanyNotificationController::class, 'unreadCount'])->name('unread-count');
            Route::get('/recent', [App\Http\Controllers\CompanyNotificationController::class, 'recent'])->name('recent');
            Route::put('/{id}/mark-read', [App\Http\Controllers\CompanyNotificationController::class, 'markAsRead'])->name('mark-read');
            Route::put('/mark-all-read', [App\Http\Controllers\CompanyNotificationController::class, 'markAllAsRead'])->name('mark-all-read');
            Route::delete('/{id}', [App\Http\Controllers\CompanyNotificationController::class, 'destroy'])->name('destroy');
            Route::delete('/delete-all', [App\Http\Controllers\CompanyNotificationController::class, 'deleteAll'])->name('delete-all');
        });

        // Media Library API routes
        Route::get('api/media', [MediaController::class, 'index'])->middleware('permission:manage-media')->name('api.media.index');
        Route::post('api/media/batch', [MediaController::class, 'batchStore'])->middleware('permission:create-media')->name('api.media.batch');
        Route::get('api/media/{id}/download', [MediaController::class, 'download'])->middleware('permission:download-media')->name('api.media.download');
        Route::delete('api/media/{id}', [MediaController::class, 'destroy'])->middleware('permission:delete-media')->name('api.media.destroy');

        // Storage settings API
        Route::get('api/storage-settings', [SystemSettingsController::class, 'getStorageSettings'])->name('api.storage-settings');

        // Notification Templates routes
        Route::middleware('permission:manage-notification-templates')->group(function () {
            Route::get('notification-templates', [\App\Http\Controllers\NotificationTemplateController::class, 'index'])->name('notification-templates.index');
            Route::get('notification-templates/{notificationTemplate}', [\App\Http\Controllers\NotificationTemplateController::class, 'show'])->name('notification-templates.show');
            Route::put('notification-templates/{notificationTemplate}/content', [\App\Http\Controllers\NotificationTemplateController::class, 'updateContent'])->name('notification-templates.update-content');
        });

        // Permissions routes with granular permissions
        Route::middleware('permission:manage-permissions')->group(function () {
            Route::get('permissions', [PermissionController::class, 'index'])->middleware('permission:manage-permissions')->name('permissions.index');
            Route::get('permissions/create', [PermissionController::class, 'create'])->middleware('permission:create-permissions')->name('permissions.create');
            Route::post('permissions', [PermissionController::class, 'store'])->middleware('permission:create-permissions')->name('permissions.store');
            Route::get('permissions/{permission}', [PermissionController::class, 'show'])->middleware('permission:view-permissions')->name('permissions.show');
            Route::get('permissions/{permission}/edit', [PermissionController::class, 'edit'])->middleware('permission:edit-permissions')->name('permissions.edit');
            Route::put('permissions/{permission}', [PermissionController::class, 'update'])->middleware('permission:edit-permissions')->name('permissions.update');
            Route::patch('permissions/{permission}', [PermissionController::class, 'update'])->middleware('permission:edit-permissions');
            Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('permission:delete-permissions')->name('permissions.destroy');
        });

        // Roles routes with granular permissions
        Route::middleware('permission:manage-roles')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->middleware('permission:manage-roles')->name('roles.index');
            Route::get('roles/create', [RoleController::class, 'create'])->middleware('permission:create-roles')->name('roles.create');
            Route::post('roles', [RoleController::class, 'store'])->middleware('permission:create-roles')->name('roles.store');
            Route::get('roles/{role}', [RoleController::class, 'show'])->middleware('permission:view-roles')->name('roles.show');
            Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:edit-roles')->name('roles.edit');
            Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('permission:edit-roles')->name('roles.update');
            Route::patch('roles/{role}', [RoleController::class, 'update'])->middleware('permission:edit-roles');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:delete-roles')->name('roles.destroy');
        });

        // Users routes with granular permissions
        Route::middleware('permission:manage-users')->group(function () {
            Route::get('users', [UserController::class, 'index'])->middleware('permission:manage-users')->name('users.index');
            Route::get('users/create', [UserController::class, 'create'])->middleware('permission:create-users')->name('users.create');
            Route::post('users', [UserController::class, 'store'])->middleware('permission:create-users')->name('users.store');
            Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:view-users')->name('users.show');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:edit-users')->name('users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:edit-users')->name('users.update');
            Route::patch('users/{user}', [UserController::class, 'update'])->middleware('permission:edit-users');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:delete-users')->name('users.destroy');

            // Additional user routes
            Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('permission:reset-password-users')->name('users.reset-password');
            Route::put('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('permission:toggle-status-users')->name('users.toggle-status');
            Route::get('users-logs', [UserController::class, 'allUserLogs'])->middleware('permission:view-users')->name('users.all-logs');

            Route::put('users/{user}/product-limit', [UserController::class, 'updateProductLimit'])
                ->middleware('permission:edit-users')
                ->name('users.update-product-limit');
        });

        // Plans management routes (admin only)
        Route::middleware('permission:manage-plans')->group(function () {
            Route::get('plans/create', [PlanController::class, 'create'])->middleware('permission:create-plans')->name('plans.create');
            Route::post('plans', [PlanController::class, 'store'])->middleware('permission:create-plans')->name('plans.store');
            Route::get('plans/{plan}/edit', [PlanController::class, 'edit'])->middleware('permission:edit-plans')->name('plans.edit');
            Route::put('plans/{plan}', [PlanController::class, 'update'])->middleware('permission:edit-plans')->name('plans.update');
            Route::delete('plans/{plan}', [PlanController::class, 'destroy'])->middleware('permission:delete-plans')->name('plans.destroy');
            Route::post('plans/{plan}/toggle-status', [PlanController::class, 'toggleStatus'])->name('plans.toggle-status');
        });

        // Plan Orders routes
        Route::middleware('permission:manage-plan-orders')->group(function () {
            Route::get('plan-orders', [PlanOrderController::class, 'index'])->middleware('permission:manage-plan-orders')->name('plan-orders.index');
            Route::post('plan-orders/{planOrder}/approve', [PlanOrderController::class, 'approve'])->middleware('permission:approve-plan-orders')->name('plan-orders.approve');
            Route::post('plan-orders/{planOrder}/reject', [PlanOrderController::class, 'reject'])->middleware('permission:reject-plan-orders')->name('plan-orders.reject');
        });

        // Plan Requests routes (placeholder)
        Route::get('plan-requests', function () {
            return Inertia::render('plans/plan-requests');
        })->name('plan-requests.index');

        // Companies routes
        Route::middleware('permission:manage-companies')->group(function () {
            Route::get('companies', [CompanyController::class, 'index'])->middleware('permission:manage-companies')->name('companies.index');
            Route::post('companies', [CompanyController::class, 'store'])->middleware('permission:create-companies')->name('companies.store');
            Route::put('companies/{company}', [CompanyController::class, 'update'])->middleware('permission:edit-companies')->name('companies.update');
            Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->middleware('permission:delete-companies')->name('companies.destroy');
            Route::put('companies/{company}/reset-password', [CompanyController::class, 'resetPassword'])->middleware('permission:reset-password-companies')->name('companies.reset-password');
            Route::put('companies/{company}/toggle-status', [CompanyController::class, 'toggleStatus'])->middleware('permission:toggle-status-companies')->name('companies.toggle-status');
            Route::get('companies/{company}/plans', [CompanyController::class, 'getPlans'])->middleware('permission:manage-plans-companies')->name('companies.plans');
            Route::put('companies/{company}/upgrade-plan', [CompanyController::class, 'upgradePlan'])->middleware('permission:upgrade-plan-companies')->name('companies.upgrade-plan');
        });


        // Coupons routes
        Route::middleware('permission:manage-coupons')->group(function () {
            Route::get('coupons', [CouponController::class, 'index'])->middleware('permission:manage-coupons')->name('coupons.index');
            Route::get('coupons/{coupon}', [CouponController::class, 'show'])->middleware('permission:view-coupons')->name('coupons.show');
            Route::post('coupons', [CouponController::class, 'store'])->middleware('permission:create-coupons')->name('coupons.store');
            Route::put('coupons/{coupon}', [CouponController::class, 'update'])->middleware('permission:edit-coupons')->name('coupons.update');
            Route::put('coupons/{coupon}/toggle-status', [CouponController::class, 'toggleStatus'])->middleware('permission:toggle-status-coupons')->name('coupons.toggle-status');
            Route::delete('coupons/{coupon}', [CouponController::class, 'destroy'])->middleware('permission:delete-coupons')->name('coupons.destroy');
        });

        // Plan Requests routes
        Route::middleware('permission:manage-plan-requests')->group(function () {
            Route::get('plan-requests', [PlanRequestController::class, 'index'])->middleware('permission:manage-plan-requests')->name('plan-requests.index');
            Route::post('plan-requests/{planRequest}/approve', [PlanRequestController::class, 'approve'])->middleware('permission:approve-plan-requests')->name('plan-requests.approve');
            Route::post('plan-requests/{planRequest}/reject', [PlanRequestController::class, 'reject'])->middleware('permission:reject-plan-requests')->name('plan-requests.reject');
        });

        // Referral routes
        Route::middleware('permission:manage-referral')->group(function () {
            Route::get('referral', [ReferralController::class, 'index'])->middleware('permission:manage-referral')->name('referral.index');
            Route::get('referral/referred-users', [ReferralController::class, 'getReferredUsers'])->middleware('permission:manage-users-referral')->name('referral.referred-users');
            Route::post('referral/settings', [ReferralController::class, 'updateSettings'])->middleware('permission:manage-setting-referral')->name('referral.settings.update');
            Route::post('referral/payout-request', [ReferralController::class, 'createPayoutRequest'])->middleware('permission:manage-payout-referral')->name('referral.payout-request.create');
            Route::post('referral/payout-request/{payoutRequest}/approve', [ReferralController::class, 'approvePayoutRequest'])->middleware('permission:approve-payout-referral')->name('referral.payout-request.approve');
            Route::post('referral/payout-request/{payoutRequest}/reject', [ReferralController::class, 'rejectPayoutRequest'])->middleware('permission:reject-payout-referral')->name('referral.payout-request.reject');
        });

        // Currencies routes
        Route::middleware('permission:manage-currencies')->group(function () {
            Route::get('currencies', [CurrencyController::class, 'index'])->middleware('permission:manage-currencies')->name('currencies.index');
            Route::post('currencies', [CurrencyController::class, 'store'])->middleware('permission:create-currencies')->name('currencies.store');
            Route::put('currencies/{currency}', [CurrencyController::class, 'update'])->middleware('permission:edit-currencies')->name('currencies.update');
            Route::delete('currencies/{currency}', [CurrencyController::class, 'destroy'])->middleware('permission:delete-currencies')->name('currencies.destroy');
        });

        // Taxes routes
        Route::middleware('permission:manage-taxes')->group(function () {
            Route::get('taxes', [TaxController::class, 'index'])->middleware('permission:manage-taxes')->name('taxes.index');
            Route::post('taxes', [TaxController::class, 'store'])->middleware('permission:create-taxes')->name('taxes.store');
            Route::put('taxes/{tax}', [TaxController::class, 'update'])->middleware('permission:edit-taxes')->name('taxes.update');
            Route::delete('taxes/{tax}', [TaxController::class, 'destroy'])->middleware('permission:delete-taxes')->name('taxes.destroy');
            Route::put('taxes/{tax}/toggle-status', [TaxController::class, 'toggleStatus'])->middleware('permission:toggle-status-taxes')->name('taxes.toggle-status');
        });

        // Brands routes
        Route::middleware('permission:manage-brands')->group(function () {
            Route::get('brands', [BrandController::class, 'index'])->middleware('permission:manage-brands')->name('brands.index');
            Route::post('brands', [BrandController::class, 'store'])->middleware('permission:create-brands')->name('brands.store');
            Route::put('brands/{brand}', [BrandController::class, 'update'])->middleware('permission:edit-brands')->name('brands.update');
            Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->middleware('permission:delete-brands')->name('brands.destroy');
            Route::put('brands/{brand}/toggle-status', [BrandController::class, 'toggleStatus'])->middleware('permission:toggle-status-brands')->name('brands.toggle-status');
        });

        // Categories routes
        Route::middleware('permission:manage-categories')->group(function () {
            Route::get('categories', [CategoryController::class, 'index'])->middleware('permission:manage-categories')->name('categories.index');
            Route::post('categories', [CategoryController::class, 'store'])->middleware('permission:create-categories')->name('categories.store');
            Route::put('categories/{category}', [CategoryController::class, 'update'])->middleware('permission:edit-categories')->name('categories.update');
            Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:delete-categories')->name('categories.destroy');
            Route::put('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->middleware('permission:toggle-status-categories')->name('categories.toggle-status');
        });

        // Merchant comparison (Company users - compare their overrides with super admin originals)
        Route::get('products/merchant-comparison', [ProductController::class, 'merchantCompareResults'])
            ->name('products.merchant-comparison');
        Route::post('products/merchant-revert-field', [ProductController::class, 'merchantRevertField'])
            ->name('products.merchant-revert-field');

        // Provider comparison (Super Admin - compare local data with external provider API)
        Route::get('products/compare-with-provider', [ProductController::class, 'compareWithProvider'])
            ->name('products.compare-with-provider');
        Route::get('products/provider-comparison', [ProductController::class, 'providerComparisonResults'])
            ->name('products.provider-comparison');
        Route::post('products/comparison/{id}/accept', [ProductController::class, 'acceptComparison'])
            ->name('products.comparison.accept');
        Route::post('products/comparison/{id}/reject', [ProductController::class, 'rejectComparison'])
            ->name('products.comparison.reject');
        Route::post('products/comparison/{productId}/accept-all', [ProductController::class, 'acceptAllProductChanges'])
            ->name('products.comparison.accept-all');
        Route::post('products/comparison/{productId}/reject-all', [ProductController::class, 'rejectAllProductChanges'])
            ->name('products.comparison.reject-all');

        // Products routes
        Route::middleware('permission:manage-products')->group(function () {
            Route::get('products', [ProductController::class, 'index'])->middleware('permission:manage-products')->name('products.index');
            Route::get('products/create', [ProductController::class, 'create'])->middleware('permission:create-products')->name('products.create');
            Route::get('products/{product}', [ProductController::class, 'show'])->middleware('permission:view-products')->name('products.show');
            Route::get('products/{product}/edit', [ProductController::class, 'edit'])->middleware('permission:edit-products')->name('products.edit');
            Route::post('products', [ProductController::class, 'store'])->middleware('permission:create-products')->name('products.store');
            Route::put('products/{product}', [ProductController::class, 'update'])->middleware('permission:edit-products')->name('products.update');
            Route::delete('products/{product}/', [ProductController::class, 'destroy'])->middleware('permission:delete-products')->name('products.destroy');
            Route::put('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->middleware('permission:toggle-status-products')->name('products.toggle-status');

            // Product Import/Export routes
            Route::get('products/file/export/', [ProductController::class, 'fileExport'])->middleware('permission:export-products')->name('product.export');
            Route::post('products/file/parse', [ProductController::class, 'parseFile'])->middleware('permission:import-products')->name('product.parse');
            Route::post('products/file/import', [ProductController::class, 'fileImport'])->middleware('permission:import-products')->name('product.import');
            Route::get('products/download/template', [ProductController::class, 'downloadTemplate'])->name('product.download.template');

            Route::prefix('product-overrides')->name('product-overrides.')->group(function () {
                Route::post('/', [ProductController::class, 'updateOverride'])->name('store');
                Route::put('/{productId}', [ProductController::class, 'updateOverride'])->name('update');
            });
        });

        // Reports routes
        Route::middleware('permission:manage-reports')->group(function () {
            Route::get('reports/leads', [ReportsController::class, 'leads'])->name('reports.leads');
            Route::get('reports/sales', [ReportsController::class, 'sales'])->name('reports.sales');
            Route::get('reports/product-reports', [ReportsController::class, 'products'])->name('reports.product-reports');
            Route::get('reports/customers', [ReportsController::class, 'customers'])->name('reports.customers');
            Route::get('reports/projects', [ReportsController::class, 'projects'])->name('reports.projects');
        });

        // Account Types routes
        Route::middleware('permission:manage-account-types')->group(function () {
            Route::get('account-types', [AccountTypeController::class, 'index'])->middleware('permission:manage-account-types')->name('account-types.index');
            Route::post('account-types', [AccountTypeController::class, 'store'])->middleware('permission:create-account-types')->name('account-types.store');
            Route::put('account-types/{accountType}', [AccountTypeController::class, 'update'])->middleware('permission:edit-account-types')->name('account-types.update');
            Route::delete('account-types/{accountType}', [AccountTypeController::class, 'destroy'])->middleware('permission:delete-account-types')->name('account-types.destroy');
            Route::put('account-types/{accountType}/toggle-status', [AccountTypeController::class, 'toggleStatus'])->middleware('permission:toggle-status-account-types')->name('account-types.toggle-status');
        });

        // Account Industries routes
        Route::middleware('permission:manage-account-industries')->group(function () {
            Route::get('account-industries', [AccountIndustryController::class, 'index'])->middleware('permission:manage-account-industries')->name('account-industries.index');
            Route::post('account-industries', [AccountIndustryController::class, 'store'])->middleware('permission:create-account-industries')->name('account-industries.store');
            Route::put('account-industries/{accountIndustry}', [AccountIndustryController::class, 'update'])->middleware('permission:edit-account-industries')->name('account-industries.update');
            Route::delete('account-industries/{accountIndustry}', [AccountIndustryController::class, 'destroy'])->middleware('permission:delete-account-industries')->name('account-industries.destroy');
            Route::put('account-industries/{accountIndustry}/toggle-status', [AccountIndustryController::class, 'toggleStatus'])->middleware('permission:toggle-status-account-industries')->name('account-industries.toggle-status');
        });

        // Accounts routes
        Route::middleware('permission:manage-accounts')->group(function () {
            Route::get('accounts', [AccountController::class, 'index'])->middleware('permission:manage-accounts')->name('accounts.index');
            Route::get('accounts/{account}', [AccountController::class, 'show'])->middleware('permission:view-accounts')->name('accounts.show');
            Route::post('accounts', [AccountController::class, 'store'])->middleware('permission:create-accounts')->name('accounts.store');
            Route::put('accounts/{account}', [AccountController::class, 'update'])->middleware('permission:edit-accounts')->name('accounts.update');
            Route::delete('accounts/{account}', [AccountController::class, 'destroy'])->middleware('permission:delete-accounts')->name('accounts.destroy');
            Route::put('accounts/{account}/toggle-status', [AccountController::class, 'toggleStatus'])->middleware('permission:toggle-status-accounts')->name('accounts.toggle-status');
            Route::delete('accounts/{account}/activities', [AccountController::class, 'deleteActivities'])->middleware('permission:delete-accounts')->name('accounts.delete-activities');
            Route::delete('accounts/{account}/activities/{activity}', [AccountController::class, 'deleteActivity'])->middleware('permission:delete-accounts')->name('accounts.delete-activity');

            // Account Export route
            Route::get('accounts/file/export/', [AccountController::class, 'fileExport'])->middleware('permission:export-accounts')->name('account.export');

            // Account Comments routes
            Route::post('accounts/{account}/comments', [AccountCommentController::class, 'store'])->middleware('permission:create-accounts')->name('accounts.comments.store');
            Route::put('accounts/{account}/activities/{activity}/comment', [AccountCommentController::class, 'updateActivity'])->middleware('permission:edit-accounts')->name('accounts.comments.update-activity');
        });

        // Contacts routes
        Route::middleware('permission:manage-contacts')->group(function () {
            Route::get('contacts', [ContactController::class, 'index'])->middleware('permission:manage-contacts')->name('contacts.index');
            Route::get('contacts/{contact}', [ContactController::class, 'show'])->middleware('permission:view-contacts')->name('contacts.show');
            Route::post('contacts', [ContactController::class, 'store'])->middleware('permission:create-contacts')->name('contacts.store');
            Route::put('contacts/{contact}', [ContactController::class, 'update'])->middleware('permission:edit-contacts')->name('contacts.update');
            Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])->middleware('permission:delete-contacts')->name('contacts.destroy');
            Route::put('contacts/{contact}/toggle-status', [ContactController::class, 'toggleStatus'])->middleware('permission:toggle-status-contacts')->name('contacts.toggle-status');

            // Contact Export route
            Route::get('contacts/file/export/', [ContactController::class, 'fileExport'])->middleware('permission:export-contacts')->name('contact.export');
        });

        // Lead Status routes
        Route::middleware('permission:manage-lead-statuses')->group(function () {
            Route::get('lead-statuses', [LeadStatusController::class, 'index'])->middleware('permission:manage-lead-statuses')->name('lead-statuses.index');
            Route::post('lead-statuses', [LeadStatusController::class, 'store'])->middleware('permission:create-lead-statuses')->name('lead-statuses.store');
            Route::put('lead-statuses/{leadStatus}', [LeadStatusController::class, 'update'])->middleware('permission:edit-lead-statuses')->name('lead-statuses.update');
            Route::delete('lead-statuses/{leadStatus}', [LeadStatusController::class, 'destroy'])->middleware('permission:delete-lead-statuses')->name('lead-statuses.destroy');
            Route::put('lead-statuses/{leadStatus}/toggle-status', [LeadStatusController::class, 'toggleStatus'])->middleware('permission:toggle-status-lead-statuses')->name('lead-statuses.toggle-status');
        });

        // Lead Source routes
        Route::middleware('permission:manage-lead-sources')->group(function () {
            Route::get('lead-sources', [LeadSourceController::class, 'index'])->middleware('permission:manage-lead-sources')->name('lead-sources.index');
            Route::post('lead-sources', [LeadSourceController::class, 'store'])->middleware('permission:create-lead-sources')->name('lead-sources.store');
            Route::put('lead-sources/{leadSource}', [LeadSourceController::class, 'update'])->middleware('permission:edit-lead-sources')->name('lead-sources.update');
            Route::delete('lead-sources/{leadSource}', [LeadSourceController::class, 'destroy'])->middleware('permission:delete-lead-sources')->name('lead-sources.destroy');
            Route::put('lead-sources/{leadSource}/toggle-status', [LeadSourceController::class, 'toggleStatus'])->middleware('permission:toggle-status-lead-sources')->name('lead-sources.toggle-status');
        });

        // Lead routes
        Route::middleware('permission:manage-leads')->group(function () {
            Route::get('leads', [LeadController::class, 'index'])->middleware('permission:manage-leads')->name('leads.index');
            Route::get('leads/{lead}', [LeadController::class, 'show'])->middleware('permission:view-leads')->name('leads.show');
            Route::post('leads', [LeadController::class, 'store'])->middleware('permission:create-leads')->name('leads.store');
            Route::put('leads/{lead}', [LeadController::class, 'update'])->middleware('permission:edit-leads')->name('leads.update');
            Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->middleware('permission:delete-leads')->name('leads.destroy');
            Route::put('leads/{lead}/toggle-status', [LeadController::class, 'toggleStatus'])->middleware('permission:toggle-status-leads')->name('leads.toggle-status');
            Route::put('leads/{lead}/convert-to-account', [LeadController::class, 'convertToAccount'])->middleware('permission:convert-leads')->name('leads.convert-to-account');
            Route::put('leads/{lead}/convert-to-contact', [LeadController::class, 'convertToContact'])->middleware('permission:convert-leads')->name('leads.convert-to-contact');

            Route::post('leads/{lead}/update-field', [LeadController::class, 'updateField'])->middleware('permission:edit-leads')->name('leads.update-field');
            Route::put('leads/{lead}/update-status', [LeadController::class, 'updateStatus'])->middleware('permission:edit-leads')->name('leads.update-status');
            Route::delete('leads/{lead}/activities', [LeadController::class, 'deleteActivities'])->middleware('permission:delete-leads')->name('leads.delete-activities');
            Route::delete('leads/{lead}/activities/{activity}', [LeadController::class, 'deleteActivity'])->middleware('permission:delete-leads')->name('leads.delete-activity');

            // Lead Import/Export routes
            Route::get('leads/file/export/', [LeadController::class, 'fileExport'])->middleware('permission:export-leads')->name('lead.export');
            Route::post('leads/file/parse', [LeadController::class, 'parseFile'])->middleware('permission:import-leads')->name('lead.parse');
            Route::post('leads/file/import', [LeadController::class, 'fileImport'])->middleware('permission:import-leads')->name('lead.import');
            Route::get('leads/download/template', [LeadController::class, 'downloadTemplate'])->name('lead.download.template');

            // Lead Comments routes
            Route::post('leads/{lead}/comments', [LeadCommentController::class, 'store'])->middleware('permission:create-leads')->name('leads.comments.store');
            Route::put('leads/{lead}/comments/{comment}', [LeadCommentController::class, 'update'])->middleware('permission:edit-leads')->name('leads.comments.update');
            Route::put('leads/{lead}/activities/{activity}/comment', [LeadCommentController::class, 'updateActivity'])->middleware('permission:edit-leads')->name('leads.comments.update-activity');
            Route::delete('leads/{lead}/comments/{comment}', [LeadCommentController::class, 'destroy'])->middleware('permission:delete-leads')->name('leads.comments.destroy');
        });

        // Opportunity Stage routes
        Route::middleware('permission:manage-opportunity-stages')->group(function () {
            Route::get('opportunity-stages', [OpportunityStageController::class, 'index'])->middleware('permission:manage-opportunity-stages')->name('opportunity-stages.index');
            Route::post('opportunity-stages', [OpportunityStageController::class, 'store'])->middleware('permission:create-opportunity-stages')->name('opportunity-stages.store');
            Route::put('opportunity-stages/{opportunityStage}', [OpportunityStageController::class, 'update'])->middleware('permission:edit-opportunity-stages')->name('opportunity-stages.update');
            Route::delete('opportunity-stages/{opportunityStage}', [OpportunityStageController::class, 'destroy'])->middleware('permission:delete-opportunity-stages')->name('opportunity-stages.destroy');
            Route::put('opportunity-stages/{opportunityStage}/toggle-status', [OpportunityStageController::class, 'toggleStatus'])->middleware('permission:toggle-status-opportunity-stages')->name('opportunity-stages.toggle-status');
        });

        // Opportunity Source routes
        Route::middleware('permission:manage-opportunity-sources')->group(function () {
            Route::get('opportunity-sources', [OpportunitySourceController::class, 'index'])->middleware('permission:manage-opportunity-sources')->name('opportunity-sources.index');
            Route::post('opportunity-sources', [OpportunitySourceController::class, 'store'])->middleware('permission:create-opportunity-sources')->name('opportunity-sources.store');
            Route::put('opportunity-sources/{opportunitySource}', [OpportunitySourceController::class, 'update'])->middleware('permission:edit-opportunity-sources')->name('opportunity-sources.update');
            Route::delete('opportunity-sources/{opportunitySource}', [OpportunitySourceController::class, 'destroy'])->middleware('permission:delete-opportunity-sources')->name('opportunity-sources.destroy');
            Route::put('opportunity-sources/{opportunitySource}/toggle-status', [OpportunitySourceController::class, 'toggleStatus'])->middleware('permission:toggle-status-opportunity-sources')->name('opportunity-sources.toggle-status');
        });

        // Opportunity routes
        Route::middleware('permission:manage-opportunities')->group(function () {
            Route::get('opportunities', [OpportunityController::class, 'index'])->middleware('permission:manage-opportunities')->name('opportunities.index');
            Route::get('opportunities/{opportunity}', [OpportunityController::class, 'show'])->middleware('permission:view-opportunities')->name('opportunities.show');
            Route::post('opportunities', [OpportunityController::class, 'store'])->middleware('permission:create-opportunities')->name('opportunities.store');
            Route::put('opportunities/{opportunity}', [OpportunityController::class, 'update'])->middleware('permission:edit-opportunities')->name('opportunities.update');
            Route::delete('opportunities/{opportunity}', [OpportunityController::class, 'destroy'])->middleware('permission:delete-opportunities')->name('opportunities.destroy');
            Route::put('opportunities/{opportunity}/toggle-status', [OpportunityController::class, 'toggleStatus'])->middleware('permission:toggle-status-opportunities')->name('opportunities.toggle-status');
            Route::put('opportunities/{opportunity}/update-status', [OpportunityController::class, 'updateStatus'])->middleware('permission:edit-opportunities')->name('opportunities.update-status');
            Route::delete('opportunities/{opportunity}/activities', [OpportunityController::class, 'deleteActivities'])->middleware('permission:delete-opportunities')->name('opportunities.delete-activities');
            Route::delete('opportunities/{opportunity}/activities/{activity}', [OpportunityController::class, 'deleteActivity'])->middleware('permission:delete-opportunities')->name('opportunities.delete-activity');

            // Opportunity Export route
            Route::get('opportunities/file/export/', [OpportunityController::class, 'fileExport'])->middleware('permission:export-opportunities')->name('opportunity.export');

            // Opportunity Comments routes
            Route::post('opportunities/{opportunity}/comments', [OpportunityCommentController::class, 'store'])->middleware('permission:create-opportunities')->name('opportunities.comments.store');
            Route::put('opportunities/{opportunity}/activities/{activity}/comment', [OpportunityCommentController::class, 'updateActivity'])->middleware('permission:edit-opportunities')->name('opportunities.comments.update-activity');
        });

        // Campaign Type routes
        Route::middleware('permission:manage-campaign-types')->group(function () {
            Route::get('campaign-types', [CampaignTypeController::class, 'index'])->middleware('permission:manage-campaign-types')->name('campaign-types.index');
            Route::post('campaign-types', [CampaignTypeController::class, 'store'])->middleware('permission:create-campaign-types')->name('campaign-types.store');
            Route::put('campaign-types/{campaignType}', [CampaignTypeController::class, 'update'])->middleware('permission:edit-campaign-types')->name('campaign-types.update');
            Route::delete('campaign-types/{campaignType}', [CampaignTypeController::class, 'destroy'])->middleware('permission:delete-campaign-types')->name('campaign-types.destroy');
            Route::put('campaign-types/{campaignType}/toggle-status', [CampaignTypeController::class, 'toggleStatus'])->middleware('permission:toggle-status-campaign-types')->name('campaign-types.toggle-status');
        });

        // Target List routes
        Route::middleware('permission:manage-target-lists')->group(function () {
            Route::get('target-lists', [TargetListController::class, 'index'])->middleware('permission:manage-target-lists')->name('target-lists.index');
            Route::post('target-lists', [TargetListController::class, 'store'])->middleware('permission:create-target-lists')->name('target-lists.store');
            Route::put('target-lists/{targetList}', [TargetListController::class, 'update'])->middleware('permission:edit-target-lists')->name('target-lists.update');
            Route::delete('target-lists/{targetList}', [TargetListController::class, 'destroy'])->middleware('permission:delete-target-lists')->name('target-lists.destroy');
            Route::put('target-lists/{targetList}/toggle-status', [TargetListController::class, 'toggleStatus'])->middleware('permission:toggle-status-target-lists')->name('target-lists.toggle-status');
        });

        // Campaign routes
        Route::middleware('permission:manage-campaigns')->group(function () {
            Route::get('campaigns', [CampaignController::class, 'index'])->middleware('permission:manage-campaigns')->name('campaigns.index');
            Route::post('campaigns', [CampaignController::class, 'store'])->middleware('permission:create-campaigns')->name('campaigns.store');
            Route::put('campaigns/{campaign}', [CampaignController::class, 'update'])->middleware('permission:edit-campaigns')->name('campaigns.update');
            Route::delete('campaigns/{campaign}', [CampaignController::class, 'destroy'])->middleware('permission:delete-campaigns')->name('campaigns.destroy');
            Route::get('campaigns/{campaign}', [CampaignController::class, 'show'])->middleware('permission:view-campaigns')->name('campaigns.show');
            Route::put('campaigns/{campaign}/toggle-status', [CampaignController::class, 'toggleStatus'])->middleware('permission:toggle-status-campaigns')->name('campaigns.toggle-status');
        });

        // Shipping Provider Type routes
        Route::middleware('permission:manage-shipping-provider-types')->group(function () {
            Route::get('shipping-provider-types', [ShippingProviderTypeController::class, 'index'])->middleware('permission:manage-shipping-provider-types')->name('shipping-provider-types.index');
            Route::get('shipping-provider-types/{id}', [ShippingProviderTypeController::class, 'show'])->middleware('permission:view-shipping-provider-types')->name('shipping-provider-types.show');
            Route::post('shipping-provider-types', [ShippingProviderTypeController::class, 'store'])->middleware('permission:create-shipping-provider-types')->name('shipping-provider-types.store');
            Route::put('shipping-provider-types/{shippingProviderType}', [ShippingProviderTypeController::class, 'update'])->middleware('permission:edit-shipping-provider-types')->name('shipping-provider-types.update');
            Route::delete('shipping-provider-types/{shippingProviderType}', [ShippingProviderTypeController::class, 'destroy'])->middleware('permission:delete-shipping-provider-types')->name('shipping-provider-types.destroy');
            Route::put('shipping-provider-types/{shippingProviderType}/toggle-status', [ShippingProviderTypeController::class, 'toggleStatus'])->middleware('permission:toggle-status-shipping-provider-types')->name('shipping-provider-types.toggle-status');
        });

        // Cases routes
        Route::middleware('permission:manage-cases')->group(function () {
            Route::get('cases', [CaseController::class, 'index'])->middleware('permission:manage-cases')->name('cases.index');
            Route::get('cases/create', [CaseController::class, 'create'])->middleware('permission:create-cases')->name('cases.create');
            Route::get('cases/{case}', [CaseController::class, 'show'])->middleware('permission:view-cases')->name('cases.show');
            Route::get('cases/{case}/edit', [CaseController::class, 'edit'])->middleware('permission:edit-cases')->name('cases.edit');
            Route::post('cases', [CaseController::class, 'store'])->middleware('permission:create-cases')->name('cases.store');
            Route::put('cases/{case}', [CaseController::class, 'update'])->middleware('permission:edit-cases')->name('cases.update');
            Route::delete('cases/{case}', [CaseController::class, 'destroy'])->middleware('permission:delete-cases')->name('cases.destroy');
            Route::put('cases/{case}/toggle-status', [CaseController::class, 'toggleStatus'])->middleware('permission:toggle-status-cases')->name('cases.toggle-status');

            // Case Export route
            Route::get('cases/file/export/', [CaseController::class, 'fileExport'])->middleware('permission:export-cases')->name('case.export');
        });

        // Quote routes
        Route::middleware('permission:manage-quotes')->group(function () {
            Route::get('quotes', [QuoteController::class, 'index'])->middleware('permission:manage-quotes')->name('quotes.index');
            Route::get('quotes/{quote}', [QuoteController::class, 'show'])->middleware('permission:view-quotes')->name('quotes.show');
            Route::post('quotes', [QuoteController::class, 'store'])->middleware('permission:create-quotes')->name('quotes.store');
            Route::put('quotes/{quote}', [QuoteController::class, 'update'])->middleware('permission:edit-quotes')->name('quotes.update');
            Route::delete('quotes/{quote}', [QuoteController::class, 'destroy'])->middleware('permission:delete-quotes')->name('quotes.destroy');
            Route::put('quotes/{quote}/toggle-status', [QuoteController::class, 'toggleStatus'])->middleware('permission:toggle-status-quotes')->name('quotes.toggle-status');
            Route::put('quotes/{quote}/assign-user', [QuoteController::class, 'assignUser'])->middleware('permission:edit-quotes')->name('quotes.assign-user');
            Route::put('quotes/{quote}/add-opportunity', [QuoteController::class, 'addOpportunity'])->middleware('permission:edit-quotes')->name('quotes.add-opportunity');

            // Quote Export route
            Route::get('quotes/file/export/', [QuoteController::class, 'fileExport'])->middleware('permission:export-quotes')->name('quote.export');

            // Quote Comments routes
            Route::post('quotes/{quote}/comments', [QuoteCommentController::class, 'store'])->middleware('permission:create-quotes')->name('quotes.comments.store');
            Route::put('quotes/{quote}/activities/{activity}/comment', [QuoteCommentController::class, 'updateActivity'])->middleware('permission:edit-quotes')->name('quotes.comments.update-activity');

            // Quote Activity delete routes
            Route::delete('quotes/{quote}/activities', [QuoteController::class, 'deleteActivities'])->middleware('permission:delete-quotes')->name('quotes.delete-activities');
            Route::delete('quotes/{quote}/activities/{activity}', [QuoteController::class, 'deleteActivity'])->middleware('permission:delete-quotes')->name('quotes.delete-activity');
            Route::get('api/opportunities/{opportunity}/details', [QuoteController::class, 'getOpportunityDetails'])->name('api.opportunities.details');
        });


        // Sales Order routes
        Route::middleware('permission:manage-sales-orders')->group(function () {
            Route::get('sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
            Route::get('sales-orders', [SalesOrderController::class, 'index'])->middleware('permission:manage-sales-orders')->name('sales-orders.index');
            Route::get('sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->middleware('permission:view-sales-orders')->name('sales-orders.show');
            Route::post('sales-orders', [SalesOrderController::class, 'store'])->middleware('permission:create-sales-orders')->name('sales-orders.store');
            Route::put('sales-orders/{salesOrder}', [SalesOrderController::class, 'update'])->middleware('permission:edit-sales-orders')->name('sales-orders.update');
            Route::delete('sales-orders/{salesOrder}', [SalesOrderController::class, 'destroy'])->middleware('permission:delete-sales-orders')->name('sales-orders.destroy');
            Route::put('sales-orders/{salesOrder}/toggle-status', [SalesOrderController::class, 'toggleStatus'])->middleware('permission:toggle-status-sales-orders')->name('sales-orders.toggle-status');

            Route::put('sales-orders/{salesOrder}/assign-user', [SalesOrderController::class, 'assignUser'])->middleware('permission:edit-sales-orders')->name('sales-orders.assign-user');

            // Sales Order Export route
            Route::get('sales-orders/file/export/', [SalesOrderController::class, 'fileExport'])->middleware('permission:export-sales-orders')->name('sales-order.export');

            // Sales Order Comments routes
            Route::post('sales-orders/{salesOrder}/comments', [SalesOrderCommentController::class, 'store'])->middleware('permission:create-sales-orders')->name('sales-orders.comments.store');
            Route::put('sales-orders/{salesOrder}/activities/{activity}/comment', [SalesOrderCommentController::class, 'updateActivity'])->middleware('permission:edit-sales-orders')->name('sales-orders.comments.update-activity');

            // Sales Order Activity delete routes
            Route::delete('sales-orders/{salesOrder}/activities', [SalesOrderController::class, 'deleteActivities'])->middleware('permission:delete-sales-orders')->name('sales-orders.delete-activities');
            Route::delete('sales-orders/{salesOrder}/activities/{activity}', [SalesOrderController::class, 'deleteActivity'])->middleware('permission:delete-sales-orders')->name('sales-orders.delete-activity');
        });

        Route::get('api/quotes/{quote}/details', [SalesOrderController::class, 'getQuoteDetails'])->name('api.quotes.details');
        Route::get('api/sales-orders/{salesOrder}/details', [PurchaseOrderController::class, 'getSalesOrderDetails'])->name('api.sales-orders.details');
        Route::get('api/invoices/sales-orders/{salesOrder}/details', [InvoiceController::class, 'getSalesOrderDetails'])->name('api.invoices.sales-orders.details');
        Route::get('api/invoices/quotes/{quote}/details', [InvoiceController::class, 'getQuoteDetails'])->name('api.invoices.quotes.details');
        Route::get('api/invoices/opportunities/{opportunity}/details', [InvoiceController::class, 'getOpportunityDetails'])->name('api.invoices.opportunities.details');

        Route::get('api/return-orders/sales-orders/{salesOrder}/details', [ReturnOrderController::class, 'getSalesOrderDetails'])->name('api.return-orders.sales-orders.details');

        Route::get('api/receipt-orders/purchase-orders/{purchaseOrder}/details', [ReceiptOrderController::class, 'getPurchaseOrderDetails'])->name('api.receipt-orders.purchase-orders.details');
        Route::get('api/receipt-orders/return-orders/{returnOrder}/details', [ReceiptOrderController::class, 'getReturnOrderDetails'])->name('api.receipt-orders.return-orders.details');

        // Invoice routes
        Route::middleware('permission:manage-invoices')->group(function () {
            Route::get('invoices', [InvoiceController::class, 'index'])->middleware('permission:manage-invoices')->name('invoices.index');
            Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('permission:view-invoices')->name('invoices.show');
            Route::post('invoices', [InvoiceController::class, 'store'])->middleware('permission:create-invoices')->name('invoices.store');
            Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->middleware('permission:edit-invoices')->name('invoices.update');
            Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->middleware('permission:delete-invoices')->name('invoices.destroy');
            Route::put('invoices/{invoice}/toggle-status', [InvoiceController::class, 'toggleStatus'])->middleware('permission:toggle-status-invoices')->name('invoices.toggle-status');
            Route::post('invoices/{invoice}/send-reminder', [InvoiceReminderController::class, 'sendReminder'])->middleware('permission:send-reminder-invoices')->name('invoices.send-reminder');
            Route::get('invoices/{invoice}/reminder-history', [InvoiceController::class, 'getReminderHistory'])->middleware('permission:view-invoices')->name('invoices.reminder-history');

            Route::put('invoices/{invoice}/assign-user', [InvoiceController::class, 'assignUser'])->middleware('permission:edit-invoices')->name('invoices.assign-user');

            // Invoice Export route
            Route::get('invoices/file/export/', [InvoiceController::class, 'fileExport'])->middleware('permission:export-invoices')->name('invoice.export');

            // Invoice Comments routes
            Route::post('invoices/{invoice}/comments', [InvoiceCommentController::class, 'store'])->middleware('permission:create-invoices')->name('invoices.comments.store');
            Route::put('invoices/{invoice}/activities/{activity}/comment', [InvoiceCommentController::class, 'updateActivity'])->middleware('permission:edit-invoices')->name('invoices.comments.update-activity');

            // Invoice Activity delete routes
            Route::delete('invoices/{invoice}/activities', [InvoiceController::class, 'deleteActivities'])->middleware('permission:delete-invoices')->name('invoices.delete-activities');
            Route::delete('invoices/{invoice}/activities/{activity}', [InvoiceController::class, 'deleteActivity'])->middleware('permission:delete-invoices')->name('invoices.delete-activity');
        });

        // Delivery Order routes
        Route::middleware('permission:manage-delivery-orders')->group(function () {
            Route::get('delivery-orders', [DeliveryOrderController::class, 'index'])->middleware('permission:manage-delivery-orders')->name('delivery-orders.index');
            Route::get('delivery-orders/{deliveryOrder}', [DeliveryOrderController::class, 'show'])->middleware('permission:view-delivery-orders')->name('delivery-orders.show');
            Route::post('delivery-orders', [DeliveryOrderController::class, 'store'])->middleware('permission:create-delivery-orders')->name('delivery-orders.store');
            Route::put('delivery-orders/{deliveryOrder}', [DeliveryOrderController::class, 'update'])->middleware('permission:edit-delivery-orders')->name('delivery-orders.update');
            Route::delete('delivery-orders/{deliveryOrder}', [DeliveryOrderController::class, 'destroy'])->middleware('permission:delete-delivery-orders')->name('delivery-orders.destroy');
            Route::put('delivery-orders/{deliveryOrder}/toggle-status', [DeliveryOrderController::class, 'toggleStatus'])->middleware('permission:toggle-status-delivery-orders')->name('delivery-orders.toggle-status');

            Route::put('delivery-orders/{deliveryOrder}/assign-user', [DeliveryOrderController::class, 'assignUser'])->middleware('permission:edit-delivery-orders')->name('delivery-orders.assign-user');

            // Delivery Order Export route
            Route::get('delivery-orders/file/export/', [DeliveryOrderController::class, 'fileExport'])->middleware('permission:export-delivery-orders')->name('delivery-order.export');
        });

        // Return Order routes
        Route::middleware('permission:manage-delivery-orders')->group(function () {
            Route::get('return-orders', [ReturnOrderController::class, 'index'])->middleware('permission:manage-delivery-orders')->name('return-orders.index');
            Route::get('return-orders/{returnOrder}', [ReturnOrderController::class, 'show'])->middleware('permission:view-return-orders')->name('return-orders.show');
            Route::post('return-orders', [ReturnOrderController::class, 'store'])->middleware('permission:create-return-orders')->name('return-orders.store');
            Route::put('return-orders/{returnOrder}', [ReturnOrderController::class, 'update'])->middleware('permission:edit-return-orders')->name('return-orders.update');
            Route::delete('return-orders/{returnOrder}', [ReturnOrderController::class, 'destroy'])->middleware('permission:delete-return-orders')->name('return-orders.destroy');

            // Return Order Export route
            Route::get('return-orders/file/export/', [ReturnOrderController::class, 'fileExport'])->middleware('permission:export-return-orders')->name('return-order.export');
        });

        // Purchase Order routes
        Route::middleware('permission:manage-purchase-orders')->group(function () {
            Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->middleware('permission:manage-purchase-orders')->name('purchase-orders.index');
            Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->middleware('permission:view-purchase-orders')->name('purchase-orders.show');
            Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->middleware('permission:create-purchase-orders')->name('purchase-orders.store');
            Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->middleware('permission:edit-purchase-orders')->name('purchase-orders.update');
            Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->middleware('permission:delete-purchase-orders')->name('purchase-orders.destroy');
            Route::put('purchase-orders/{purchaseOrder}/toggle-status', [PurchaseOrderController::class, 'toggleStatus'])->middleware('permission:toggle-status-purchase-orders')->name('purchase-orders.toggle-status');
            Route::put('purchase-orders/{purchaseOrder}/add-sales-order', [PurchaseOrderController::class, 'addSalesOrder'])->middleware('permission:edit-purchase-orders')->name('purchase-orders.add-sales-order');
            Route::put('purchase-orders/{purchaseOrder}/assign-user', [PurchaseOrderController::class, 'assignUser'])->middleware('permission:edit-purchase-orders')->name('purchase-orders.assign-user');
            Route::post('purchase-orders/{purchaseOrder}/comments', [PurchaseOrderCommentController::class, 'store'])->middleware('permission:create-purchase-orders')->name('purchase-orders.comments.store');
            Route::put('purchase-orders/{purchaseOrder}/activities/{activity}/comment', [PurchaseOrderCommentController::class, 'updateActivity'])->middleware('permission:edit-purchase-orders')->name('purchase-orders.comments.update-activity');
            Route::delete('purchase-orders/{purchaseOrder}/activities', [PurchaseOrderController::class, 'deleteActivities'])->middleware('permission:delete-purchase-orders')->name('purchase-orders.delete-activities');
            Route::delete('purchase-orders/{purchaseOrder}/activities/{activity}', [PurchaseOrderController::class, 'deleteActivity'])->middleware('permission:delete-purchase-orders')->name('purchase-orders.delete-activity');

            // Purchase Order Export route
            Route::get('purchase-orders/file/export/', [PurchaseOrderController::class, 'fileExport'])->middleware('permission:export-purchase-orders')->name('purchase-order.export');
        });

        // Receipt Order routes
        Route::middleware('permission:manage-receipt-orders')->group(function () {
            Route::get('receipt-orders', [ReceiptOrderController::class, 'index'])->middleware('permission:manage-receipt-orders')->name('receipt-orders.index');
            Route::get('receipt-orders/{receiptOrder}', [ReceiptOrderController::class, 'show'])->middleware('permission:view-receipt-orders')->name('receipt-orders.show');
            Route::post('receipt-orders', [ReceiptOrderController::class, 'store'])->middleware('permission:create-receipt-orders')->name('receipt-orders.store');
            Route::put('receipt-orders/{receiptOrder}', [ReceiptOrderController::class, 'update'])->middleware('permission:edit-receipt-orders')->name('receipt-orders.update');
            Route::delete('receipt-orders/{receiptOrder}', [ReceiptOrderController::class, 'destroy'])->middleware('permission:delete-receipt-orders')->name('receipt-orders.destroy');
            Route::put('receipt-orders/{receiptOrder}/toggle-status', [ReceiptOrderController::class, 'toggleStatus'])->middleware('permission:toggle-status-receipt-orders')->name('receipt-orders.toggle-status');

            Route::put('receipt-orders/{receiptOrder}/assign-user', [ReceiptOrderController::class, 'assignUser'])->middleware('permission:edit-receipt-orders')->name('receipt-orders.assign-user');

            // Receipt Order Export route
            Route::get('receipt-orders/file/export/', [ReceiptOrderController::class, 'fileExport'])->middleware('permission:export-receipt-orders')->name('receipt-order.export');
        });

        // Project routes
        Route::middleware('permission:manage-projects')->group(function () {
            Route::get('projects', [ProjectController::class, 'index'])->middleware('permission:manage-projects')->name('projects.index');
            Route::get('projects/{project}', [ProjectController::class, 'show'])->middleware('permission:view-projects')->name('projects.show');
            Route::post('projects', [ProjectController::class, 'store'])->middleware('permission:create-projects')->name('projects.store');
            Route::put('projects/{project}', [ProjectController::class, 'update'])->middleware('permission:edit-projects')->name('projects.update');
            Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->middleware('permission:delete-projects')->name('projects.destroy');
            Route::put('projects/{project}/toggle-status', [ProjectController::class, 'toggleStatus'])->middleware('permission:toggle-status-projects')->name('projects.toggle-status');

            // Project Export route
            Route::get('projects/file/export/', [ProjectController::class, 'fileExport'])->middleware('permission:export-projects')->name('project.export');
        });

        // Project Task routes
        Route::middleware('permission:manage-project-tasks')->group(function () {
            Route::get('project-tasks', [ProjectTaskController::class, 'index'])->middleware('permission:manage-project-tasks')->name('project-tasks.index');
            Route::get('project-tasks/{task}', [ProjectTaskController::class, 'show'])->middleware('permission:view-project-tasks')->name('project-tasks.show');
            Route::post('project-tasks', [ProjectTaskController::class, 'store'])->middleware('permission:create-project-tasks')->name('project-tasks.store');
            Route::put('project-tasks/{task}', [ProjectTaskController::class, 'update'])->middleware('permission:edit-project-tasks')->name('project-tasks.update');
            Route::delete('project-tasks/{task}', [ProjectTaskController::class, 'destroy'])->middleware('permission:delete-project-tasks')->name('project-tasks.destroy');
            Route::put('project-tasks/{task}/toggle-status', [ProjectTaskController::class, 'toggleStatus'])->middleware('permission:toggle-status-project-tasks')->name('project-tasks.toggle-status');
            Route::get('projects/{project}/kanban', [ProjectTaskController::class, 'kanban'])->middleware('permission:view-project-tasks')->name('projects.kanban');
            Route::get('projects/{project}/gantt', [ProjectTaskController::class, 'gantt'])->middleware('permission:view-project-tasks')->name('projects.gantt');
            Route::put('project-tasks/{task}/update-status', [ProjectTaskController::class, 'updateStatus'])->middleware('permission:edit-project-tasks')->name('project-tasks.update-status');
            Route::get('api/project-tasks/parent-tasks/{projectId}', [ProjectTaskController::class, 'getParentTasks'])->name('api.project-tasks.parent-tasks');
            Route::get('api/projects/{projectId}/details', [ProjectTaskController::class, 'getProjectDetails'])->name('api.projects.details');

            // Project Task Export route
            Route::get('project-tasks/file/export/', [ProjectTaskController::class, 'fileExport'])->middleware('permission:export-project-tasks')->name('project-task.export');
        });

        // Task Status routes
        Route::middleware('permission:manage-task-statuses')->group(function () {
            Route::get('task-statuses', [\App\Http\Controllers\TaskStatusController::class, 'index'])->middleware('permission:manage-task-statuses')->name('task-statuses.index');
            Route::post('task-statuses', [\App\Http\Controllers\TaskStatusController::class, 'store'])->middleware('permission:create-task-statuses')->name('task-statuses.store');
            Route::put('task-statuses/{taskStatus}', [\App\Http\Controllers\TaskStatusController::class, 'update'])->middleware('permission:edit-task-statuses')->name('task-statuses.update');
            Route::delete('task-statuses/{taskStatus}', [\App\Http\Controllers\TaskStatusController::class, 'destroy'])->middleware('permission:delete-task-statuses')->name('task-statuses.destroy');
            Route::put('task-statuses/{taskStatus}/toggle-status', [\App\Http\Controllers\TaskStatusController::class, 'toggleStatus'])->middleware('permission:toggle-status-task-statuses')->name('task-statuses.toggle-status');
        });

        // Meeting routes
        Route::middleware('permission:manage-meetings')->group(function () {
            Route::get('meetings', [MeetingController::class, 'index'])->middleware('permission:manage-meetings')->name('meetings.index');
            Route::get('meetings/{meeting}', [MeetingController::class, 'show'])->middleware('permission:view-meetings')->name('meetings.show');
            Route::post('meetings', [MeetingController::class, 'store'])->middleware('permission:create-meetings')->name('meetings.store');
            Route::put('meetings/{meeting}', [MeetingController::class, 'update'])->middleware('permission:edit-meetings')->name('meetings.update');
            Route::delete('meetings/{meeting}', [MeetingController::class, 'destroy'])->middleware('permission:delete-meetings')->name('meetings.destroy');
            Route::put('meetings/{meeting}/toggle-status', [MeetingController::class, 'toggleStatus'])->middleware('permission:toggle-status-meetings')->name('meetings.toggle-status');
            Route::get('api/parent-module/{module}', [MeetingController::class, 'getParentModuleRecords'])->name('api.parent-module.records');
            Route::get('api/attendee-types/{type}', [MeetingController::class, 'getAttendeeRecords'])->name('api.attendee-types.records');
        });

        // Call routes
        Route::middleware('permission:manage-calls')->group(function () {
            Route::get('calls', [CallController::class, 'index'])->middleware('permission:manage-calls')->name('calls.index');
            Route::get('calls/{call}', [CallController::class, 'show'])->middleware('permission:view-calls')->name('calls.show');
            Route::post('calls', [CallController::class, 'store'])->middleware('permission:create-calls')->name('calls.store');
            Route::put('calls/{call}', [CallController::class, 'update'])->middleware('permission:edit-calls')->name('calls.update');
            Route::delete('calls/{call}', [CallController::class, 'destroy'])->middleware('permission:delete-calls')->name('calls.destroy');
            Route::put('calls/{call}/toggle-status', [CallController::class, 'toggleStatus'])->middleware('permission:toggle-status-calls')->name('calls.toggle-status');
            Route::get('api/calls/parent-module/{module}', [CallController::class, 'getParentModuleRecords'])->name('api.calls.parent-module.records');
            Route::get('api/calls/attendee-types/{type}', [CallController::class, 'getAttendeeRecords'])->name('api.calls.attendee-types.records');
        });

        // Calendar route
        Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');

        // Google Calendar API routes
        Route::get('api/google-calendar/events', [\App\Http\Controllers\GoogleCalendarController::class, 'getEvents'])->name('google-calendar.events');
        Route::post('api/google-calendar/sync', [\App\Http\Controllers\GoogleCalendarController::class, 'syncEvents'])->name('google-calendar.sync');
        Route::get('api/google-calendar/status', [\App\Http\Controllers\GoogleCalendarController::class, 'checkStatus'])->name('google-calendar.status');

        // Document Folder management
        Route::middleware('permission:manage-document-folders')->group(function () {
            Route::get('document-folders', [DocumentFolderController::class, 'index'])->middleware('permission:manage-document-folders')->name('document-folders.index');
            Route::get('document-folders/{documentFolder}', [DocumentFolderController::class, 'show'])->middleware('permission:view-document-folders')->name('document-folders.show');
            Route::post('document-folders', [DocumentFolderController::class, 'store'])->middleware('permission:create-document-folders')->name('document-folders.store');
            Route::put('document-folders/{documentFolder}', [DocumentFolderController::class, 'update'])->middleware('permission:edit-document-folders')->name('document-folders.update');
            Route::delete('document-folders/{documentFolder}', [DocumentFolderController::class, 'destroy'])->middleware('permission:delete-document-folders')->name('document-folders.destroy');
            Route::put('document-folders/{documentFolder}/toggle-status', [DocumentFolderController::class, 'toggleStatus'])->middleware('permission:toggle-status-document-folders')->name('document-folders.toggle-status');
        });

        // Streams management
        Route::middleware('permission:manage-stream')->group(function () {
            Route::get('stream', [StreamController::class, 'index'])->middleware('permission:manage-stream')->name('stream.index');

            // Account Activities
            Route::get('stream/account-activities', [StreamController::class, 'accountActivities'])->middleware('permission:view-stream')->name('stream.account-activities');
            Route::delete('stream/account-activities/{id}', [StreamController::class, 'deleteAccountActivity'])->middleware('permission:delete-stream')->name('stream.delete-account-activities');

            // Invoice Activities
            Route::get('stream/invoice-activities', [StreamController::class, 'invoiceActivities'])->middleware('permission:view-stream')->name('stream.invoice-activities');
            Route::delete('stream/invoice-activities/{id}', [StreamController::class, 'deleteInvoiceActivity'])->middleware('permission:delete-stream')->name('stream.delete-invoice-activities');

            // Lead Activities
            Route::get('stream/lead-activities', [StreamController::class, 'leadActivities'])->middleware('permission:view-stream')->name('stream.lead-activities');
            Route::delete('stream/lead-activities/{id}', [StreamController::class, 'deleteLeadActivity'])->middleware('permission:delete-stream')->name('stream.delete-lead-activities');

            // Opportunity Activities
            Route::get('stream/opportunity-activities', [StreamController::class, 'opportunityActivities'])->middleware('permission:view-stream')->name('stream.opportunity-activities');
            Route::delete('stream/opportunity-activities/{id}', [StreamController::class, 'deleteOpportunityActivity'])->middleware('permission:delete-stream')->name('stream.delete-opportunity-activities');

            // Purchase Order Activities
            Route::get('stream/purchase-order-activities', [StreamController::class, 'purchaseOrderActivities'])->middleware('permission:view-stream')->name('stream.purchase-order-activities');
            Route::delete('stream/purchase-order-activities/{id}', [StreamController::class, 'deletePurchaseOrderActivity'])->middleware('permission:delete-stream')->name('stream.delete-purchase-order-activities');

            // Quote Activities
            Route::get('stream/quote-activities', [StreamController::class, 'quoteActivities'])->middleware('permission:view-stream')->name('stream.quote-activities');
            Route::delete('stream/quote-activities/{id}', [StreamController::class, 'deleteQuoteActivity'])->middleware('permission:delete-stream')->name('stream.delete-quote-activities');

            // Sales Order Activities
            Route::get('stream/sales-order-activities', [StreamController::class, 'salesOrderActivities'])->middleware('permission:view-stream')->name('stream.sales-order-activities');
            Route::delete('stream/sales-order-activities/{id}', [StreamController::class, 'deleteSalesOrderActivity'])->middleware('permission:delete-stream')->name('stream.delete-sales-order-activities');
        });

        // Notes routes
        Route::middleware('permission:manage-notes')->group(function () {
            Route::get('notes', [NoteController::class, 'index'])->middleware('permission:manage-notes')->name('notes.index');
            Route::post('notes', [NoteController::class, 'store'])->middleware('permission:create-notes')->name('notes.store');
            Route::put('notes/{note}', [NoteController::class, 'update'])->middleware('permission:edit-notes')->name('notes.update');
            Route::delete('notes/{note}', [NoteController::class, 'destroy'])->middleware('permission:delete-notes')->name('notes.destroy');
        });

        // Announcement Categories routes
        Route::middleware('permission:manage-announcement-categories')->group(function () {
            Route::get('announcement-categories', [\App\Http\Controllers\AnnouncementCategoryController::class, 'index'])->middleware('permission:manage-announcement-categories')->name('announcement-categories.index');
            Route::post('announcement-categories', [\App\Http\Controllers\AnnouncementCategoryController::class, 'store'])->middleware('permission:create-announcement-categories')->name('announcement-categories.store');
            Route::put('announcement-categories/{category}', [\App\Http\Controllers\AnnouncementCategoryController::class, 'update'])->middleware('permission:edit-announcement-categories')->name('announcement-categories.update');
            Route::delete('announcement-categories/{category}', [\App\Http\Controllers\AnnouncementCategoryController::class, 'destroy'])->middleware('permission:delete-announcement-categories')->name('announcement-categories.destroy');
            Route::put('announcement-categories/{category}/toggle-status', [\App\Http\Controllers\AnnouncementCategoryController::class, 'toggleStatus'])->middleware('permission:toggle-status-announcement-categories')->name('announcement-categories.toggle-status');
        });

        // Announcements routes
        Route::middleware('permission:manage-announcements')->group(function () {
            Route::get('announcements/dashboard', [AnnouncementController::class, 'dashboard'])->middleware('permission:manage-announcements')->name('announcements.dashboard');
            Route::get('announcements', [AnnouncementController::class, 'index'])->middleware('permission:manage-announcements')->name('announcements.index');
            Route::get('announcements/{announcement}', [AnnouncementController::class, 'show'])->middleware('permission:view-announcements')->name('announcements.show');
            Route::post('announcements', [AnnouncementController::class, 'store'])->middleware('permission:create-announcements')->name('announcements.store');
            Route::put('announcements/{announcement}', [AnnouncementController::class, 'update'])->middleware('permission:edit-announcements')->name('announcements.update');
            Route::delete('announcements/{announcement}', [AnnouncementController::class, 'destroy'])->middleware('permission:delete-announcements')->name('announcements.destroy');
            Route::put('announcements/{announcement}/toggle-status', [AnnouncementController::class, 'toggleStatus'])->middleware('permission:toggle-status-announcements')->name('announcements.toggle-status');
        });

        // Document Type management
        Route::middleware('permission:manage-document-types')->group(function () {
            Route::get('document-types', [DocumentTypeController::class, 'index'])->middleware('permission:manage-document-types')->name('document-types.index');
            Route::get('document-types/{documentType}', [DocumentTypeController::class, 'show'])->middleware('permission:view-document-types')->name('document-types.show');
            Route::post('document-types', [DocumentTypeController::class, 'store'])->middleware('permission:create-document-types')->name('document-types.store');
            Route::put('document-types/{documentType}', [DocumentTypeController::class, 'update'])->middleware('permission:edit-document-types')->name('document-types.update');
            Route::delete('document-types/{documentType}', [DocumentTypeController::class, 'destroy'])->middleware('permission:delete-document-types')->name('document-types.destroy');
            Route::put('document-types/{documentType}/toggle-status', [DocumentTypeController::class, 'toggleStatus'])->middleware('permission:toggle-status-document-types')->name('document-types.toggle-status');
        });

        // Document management
        Route::middleware('permission:manage-documents')->group(function () {
            Route::get('documents', [DocumentController::class, 'index'])->middleware('permission:manage-documents')->name('documents.index');
            Route::get('documents/{document}', [DocumentController::class, 'show'])->middleware('permission:view-documents')->name('documents.show');
            Route::get('documents/{document}/download', [DocumentController::class, 'download'])->middleware('permission:view-documents')->name('documents.download');
            Route::post('documents', [DocumentController::class, 'store'])->middleware('permission:create-documents')->name('documents.store');
            Route::put('documents/{document}', [DocumentController::class, 'update'])->middleware('permission:edit-documents')->name('documents.update');
            Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->middleware('permission:delete-documents')->name('documents.destroy');
            Route::put('documents/{document}/toggle-status', [DocumentController::class, 'toggleStatus'])->middleware('permission:toggle-status-documents')->name('documents.toggle-status');
        });

        // ChatGPT routes
        Route::post('api/chatgpt/generate', [ChatGptController::class, 'generate'])->name('chatgpt.generate');


        // Language management
        Route::get('manage-language/{lang?}', [LanguageController::class, 'managePage'])->middleware('permission:manage-language')->name('manage-language');
        Route::get('language/load', [LanguageController::class, 'load'])->name('language.load');
        Route::match(['POST', 'PATCH'], 'language/save', [LanguageController::class, 'save'])->middleware('permission:edit-language')->name('language.save');
        Route::post('languages/change', [LanguageController::class, 'changeLanguage'])->name('languages.change');
        Route::post('languages/create', [LanguageController::class, 'createLanguage'])->middleware('App\Http\Middleware\SuperAdminMiddleware')->name('languages.create');
        Route::delete('languages/{languageCode}', [LanguageController::class, 'deleteLanguage'])->middleware('App\Http\Middleware\SuperAdminMiddleware')->name('languages.delete');
        Route::patch('languages/{languageCode}/toggle', [LanguageController::class, 'toggleLanguageStatus'])->middleware('App\Http\Middleware\SuperAdminMiddleware')->name('languages.toggle');


        // Login History routes
        Route::middleware('permission:manage-login-history')->group(function () {
            Route::get('login-history', [LoginHistoryController::class, 'index'])->middleware('permission:show-login-history')->name('login-history.index');
            Route::delete('login-history/{loginDetail}', [LoginHistoryController::class, 'destroy'])->middleware('permission:delete-login-history')->name('login-history.destroy');
        });

        // Landing Page content management (Super Admin only)
        Route::middleware('App\Http\Middleware\SuperAdminMiddleware')->group(function () {
            Route::get('landing-page/settings', [LandingPageController::class, 'settings'])->name('landing-page.settings');
            Route::post('landing-page/settings', [LandingPageController::class, 'updateSettings'])->name('landing-page.settings.update');

            Route::resource('/custom-pages', CustomPageController::class)->names('landing-page.custom-pages');

            // Contact Messages routes
            Route::get('contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
            Route::delete('contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('contact-messages.destroy');

            // Newsletter routes
            Route::get('newsletters', [NewsletterController::class, 'index'])->name('newsletters.index');
            Route::delete('newsletters/{newsletter}', [NewsletterController::class, 'destroy'])->name('newsletters.destroy');

            // Impersonation routes
            Route::get('impersonate/{userId}', [ImpersonateController::class, 'start'])->name('impersonate.start');
        });

        Route::middleware(['role:superadmin|super admin'])->group(function () {
            Route::get('/landing-page', [LandingPageController::class, 'settings'])->name('landing-page');
            // Email Templates routes (no middleware for testing)
            Route::get('email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
            Route::get('email-templates/{emailTemplate}', [EmailTemplateController::class, 'show'])->name('email-templates.show');
            Route::put('email-templates/{emailTemplate}/settings', [EmailTemplateController::class, 'updateSettings'])->name('email-templates.update-settings');
            Route::put('email-templates/{emailTemplate}/content', [EmailTemplateController::class, 'updateContent'])->name('email-templates.update-content');
        });

        Route::post('impersonate/leave', [ImpersonateController::class, 'leave'])->name('impersonate.leave');
    }); // End plan.access middleware group
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

Route::match(['GET', 'POST'], 'payments/easebuzz/success', [EasebuzzPaymentController::class, 'success'])->name('easebuzz.success');
Route::post('payments/easebuzz/callback', [EasebuzzPaymentController::class, 'callback'])->name('easebuzz.callback');

// Public invoice routes (outside authentication)
Route::get('invoices/public/{invoice}', [InvoiceController::class, 'publicView'])->name('invoices.public');
Route::get('invoice-payment/{method}', [InvoiceController::class, 'showPaymentPage'])->name('invoice.payment.page');


// Public quote routes (outside authentication)
Route::get('quotes/public/{quote}', [QuoteController::class, 'publicView'])->name('quotes.public');
Route::get('sales-orders/public/{salesOrder}', [SalesOrderController::class, 'publicView'])->name('sales-orders.public');

Route::post('invoices/payment/stripe', [InvoiceStripePaymentController::class, 'processPayment'])->name('invoice.stripe.payment');
Route::post('invoices/payment/stripe/confirm', [InvoiceStripePaymentController::class, 'confirmPayment'])->name('invoice.stripe.confirm');
Route::post('invoices/payment/paypal', [InvoicePayPalPaymentController::class, 'processPayment'])->name('invoice.paypal.payment');
Route::post('invoices/payment/razorpay/create-order', [InvoiceRazorpayPaymentController::class, 'createOrder'])->name('invoice.razorpay.create-order');
Route::post('invoices/payment/razorpay', [InvoiceRazorpayPaymentController::class, 'processPayment'])->name('invoice.razorpay.payment');
Route::post('invoices/payment/mercadopago/create-preference', [\App\Http\Controllers\InvoiceMercadoPagoPaymentController::class, 'createPreference'])->name('invoice.mercadopago.create-preference');
Route::get('invoices/payment/mercadopago/success', [\App\Http\Controllers\InvoiceMercadoPagoPaymentController::class, 'success'])->name('invoice.mercadopago.success');
Route::get('invoices/payment/mercadopago/failure', [\App\Http\Controllers\InvoiceMercadoPagoPaymentController::class, 'failure'])->name('invoice.mercadopago.failure');
Route::get('invoices/payment/mercadopago/pending', [\App\Http\Controllers\InvoiceMercadoPagoPaymentController::class, 'pending'])->name('invoice.mercadopago.pending');
Route::post('invoices/payment/paystack', [\App\Http\Controllers\InvoicePaystackPaymentController::class, 'processPayment'])->name('invoice.paystack.payment');
Route::post('invoices/payment/flutterwave', [\App\Http\Controllers\InvoiceFlutterwavePaymentController::class, 'processPayment'])->name('invoice.flutterwave.payment');
Route::post('invoices/payment/paytabs', [\App\Http\Controllers\InvoicePayTabsPaymentController::class, 'processPayment'])->name('invoice.paytabs.payment');
Route::get('invoices/payment/paytabs/success', [\App\Http\Controllers\InvoicePayTabsPaymentController::class, 'success'])->name('invoice.paytabs.success');
Route::match(['GET', 'POST'], 'invoices/payment/paytabs/callback', [\App\Http\Controllers\InvoicePayTabsPaymentController::class, 'callback'])->name('invoice.paytabs.callback');
Route::post('invoices/payment/skrill', [\App\Http\Controllers\InvoiceSkrillPaymentController::class, 'processPayment'])->name('invoice.skrill.payment');
Route::post('invoices/payment/skrill/callback', [\App\Http\Controllers\InvoiceSkrillPaymentController::class, 'callback'])->name('invoice.skrill.callback');
Route::post('invoices/payment/coingate', [\App\Http\Controllers\InvoiceCoingatePaymentController::class, 'processPayment'])->name('invoice.coingate.payment');
Route::match(['GET', 'POST'], 'invoices/payment/coingate/callback', [\App\Http\Controllers\InvoiceCoingatePaymentController::class, 'callback'])->name('invoice.coingate.callback');
Route::post('invoices/payment/bank', [InvoiceBankPaymentController::class, 'processPayment'])->name('invoice.bank.payment');
Route::post('invoices/payment/benefit', [InvoiceBenefitPaymentController::class, 'processPayment'])->name('invoice.benefit.payment');
Route::get('invoices/payment/benefit/success', [InvoiceBenefitPaymentController::class, 'success'])->name('invoice.benefit.success');
Route::post('invoices/payment/benefit/callback', [InvoiceBenefitPaymentController::class, 'callback'])->name('invoice.benefit.callback');
Route::post('invoices/payment/payfast', [InvoicePayfastPaymentController::class, 'processPayment'])->name('invoice.payfast.payment');
Route::get('invoices/payment/payfast/success', [InvoicePayfastPaymentController::class, 'success'])->name('invoice.payfast.success');
Route::post('invoices/payment/payfast/callback', [InvoicePayfastPaymentController::class, 'callback'])->name('invoice.payfast.callback');
Route::post('invoices/payment/tap', [InvoiceTapPaymentController::class, 'processPayment'])->name('invoice.tap.payment');
Route::get('invoices/payment/tap/success', [InvoiceTapPaymentController::class, 'success'])->name('invoice.tap.success');
Route::post('invoices/payment/tap/callback', [InvoiceTapPaymentController::class, 'callback'])->name('invoice.tap.callback');
Route::post('invoices/payment/xendit', [InvoiceXenditPaymentController::class, 'createPayment'])->name('invoice.xendit.payment');
Route::get('invoices/payment/xendit/success', [InvoiceXenditPaymentController::class, 'success'])->name('invoice.xendit.success');
Route::post('invoices/payment/xendit/callback', [InvoiceXenditPaymentController::class, 'callback'])->name('invoice.xendit.callback');
Route::post('invoices/payment/paytr/create-token', [InvoicePayTRPaymentController::class, 'createPaymentToken'])->name('invoice.paytr.create-token');
Route::get('invoices/payment/paytr/success', [InvoicePayTRPaymentController::class, 'success'])->name('invoice.paytr.success');
Route::get('invoices/payment/paytr/failure', [InvoicePayTRPaymentController::class, 'failure'])->name('invoice.paytr.failure');
Route::post('invoices/payment/paytr/callback', [InvoicePayTRPaymentController::class, 'callback'])->name('invoice.paytr.callback');
Route::post('invoices/payment/mollie', [InvoiceMolliePaymentController::class, 'processPayment'])->name('invoice.mollie.payment');
Route::get('invoices/payment/mollie/success', [InvoiceMolliePaymentController::class, 'success'])->name('invoice.mollie.success');
Route::post('invoices/payment/mollie/callback', [InvoiceMolliePaymentController::class, 'callback'])->name('invoice.mollie.callback');
Route::post('invoices/payment/toyyibpay', [InvoiceToyyibPayPaymentController::class, 'processPayment'])->name('invoice.toyyibpay.payment');
Route::match(['GET', 'POST'], 'invoices/payment/toyyibpay/success', [InvoiceToyyibPayPaymentController::class, 'success'])->name('invoice.toyyibpay.success');
Route::post('invoices/payment/toyyibpay/callback', [InvoiceToyyibPayPaymentController::class, 'callback'])->name('invoice.toyyibpay.callback');
Route::post('invoices/payment/paymentwall/create', [InvoicePaymentWallPaymentController::class, 'createPayment'])->name('invoice.paymentwall.create');
Route::post('invoices/payment/paymentwall/process', [InvoicePaymentWallPaymentController::class, 'processPayment'])->name('invoice.paymentwall.process');
Route::match(['GET', 'POST'], 'invoices/payment/paymentwall/callback', [InvoicePaymentWallPaymentController::class, 'callback'])->name('invoice.paymentwall.callback');
Route::post('invoices/payment/sspay/create', [InvoiceSSPayPaymentController::class, 'createPayment'])->name('invoice.sspay.create');
Route::get('invoices/payment/sspay/success', [InvoiceSSPayPaymentController::class, 'success'])->name('invoice.sspay.success');
Route::post('invoices/payment/sspay/callback', [InvoiceSSPayPaymentController::class, 'callback'])->name('invoice.sspay.callback');
Route::post('invoices/payment/iyzipay/create-form', [InvoiceIyzipayPaymentController::class, 'createPaymentForm'])->name('invoice.iyzipay.create-form');
Route::post('invoices/payment/aamarpay/create', [InvoiceAamarpayPaymentController::class, 'createPayment'])->name('invoice.aamarpay.create');
Route::match(['GET', 'POST'], 'invoices/payment/aamarpay/success', [InvoiceAamarpayPaymentController::class, 'success'])->name('invoice.aamarpay.success');
Route::post('invoices/payment/aamarpay/callback', [InvoiceAamarpayPaymentController::class, 'callback'])->name('invoice.aamarpay.callback');
Route::post('invoices/payment/midtrans/create', [InvoiceMidtransPaymentController::class, 'createPayment'])->name('invoice.midtrans.create');
Route::match(['GET', 'POST'], 'invoices/payment/midtrans/success', [InvoiceMidtransPaymentController::class, 'success'])->name('invoice.midtrans.success');
Route::post('invoices/payment/midtrans/callback', [InvoiceMidtransPaymentController::class, 'callback'])->name('invoice.midtrans.callback');
Route::post('invoices/payment/yookassa/create-payment', [InvoiceYooKassaPaymentController::class, 'createPayment'])->name('invoice.yookassa.create-payment');
Route::get('invoices/payment/yookassa/success', [InvoiceYooKassaPaymentController::class, 'success'])->name('invoice.yookassa.success');
Route::post('invoices/payment/yookassa/callback', [InvoiceYooKassaPaymentController::class, 'callback'])->name('invoice.yookassa.callback');
Route::post('invoices/payment/paiement/create-payment', [InvoicePaiementPaymentController::class, 'createPayment'])->name('invoice.paiement.create-payment');
Route::get('invoices/payment/paiement/success', [InvoicePaiementPaymentController::class, 'success'])->name('invoice.paiement.success');
Route::post('invoices/payment/paiement/callback', [InvoicePaiementPaymentController::class, 'callback'])->name('invoice.paiement.callback');
Route::post('invoices/payment/cinetpay/create-payment', [InvoiceCinetPayPaymentController::class, 'createPayment'])->name('invoice.cinetpay.create-payment');
Route::get('invoices/payment/cinetpay/success', [InvoiceCinetPayPaymentController::class, 'success'])->name('invoice.cinetpay.success');
Route::post('invoices/payment/cinetpay/callback', [InvoiceCinetPayPaymentController::class, 'callback'])->name('invoice.cinetpay.callback');
Route::post('invoices/payment/payhere/create-payment', [InvoicePayHerePaymentController::class, 'createPayment'])->name('invoice.payhere.create-payment');
Route::get('invoices/payment/payhere/success', [InvoicePayHerePaymentController::class, 'success'])->name('invoice.payhere.success');
Route::post('invoices/payment/payhere/callback', [InvoicePayHerePaymentController::class, 'callback'])->name('invoice.payhere.callback');
Route::post('invoices/payment/fedapay/create-payment', [InvoiceFedaPayPaymentController::class, 'createPayment'])->name('invoice.fedapay.create-payment');
Route::match(['GET', 'POST'], 'invoices/payment/fedapay/callback', [InvoiceFedaPayPaymentController::class, 'callback'])->name('invoice.fedapay.callback');
Route::post('invoices/payment/authorizenet', [InvoiceAuthorizeNetPaymentController::class, 'processPayment'])->name('invoice.authorizenet.payment');
Route::post('invoices/payment/khalti/create-payment', [InvoiceKhaltiPaymentController::class, 'createPayment'])->name('invoice.khalti.create-payment');
Route::post('invoices/payment/khalti', [InvoiceKhaltiPaymentController::class, 'processPayment'])->name('invoice.khalti.payment');
Route::post('invoices/payment/easebuzz/create-payment', [InvoiceEasebuzzPaymentController::class, 'createPayment'])->name('invoice.easebuzz.create-payment');
Route::match(['GET', 'POST'], 'invoices/payment/easebuzz/success', [InvoiceEasebuzzPaymentController::class, 'success'])->name('invoice.easebuzz.success');
Route::match(['GET', 'POST'], 'invoices/payment/easebuzz/failure', [InvoiceEasebuzzPaymentController::class, 'failure'])->name('invoice.easebuzz.failure');
Route::post('invoices/payment/easebuzz/callback', [InvoiceEasebuzzPaymentController::class, 'callback'])->name('invoice.easebuzz.callback');
Route::post('invoices/payment/ozow/create-payment', [InvoiceOzowPaymentController::class, 'createPayment'])->name('invoice.ozow.create-payment');
Route::get('invoices/payment/ozow/success', [InvoiceOzowPaymentController::class, 'success'])->name('invoice.ozow.success');
Route::post('invoices/payment/ozow/callback', [InvoiceOzowPaymentController::class, 'callback'])->name('invoice.ozow.callback');
Route::post('invoices/payment/cashfree/create-session', [\App\Http\Controllers\InvoiceCashfreePaymentController::class, 'createPaymentSession'])->name('invoice.cashfree.create-session');
Route::post('invoices/payment/cashfree/verify-payment', [\App\Http\Controllers\InvoiceCashfreePaymentController::class, 'verifyPayment'])->name('invoice.cashfree.verify-payment');
Route::post('invoices/payment/cashfree/webhook', [\App\Http\Controllers\InvoiceCashfreePaymentController::class, 'webhook'])->name('invoice.cashfree.webhook')->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
Route::post('products/import-from-excel', [ProductController::class, 'importFromExcel'])->name('products.import-from-excel');
// Invoice payment management routes (authenticated)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('invoices/payments/{paymentId}/approve', [InvoiceController::class, 'approvePayment'])->name('invoice.payments.approve');
    Route::post('invoices/payments/{paymentId}/reject', [InvoiceController::class, 'rejectPayment'])->name('invoice.payments.reject');
});

// Cookie consent routes
Route::post('/cookie-consent/store', [CookieConsentController::class, 'store'])->name('cookie.consent.store');
Route::get('/cookie-consent/download', [CookieConsentController::class, 'download'])->name('cookie.consent.download');

// Invoice template preview route (authenticated)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('invoices/preview/{templateId}/{color}', [InvoiceController::class, 'previewTemplate'])->name('invoice.preview');
});


Route::prefix('api')->group(function () {

    // جلب قائمة المنتجات لشركة معينة
    // GET /api/{slug}/vital-products
    Route::get('/{slug}/vital-products/list', [ProductApiController::class, 'vitalProductList']);

    // جلب تفاصيل منتج واحد
    // GET /api/{slug}/products/{productId}
    Route::get('/{slug}/vital-products/{productId}', [ProductApiController::class, 'vitalProductShow']);

    // جلب قائمة التاجات لشركة معينة
    // GET /api/{slug}/tags
    Route::get('/{slug}/tags', [ProductApiController::class, 'vitalTagsList']);

    // جلب معلومات الشركة العامة
    // GET /api/{slug}/info
    Route::get('/{slug}/info', [ProductApiController::class, 'vitalCompanyInfo']);
});
