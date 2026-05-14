import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tag, Loader2 } from 'lucide-react';
import { toast } from '@/components/custom-toast';
import { StripePaymentForm } from './stripe-payment-form';
import { PayPalPaymentForm } from './paypal-payment-form';

import { BankTransferForm } from './bank-transfer-form';
import { RazorpayPaymentForm } from './razorpay-payment-form';
import { MercadoPagoPaymentForm } from './mercadopago-payment-form';
import { PaystackPaymentForm } from './paystack-payment-form';
import { FlutterwavePaymentForm } from './flutterwave-payment-form';
import { PayTabsPaymentForm } from './paytabs-payment-form';
import { SkrillPaymentForm } from './skrill-payment-form';
import { CoinGatePaymentForm } from './coingate-payment-form';
import { PayfastPaymentForm } from './payfast-payment-form';
import { ToyyibPayPaymentForm } from './toyyibpay-payment-form';
import { PayTRPaymentForm } from './paytr-payment-form';
import { MolliePaymentForm } from './mollie-payment-form';
import { CashfreePaymentForm } from './cashfree-payment-form';
import { IyzipayPaymentForm } from './iyzipay-payment-form';
import { BenefitPaymentForm } from './benefit-payment-form';
import { OzowPaymentForm } from './ozow-payment-form';
import { EasebuzzPaymentForm } from './easebuzz-payment-form';
import { KhaltiPaymentForm } from './khalti-payment-form';
import { AuthorizeNetPaymentForm } from './authorizenet-payment-form';
import { FedaPayPaymentForm } from './fedapay-payment-form';
import { PayHerePaymentForm } from './payhere-payment-form';
import { CinetPayPaymentForm } from './cinetpay-payment-form';
import { PaiementPaymentForm } from './paiement-payment-form';
import { NepalstePaymentForm } from './nepalste-payment-form';
import { YooKassaPaymentForm } from './yookassa-payment-form';
import { AamarpayPaymentForm } from './aamarpay-payment-form';
import { MidtransPaymentForm } from './midtrans-payment-form';
import { PaymentWallPaymentForm } from './paymentwall-payment-form';
import { SSPayPaymentForm } from './sspay-payment-form';
import { TapPaymentForm } from './tap-payment-form';
import { XenditPaymentForm } from './xendit-payment-form';
import { router } from '@inertiajs/react';
import axios from 'axios';

interface PaymentMethod {
    id: string;
    name: string;
    icon: React.ReactNode;
    enabled: boolean;
    currency?: string;
    currencySymbol?: string;
}

interface PaymentProcessorProps {
    plan: {
        id: number;
        name: string;
        price: string | number;
        duration: string;
        paymentMethods?: any;
    };
    billingCycle: 'monthly' | 'yearly';
    paymentMethods: PaymentMethod[];
    currencySymbol?: string;
    onSuccess: () => void;
    onCancel: () => void;
}

