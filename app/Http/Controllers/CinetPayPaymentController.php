<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CinetPayPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = validatePaymentRequest($request, [
            'cpm_trans_id' => 'required|string',
            'cpm_result' => 'required|string',
        ]);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['cinetpay_site_id'])) {
                return back()->withErrors(['error' => __('CinetPay not configured')]);
            }

            if ($validated['cpm_result'] === '00') { // Success status
                processPaymentSuccess([
                    'user_id' => auth()->id(),
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'payment_method' => 'cinetpay',
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'payment_id' => $validated['cpm_trans_id'],
                ]);

                return back()->with('success', __('Payment successful and plan activated'));
            }

            return back()->withErrors(['error' => __('Payment failed or cancelled')]);

        } catch (\Exception $e) {
            return handlePaymentError($e, 'cinetpay');
        }
    }

    public function createPayment(Request $request)
    {
        $validated = validatePaymentRequest($request);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null,$request->billing_cycle);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['cinetpay_site_id']) || !isset($settings['payment_settings']['cinetpay_api_key'])) {
                return response()->json(['error' => __('CinetPay not configured')], 400);
            }

            $user = auth()->user();
            $transactionId = 'plan_' . $plan->id . '_' . $user->id . '_' . time();

            // Prepare API request data according to CinetPay v2 API
            $apiData = [
                'apikey' => $settings['payment_settings']['cinetpay_api_key'],
                'site_id' => $settings['payment_settings']['cinetpay_site_id'],
                'transaction_id' => $transactionId,
                // 'amount' => 100,
                'amount' => (int) ($pricing['final_price']),
                'currency' => 'XOF',
                'description' => 'Plan subscription: ' . $plan->name,
                'notify_url' => route('cinetpay.callback'),
                'return_url' => route('cinetpay.success'),
                'channels' => 'ALL',
                'metadata' => json_encode([
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'coupon_code' => $validated['coupon_code'] ?? null,
                ]),
                'customer_name' => $user->name ?? 'Customer',
                'customer_surname' => $user->last_name ?? 'User',
                'customer_email' => $user->email,
                'customer_phone_number' => $user->phone ?? '+2250000000000',
                'customer_address' => $user->address ?? 'Abidjan',
                'customer_city' => $user->city ?? 'Abidjan',
                'customer_country' => 'CI',
                'customer_state' => 'CI',
                'customer_zip_code' => $user->zip_code ?? '00000',
            ];

            // Make API call to CinetPay
            $response = $this->callCinetPayAPI($apiData);

            if ($response && isset($response['code']) && $response['code'] === '201') {
                return response()->json([
                    'success' => true,
                    'payment_url' => $response['data']['payment_url'],
                    'payment_token' => $response['data']['payment_token'],
                    'transaction_id' => $transactionId
                ]);
            }

            return response()->json([
                'error' => $response['message'] ?? __('Payment creation failed')
            ], 400);

        } catch (\Exception $e) {
            Log::error('CinetPay payment creation error: ' . $e->getMessage());
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    private function callCinetPayAPI($data)
    {
        $url = 'https://api-checkout.cinetpay.com/v2/payment';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: SalesyCRM/1.0'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            Log::error('CinetPay cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            Log::error('CinetPay API HTTP error: ' . $httpCode . ' Response: ' . $response);
        }

        return json_decode($response, true);
    }

    public function success(Request $request)
    {
        return redirect()->route('plans.index')->with('success', __('Payment completed successfully'));
    }

    public function callback(Request $request)
    {
        try {
            $transactionId = $request->input('cpm_trans_id');
            $result = $request->input('cpm_result');

            if ($transactionId && $result === '00') {
                $parts = explode('_', $transactionId);

                if (count($parts) >= 3) {
                    $planId = $parts[1];
                    $userId = $parts[2];

                    $plan = Plan::find($planId);
                    $user = User::find($userId);

                    if ($plan && $user) {
                        $customData = json_decode($request->input('cpm_custom'), true);

                        processPaymentSuccess([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'billing_cycle' => $customData['billing_cycle'] ?? 'monthly',
                            'payment_method' => 'cinetpay',
                            'payment_id' => $transactionId,
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            return response()->json(['error' => __('Callback processing failed')], 500);
        }
    }
}
