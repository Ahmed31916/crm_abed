<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

class PayHerePaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = validatePaymentRequest($request, [
            'payment_id' => 'required|string',
            'status_code' => 'required|string',
            'plan_id' => 'required|integer',
            'billing_cycle' => 'required|string',
        ]);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['payhere_merchant_id'])) {
                return back()->withErrors(['error' => __('PayHere not configured')]);
            }

            if ($validated['status_code'] === '2') { // Success status
                processPaymentSuccess([
                    'user_id' => auth()->id(),
                    'plan_id' => $plan->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'payment_method' => 'payhere',
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'payment_id' => $validated['payment_id'],
                ]);

                return back()->with('success', __('Payment successful and plan activated'));
            }

            return back()->withErrors(['error' => __('Payment failed or cancelled')]);

        } catch (\Exception $e) {
            return handlePaymentError($e, 'payhere');
        }
    }

    public function createPayment(Request $request)
    {
        $validated = validatePaymentRequest($request);

        try {
            $plan = Plan::findOrFail($validated['plan_id']);
            $pricing = calculatePlanPricing($plan, $validated['coupon_code'] ?? null,$validated['billing_cycle']);
            $settings = getPaymentGatewaySettings();

            if (!isset($settings['payment_settings']['payhere_merchant_id'])) {
                return response()->json(['error' => __('PayHere not configured')], 400);
            }

            $user = auth()->user();
            $orderId = 'plan_' . $plan->id . '_' . $user->id . '_' . time();

            $paymentData = [
                'merchant_id' => $settings['payment_settings']['payhere_merchant_id'],
                'return_url' => route('payhere.success', [
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                    'billing_cycle' => $validated['billing_cycle'],
                    'coupon_code' => $validated['coupon_code'] ?? ''
                ]),
                'cancel_url' => route('plans.index'),
                'notify_url' => route('payhere.callback'),
                'order_id' => $orderId,
                'items' => $plan->name,
                'currency' => 'LKR',
                'amount' => number_format($pricing['final_price'], 2, '.', ''),
                'first_name' => $user->name ?? 'Customer',
                'last_name' => 'User',
                'email' => $user->email,
                'phone' => '0771234567',
                'address' => 'No.1, Galle Road',
                'city' => 'Colombo',
                'country' => 'Sri Lanka',
            ];

            // Generate hash
            $hashString = strtoupper(
                md5(
                    $paymentData['merchant_id'] .
                    $paymentData['order_id'] .
                    number_format($paymentData['amount'], 2, '.', '') .
                    $paymentData['currency'] .
                    strtoupper(md5($settings['payment_settings']['payhere_merchant_secret']))
                )
            );
            $paymentData['hash'] = $hashString;

            $baseUrl = $settings['payment_settings']['payhere_mode'] === 'live'
                ? 'https://www.payhere.lk'
                : 'https://sandbox.payhere.lk';

            return response()->json([
                'success' => true,
                'payment_url' => $baseUrl . '/pay/checkout',
                'payment_data' => $paymentData,
                'order_id' => $orderId
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => __('Payment creation failed')], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $planId = $request->input('plan_id');
            $userId = $request->input('user_id');
            $billingCycle = $request->input('billing_cycle', 'monthly');
            $couponCode = $request->input('coupon_code');
            $paymentId = $request->input('order_id');

            if ($planId && $userId && $paymentId) {
                $plan = Plan::find($planId);
                $user = User::find($userId);

                if ($plan && $user) {
                    processPaymentSuccess([
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'billing_cycle' => $billingCycle,
                        'payment_method' => 'payhere',
                        'coupon_code' => $couponCode,
                        'payment_id' => $paymentId,
                    ]);

                    // Log the user in if not already authenticated
                    if (!auth()->check()) {
                        auth()->login($user);
                    }

                    return redirect()->route('plans.index')->with('success', __('Payment completed successfully and plan activated'));
                }
            }

            return redirect()->route('plans.index')->with('error', __('Payment verification failed'));

        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', __('Payment processing failed'));
        }
    }

    public function callback(Request $request)
    {
        try {
            $orderId = $request->input('order_id');
            $statusCode = $request->input('status_code');

            if ($orderId && $statusCode === '2') {
                $parts = explode('_', $orderId);

                if (count($parts) >= 3) {
                    $planId = $parts[1];
                    $userId = $parts[2];

                    $plan = Plan::find($planId);
                    $user = User::find($userId);

                    if ($plan && $user) {
                        // Extract billing cycle from order_id if available, otherwise default to monthly
                        $billingCycle = 'monthly';
                        if (count($parts) >= 4) {
                            $billingCycle = $parts[3] ?? 'monthly';
                        }

                        processPaymentSuccess([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'billing_cycle' => $billingCycle,
                            'payment_method' => 'payhere',
                            'payment_id' => $request->input('payment_id'),
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