export function PaymentProcessor({
    plan,
    billingCycle,
    paymentMethods,
    currencySymbol = '$',
    onSuccess,
    onCancel
}: PaymentProcessorProps) {
    const { t } = useTranslation();
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState<string>('');
    const [couponCode, setCouponCode] = useState('');
    const [appliedCoupon, setAppliedCoupon] = useState<any>(null);
    const [couponLoading, setCouponLoading] = useState(false);
    const [showPaymentForm, setShowPaymentForm] = useState(false);

    const [currentPrice, setCurrentPrice] = useState(Number(plan.price));

    useEffect(() => {
        // Use the plan price directly as it should already be correct for the billing cycle
        setCurrentPrice(Number(plan.price));
    }, [plan.price]);

    const originalPrice = currentPrice;
    const discountAmount = appliedCoupon ? (appliedCoupon.type === 'percentage' ? (originalPrice * appliedCoupon.value / 100) : appliedCoupon.value) : 0;
    const finalPrice = Math.max(0, originalPrice - discountAmount);

    const handleApplyCoupon = async () => {
        if (!couponCode.trim()) {
            toast.error(t('Please enter a coupon code'));
            return;
        }

        setCouponLoading(true);
        try {
            const { data } = await axios.post(route('coupons.validate'), {
                coupon_code: couponCode,
                plan_id: plan.id,
                amount: originalPrice
            });

            if (data.valid) {
                setAppliedCoupon(data.coupon);
                toast.success(t(data.message || 'Coupon applied successfully'));
            } else {
                toast.error(t(data.message || 'Invalid coupon code'));
                setAppliedCoupon(null);
            }
        } catch (error: any) {
            toast.error(t('Failed to validate coupon'));
            setAppliedCoupon(null);
        } finally {
            setCouponLoading(false);
        }
    };

    const handleRemoveCoupon = () => {
        setAppliedCoupon(null);
        setCouponCode('');
    };

    const handlePayNow = () => {
        if (finalPrice == 0) {
            handleConfirmZeroPayment();
            return;
        }
        if (!selectedPaymentMethod) {
            toast.error(t('Please select a payment method'));
            return;
        }
        setShowPaymentForm(true);
    };

    const handlePaymentCancel = () => {
        setShowPaymentForm(false);
        setSelectedPaymentMethod('');
    };

    const [processing, setProcessing] = useState(false);

    const handleConfirmZeroPayment = () => {
        setProcessing(true);

        router.post(route('zero.payment'), {
            plan_id: plan.id,
            billing_cycle: billingCycle,
            coupon_code: couponCode,
            amount: finalPrice,
        }, {
            onSuccess: () => {
                toast.success(t('Payment request submitted successfully'));
                onSuccess();
            },
            onError: () => {
                toast.error(t('Failed to submit payment request'));
            },
            onFinish: () => {
                setProcessing(false);
            }
        });
    };

    const enabledPaymentMethods = paymentMethods.filter(method => method.enabled);

    const renderPaymentForm = () => {
        const commonProps = {
            planId: plan.id,
            couponCode,
            billingCycle,
            onSuccess,
            onCancel: handlePaymentCancel,
        };

        switch (selectedPaymentMethod) {
            case 'stripe':
                return (
                    <StripePaymentForm
                        {...commonProps}
                        amount={finalPrice}
                        stripeKey={plan.paymentMethods?.stripe_key || ''}
                    />
                );

            case 'paypal':
                return (
                    <PayPalPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        paypalClientId={plan.paymentMethods?.paypal_client_id || ''}
                        currency={plan.paymentMethods?.currency || 'USD'}
                    />
                );
            case 'bank':
                return (
                    <BankTransferForm
                        {...commonProps}
                        planPrice={finalPrice}
                        bankDetails={plan.paymentMethods?.bank_detail || ''}
                    />
                );
            case 'razorpay':
                return (
                    <RazorpayPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        razorpayKey={plan.paymentMethods?.razorpay_key || ''}
                        currency={plan.paymentMethods?.currency || 'INR'}
                    />
                );
            case 'mercadopago':
                return (
                    <MercadoPagoPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        accessToken={plan.paymentMethods?.mercadopago_access_token || ''}
                        currency={plan.paymentMethods?.currency || 'BRL'}
                    />
                );
            case 'paystack':
                return (
                    <PaystackPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        paystackKey={plan.paymentMethods?.paystack_public_key || ''}
                        currency={plan.paymentMethods?.currency || 'NGN'}
                    />
                );
            case 'flutterwave':
                return (
                    <FlutterwavePaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        flutterwaveKey={plan.paymentMethods?.flutterwave_public_key || ''}
                        currency={plan.paymentMethods?.currency || 'NGN'}
                    />
                );
            case 'paytabs':
                return (
                    <PayTabsPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        paytabsClientKey={''}
                        currency={plan.paymentMethods?.currency || 'USD'}
                    />
                );
            case 'skrill':
                return (
                    <SkrillPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        skrillMerchantId={plan.paymentMethods?.skrill_merchant_id || ''}
                        currency={plan.paymentMethods?.currency || 'USD'}
                    />
                );
            case 'coingate':
                return (
                    <CoinGatePaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        coinGateApiToken={plan.paymentMethods?.coingate_api_token || ''}
                        currency={plan.paymentMethods?.currency || 'USD'}
                    />
                );
            case 'payfast':
                return (
                    <PayfastPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        payfastMerchantId={plan.paymentMethods?.payfast_merchant_id || ''}
                        currency={plan.paymentMethods?.currency || 'ZAR'}
                    />
                );
            case 'toyyibpay':
                return (
                    <ToyyibPayPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        toyyibpayCategoryCode={plan.paymentMethods?.toyyibpay_category_code || ''}
                        currency={plan.paymentMethods?.currency || 'MYR'}
                    />
                );
            case 'paytr':
                return (
                    <PayTRPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        paytrMerchantId={plan.paymentMethods?.paytr_merchant_id || ''}
                        currency={plan.paymentMethods?.currency || 'TRY'}
                    />
                );
            case 'mollie':
                return (
                    <MolliePaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        mollieApiKey={plan.paymentMethods?.mollie_api_key || ''}
                        currency={plan.paymentMethods?.currency || 'EUR'}
                    />
                );
            case 'cashfree':
                return (
                    <CashfreePaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        cashfreeAppId={plan.paymentMethods?.cashfree_public_key || ''}
                        mode={plan.paymentMethods?.cashfree_mode || 'sandbox'}
                        currency={plan.paymentMethods?.currency || 'INR'}
                    />
                );
            case 'iyzipay':
                return (
                    <IyzipayPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        iyzipayPublicKey={plan.paymentMethods?.iyzipay_public_key || ''}
                        currency={plan.paymentMethods?.currency || 'USD'}
                    />
                );
            case 'benefit':
                return (
                    <BenefitPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        benefitPublicKey={plan.paymentMethods?.benefit_public_key || ''}
                        currency={plan.paymentMethods?.currency || 'BHD'}
                    />
                );
            case 'ozow':
                return (
                    <OzowPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        ozowSiteKey={plan.paymentMethods?.ozow_site_key || ''}
                        currency={plan.paymentMethods?.currency || 'ZAR'}
                    />
                );
            case 'easebuzz':
                return (
                    <EasebuzzPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        easebuzzMerchantKey={plan.paymentMethods?.easebuzz_merchant_key || ''}
                        currency={plan.paymentMethods?.currency || 'INR'}
                    />
                );
            case 'khalti':
                return (
                    <KhaltiPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        khaltiPublicKey={plan.paymentMethods?.khalti_public_key || ''}
                        currency={plan.paymentMethods?.currency || 'NPR'}
                    />
                );
            case 'authorizenet':
                return (
                    <AuthorizeNetPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        authorizenetMerchantId={plan.paymentMethods?.authorizenet_merchant_id || ''}
                        currency={plan.paymentMethods?.currency || 'USD'}
                    />
                );
            case 'fedapay':
                return (
                    <FedaPayPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        fedapayPublicKey={plan.paymentMethods?.fedapay_public_key || ''}
                        currency={plan.paymentMethods?.currency || 'XOF'}
                    />
                );
            case 'payhere':
                return (
                    <PayHerePaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        payhereMerchantId={plan.paymentMethods?.payhere_merchant_id || ''}
                        currency={plan.paymentMethods?.currency || 'LKR'}
                    />
                );
            case 'cinetpay':
                return (
                    <CinetPayPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        cinetpaySiteId={plan.paymentMethods?.cinetpay_site_id || ''}
                        currency={plan.paymentMethods?.currency || 'XOF'}
                    />
                );
            case 'paiement':
                return (
                    <PaiementPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        paiementMerchantId={plan.paymentMethods?.paiement_merchant_id || ''}
                        currency={plan.paymentMethods?.currency || 'XOF'}
                    />
                );
            case 'nepalste':
                return (
                    <NepalstePaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        nepalstePublicKey={plan.paymentMethods?.nepalste_public_key || ''}
                        currency={plan.paymentMethods?.currency || 'NPR'}
                    />
                );
            case 'yookassa':
                return (
                    <YooKassaPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        yookassaShopId={plan.paymentMethods?.yookassa_shop_id || ''}
                        currency={plan.paymentMethods?.currency || 'RUB'}
                    />
                );
            case 'aamarpay':
                return (
                    <AamarpayPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        aamarpayStoreId={plan.paymentMethods?.aamarpay_store_id || ''}
                        currency={plan.paymentMethods?.currency || 'BDT'}
                    />
                );
            case 'midtrans':
                return (
                    <MidtransPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        midtransSecretKey={plan.paymentMethods?.midtrans_secret_key || ''}
                        currency={plan.paymentMethods?.currency || 'IDR'}
                    />
                );
            case 'paymentwall':
                return (
                    <PaymentWallPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        paymentwallPublicKey={plan.paymentMethods?.paymentwall_public_key || ''}
                        currency={plan.paymentMethods?.currency || 'USD'}
                    />
                );
            case 'sspay':
                return (
                    <SSPayPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        sspaySecretKey={plan.paymentMethods?.sspay_secret_key || ''}
                        currency={plan.paymentMethods?.currency || 'MYR'}
                    />
                );
            case 'tap':
                return (
                    <TapPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        tapSecretKey={plan.paymentMethods?.tap_secret_key || ''}
                        currency={plan.paymentMethods?.currency || 'USD'}
                    />
                );
            case 'xendit':
                return (
                    <XenditPaymentForm
                        {...commonProps}
                        planPrice={finalPrice}
                        xenditApiKey={plan.paymentMethods?.xendit_api_key || ''}
                        currency={plan.paymentMethods?.currency || 'PHP'}
                    />
                );
            default:
                return null;
        }
    };

    if (showPaymentForm) {
        return (
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h3 className="font-medium">{t('Complete Payment')}</h3>
                    <Button variant="outline" size="sm" onClick={handlePaymentCancel}>
                        {t('Back')}
                    </Button>
                </div>
                {renderPaymentForm()}
            </div>
        );
    }
    return (
        <div className="space-y-6">
            {/* Plan Summary */}
            <Card>
                <CardContent className="p-4">
                    <div className="flex justify-between items-center">
                        <div>
                            <h3 className="font-medium">{plan.name}</h3>
                            <p className="text-sm text-muted-foreground">
                                {t(billingCycle)} {t('subscription')}
                            </p>
                        </div>
                        <div className="text-right">
                            <div className="text-lg font-bold">{currencySymbol} {plan.price}</div>
                            <div className="text-sm text-muted-foreground">
                                /{t(billingCycle)}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Payment Methods */}
            <div className="space-y-3">
                {finalPrice != 0 && <Label>{t('Select Payment Method')}</Label>}
                {enabledPaymentMethods.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t('No payment methods available')}
                    </p>
                ) : (
                    finalPrice != 0 && <div className="space-y-2">
                        {enabledPaymentMethods.map((method, index) => (
                            <Card
                                key={`${method.id}-${index}`}
                                className={`cursor-pointer transition-colors ${selectedPaymentMethod === method.id
                                    ? 'border-primary bg-primary/5'
                                    : 'hover:border-gray-300'
                                    }`}
                                onClick={() => setSelectedPaymentMethod(method.id)}
                            >
                                <CardContent className="p-3">
                                    <div className="flex items-center gap-3">
                                        <div className="text-primary">{method.icon}</div>
                                        <span className="font-medium">{method.name}</span>
                                        {selectedPaymentMethod === method.id && (
                                            <Badge variant="secondary" className="ml-auto">
                                                {t('Selected')}
                                            </Badge>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            {/* Coupon Code */}
            <div className="space-y-3">
                {plan.price != 0 && <><Label htmlFor="coupon">{t('Coupon Code')} ({t('Optional')})</Label>
                    <div className="flex gap-2">
                        <div className="relative flex-1">
                            <Input
                                id="coupon"
                                value={couponCode}
                                onChange={(e) => setCouponCode(e.target.value)}
                                placeholder={t('Enter coupon code')}
                                className="pr-10"
                                disabled={!!appliedCoupon}
                            />
                            <Tag className="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        </div>
                        {!appliedCoupon ? (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleApplyCoupon}
                                disabled={!couponCode.trim() || couponLoading}
                            >
                                {couponLoading ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                    t('Apply')
                                )}
                            </Button>
                        ) : (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleRemoveCoupon}
                            >
                                {t('Remove')}
                            </Button>
                        )}
                    </div></>}

                {appliedCoupon && (
                    <div className="bg-green-50 border border-green-200 rounded-lg p-3">
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-green-700 font-medium">
                                {t('Coupon Applied')}: {appliedCoupon.code}
                            </span>
                            <span className="text-green-600">
                                -{appliedCoupon.type === 'percentage' ? `${appliedCoupon.value}%` : `${currencySymbol}${appliedCoupon.value}`}
                            </span>
                        </div>
                    </div>
                )}
            </div>

            {/* Price Summary */}
            <Card>
                <CardContent className="p-4">
                    <div className="space-y-2">
                        <div className="flex justify-between text-sm">
                            <span>{t('Subtotal')}</span>
                            <span>{currencySymbol}{originalPrice}</span>
                        </div>
                        {appliedCoupon && (
                            <div className="flex justify-between text-sm text-green-600">
                                <span>{t('Discount')}</span>
                                <span>-{currencySymbol}{discountAmount}</span>
                            </div>
                        )}
                        <div className="border-t pt-2">
                            <div className="flex justify-between font-medium">
                                <span>{t('Total')}</span>
                                <span>{currencySymbol}{finalPrice.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Actions */}
            <div className="flex gap-3">
                <Button variant="outline" onClick={onCancel} className="flex-1">
                    {t('Cancel')}
                </Button>
                <Button
                    onClick={handlePayNow}
                    disabled={enabledPaymentMethods.length === 0 || processing}
                    className="flex-1"
                >
                    {t('Pay')} {currencySymbol} {finalPrice}
                </Button>
            </div>
        </div>
    );
}
