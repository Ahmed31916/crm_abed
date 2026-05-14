<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyPaymentSettingController extends Controller
{
    public function index()
    {
        $paymentSettings = getPaymentSettings();
        
        return Inertia::render('settings/index', [
            'paymentSettings' => $paymentSettings,
        ]);
    }

    public function store(Request $request)
    {
        // Use the same validation and logic as PaymentSettingController
        $paymentController = new \App\Http\Controllers\Settings\PaymentSettingController();
        return $paymentController->store($request);
    }

    public function getCompanyPaymentMethods()
    {
        $paymentSettings = getPaymentSettings();
        
        // Use the same filtering logic as PaymentSettingController
        $paymentController = new \App\Http\Controllers\Settings\PaymentSettingController();
        $reflection = new \ReflectionClass($paymentController);
        $method = $reflection->getMethod('filterSensitiveData');
        $method->setAccessible(true);
        $safeSettings = $method->invoke($paymentController, $paymentSettings);
        
        return response()->json($safeSettings);
    }
}