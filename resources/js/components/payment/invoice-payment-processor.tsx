import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { CreditCard, Banknote, Wallet } from 'lucide-react';
import { toast } from '@/components/custom-toast';
import { StripePaymentForm } from './stripe-payment-form';
import { InvoicePayPalPaymentForm } from './invoice-paypal-payment-form';
import { InvoiceRazorpayPaymentForm } from './invoice-razorpay-payment-form';
import { InvoiceMercadoPagoPaymentForm } from './invoice-mercadopago-payment-form';
import { InvoicePaystackPaymentForm } from './invoice-paystack-payment-form';
import { InvoiceFlutterwavePaymentForm } from './invoice-flutterwave-payment-form';
import { InvoicePayTabsPaymentForm } from './invoice-paytabs-payment-form';
import { InvoiceSkrillPaymentForm } from './invoice-skrill-payment-form';
import { InvoiceCoingatePaymentForm } from './invoice-coingate-payment-form';
import { InvoiceBankTransferForm } from './invoice-bank-transfer-form';
import { InvoicePayfastPaymentForm } from './invoice-payfast-payment-form';
import { InvoiceTapPaymentForm } from './invoice-tap-payment-form';
import { InvoiceXenditPaymentForm } from './invoice-xendit-payment-form';
import { InvoicePayTRPaymentForm } from './invoice-paytr-payment-form';
import { InvoiceMolliePaymentForm } from './invoice-mollie-payment-form';
import { InvoiceToyyibPayPaymentForm } from './invoice-toyyibpay-payment-form';
import { InvoicePaymentWallPaymentForm } from './invoice-paymentwall-payment-form';
import { InvoiceSSPayPaymentForm } from './invoice-sspay-payment-form';
import { InvoiceBenefitPaymentForm } from './invoice-benefit-payment-form';
import { InvoiceIyzipayPaymentForm } from './invoice-iyzipay-payment-form';
import { InvoiceAamarpayPaymentForm } from './invoice-aamarpay-payment-form';
import { InvoiceMidtransPaymentForm } from './invoice-midtrans-payment-form';
import { InvoiceYooKassaPaymentForm } from './invoice-yookassa-payment-form';
import { InvoicePaiementPaymentForm } from './invoice-paiement-payment-form';
import { InvoiceCinetPayPaymentForm } from './invoice-cinetpay-payment-form';
import { InvoicePayHerePaymentForm } from './invoice-payhere-payment-form';
import { InvoiceFedaPayPaymentForm } from './invoice-fedapay-payment-form';
import { InvoiceAuthorizeNetPaymentForm } from './invoice-authorizenet-payment-form';
import { InvoiceKhaltiPaymentForm } from './invoice-khalti-payment-form';
import { InvoiceEasebuzzPaymentForm } from './invoice-easebuzz-payment-form';
import { InvoiceOzowPaymentForm } from './invoice-ozow-payment-form';
import { InvoiceCashfreePaymentForm } from './invoice-cashfree-payment-form';

interface PaymentMethod {
    id: string;
    name: string;
    icon: React.ReactNode;
    enabled: boolean;
}

interface Invoice {
    id: number;
    invoice_number: string;
    name: string;
    total_amount: number;
    paymentMethods?: any;
}

interface InvoicePaymentProcessorProps {
    invoice: Invoice;
    amount: number;
    onAmountChange: (amount: number) => void;
    onSuccess: () => void;
    onCancel: () => void;
}

export function InvoicePaymentProcessor({
    invoice,
    amount,
    onAmountChange,
    onSuccess,
    onCancel
}: InvoicePaymentProcessorProps) {
    const { t } = useTranslation();
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState<string>('');
    const [showPaymentForm, setShowPaymentForm] = useState(false);
    const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([]);

    useEffect(() => {
        // Build payment methods from invoice.paymentMethods like plans page does
        const methods = [];
        const paymentSettings = invoice.paymentMethods;

        if (paymentSettings?.is_bank_enabled === true || paymentSettings?.is_bank_enabled === '1') {
            methods.push({
                id: 'bank',
                name: 'Bank Transfer',
                icon: <Banknote className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_stripe_enabled === true || paymentSettings?.is_stripe_enabled === '1') {
            methods.push({
                id: 'stripe',
                name: 'Stripe',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_paypal_enabled === true || paymentSettings?.is_paypal_enabled === '1') {
            methods.push({
                id: 'paypal',
                name: 'PayPal',
                icon: <Wallet className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_razorpay_enabled === true || paymentSettings?.is_razorpay_enabled === '1') {
            methods.push({
                id: 'razorpay',
                name: 'Razorpay',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_mercadopago_enabled === true || paymentSettings?.is_mercadopago_enabled === '1') {
            methods.push({
                id: 'mercadopago',
                name: 'MercadoPago',
                icon: <Wallet className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_paystack_enabled === true || paymentSettings?.is_paystack_enabled === '1') {
            methods.push({
                id: 'paystack',
                name: 'Paystack',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_flutterwave_enabled === true || paymentSettings?.is_flutterwave_enabled === '1') {
            methods.push({
                id: 'flutterwave',
                name: 'Flutterwave',
                icon: <Wallet className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_paytabs_enabled === true || paymentSettings?.is_paytabs_enabled === '1') {
            methods.push({
                id: 'paytabs',
                name: 'PayTabs',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_skrill_enabled === true || paymentSettings?.is_skrill_enabled === '1') {
            methods.push({
                id: 'skrill',
                name: 'Skrill',
                icon: <Wallet className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_coingate_enabled === true || paymentSettings?.is_coingate_enabled === '1') {
            methods.push({
                id: 'coingate',
                name: 'Coingate',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_payfast_enabled === true || paymentSettings?.is_payfast_enabled === '1') {
            methods.push({
                id: 'payfast',
                name: 'PayFast',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_tap_enabled === true || paymentSettings?.is_tap_enabled === '1') {
            methods.push({
                id: 'tap',
                name: 'Tap',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_xendit_enabled === true || paymentSettings?.is_xendit_enabled === '1') {
            methods.push({
                id: 'xendit',
                name: 'Xendit',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_paytr_enabled === true || paymentSettings?.is_paytr_enabled === '1') {
            methods.push({
                id: 'paytr',
                name: 'PayTR',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_mollie_enabled === true || paymentSettings?.is_mollie_enabled === '1') {
            methods.push({
                id: 'mollie',
                name: 'Mollie',
                icon: <Wallet className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_toyyibpay_enabled === true || paymentSettings?.is_toyyibpay_enabled === '1') {
            methods.push({
                id: 'toyyibpay',
                name: 'ToyyibPay',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_paymentwall_enabled === true || paymentSettings?.is_paymentwall_enabled === '1') {
            methods.push({
                id: 'paymentwall',
                name: 'PaymentWall',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_sspay_enabled === true || paymentSettings?.is_sspay_enabled === '1') {
            methods.push({
                id: 'sspay',
                name: 'SSPay',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_benefit_enabled === true || paymentSettings?.is_benefit_enabled === '1') {
            methods.push({
                id: 'benefit',
                name: 'Benefit',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_iyzipay_enabled === true || paymentSettings?.is_iyzipay_enabled === '1') {
            methods.push({
                id: 'iyzipay',
                name: 'Iyzipay',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_aamarpay_enabled === true || paymentSettings?.is_aamarpay_enabled === '1') {
            methods.push({
                id: 'aamarpay',
                name: 'Aamarpay',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_midtrans_enabled === true || paymentSettings?.is_midtrans_enabled === '1') {
            methods.push({
                id: 'midtrans',
                name: 'Midtrans',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_yookassa_enabled === true || paymentSettings?.is_yookassa_enabled === '1') {
            methods.push({
                id: 'yookassa',
                name: 'YooKassa',
                icon: <Wallet className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_paiement_enabled === true || paymentSettings?.is_paiement_enabled === '1') {
            methods.push({
                id: 'paiement',
                name: 'Paiement Pro',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_cinetpay_enabled === true || paymentSettings?.is_cinetpay_enabled === '1') {
            methods.push({
                id: 'cinetpay',
                name: 'CinetPay',
                icon: <Wallet className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_payhere_enabled === true || paymentSettings?.is_payhere_enabled === '1') {
            methods.push({
                id: 'payhere',
                name: 'PayHere',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_fedapay_enabled === true || paymentSettings?.is_fedapay_enabled === '1') {
            methods.push({
                id: 'fedapay',
                name: 'FedaPay',
                icon: <Wallet className="h-5 w-5" />,
                enabled: true
            });
        }


        if (paymentSettings?.is_authorizenet_enabled === true || paymentSettings?.is_authorizenet_enabled === '1') {
            methods.push({
                id: 'authorizenet',
                name: 'AuthorizeNet',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_khalti_enabled === true || paymentSettings?.is_khalti_enabled === '1') {
            methods.push({
                id: 'khalti',
                name: 'Khalti',
                icon: <Wallet className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_easebuzz_enabled === true || paymentSettings?.is_easebuzz_enabled === '1') {
            methods.push({
                id: 'easebuzz',
                name: 'Easebuzz',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_ozow_enabled === true || paymentSettings?.is_ozow_enabled === '1') {
            methods.push({
                id: 'ozow',
                name: 'Ozow',
                icon: <Wallet className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_cashfree_enabled === true || paymentSettings?.is_cashfree_enabled === '1') {
            methods.push({
                id: 'cashfree',
                name: 'Cashfree',
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        setPaymentMethods(methods);
    }, [invoice.paymentMethods]);

    const formatCurrency = (amount: number) => {
        return window.appSettings?.formatCurrency(Number(amount || 0)) || `$${Number(amount || 0).toFixed(2)}`;
    };

    // Calculate due amount (total - already paid)
    const paidAmount = invoice.payments?.reduce((total: number, payment: any) => {
        return payment.status === 'completed' ? total + parseFloat(payment.amount || 0) : total;
    }, 0) || 0;
    const dueAmount = invoice.total_amount - paidAmount;

    const handlePayNow = () => {
        if (!selectedPaymentMethod) {
            toast.error(t('Please select a payment method'));
            return;
        }
        if (amount <= 0 || amount > dueAmount) {
            toast.error(t('Payment amount cannot exceed due amount'));
            return;
        }
        setShowPaymentForm(true);
    };

    const handlePaymentCancel = () => {
        setShowPaymentForm(false);
        setSelectedPaymentMethod('');
    };

    const renderPaymentForm = () => {
        const paymentType = amount === invoice.total_amount ? 'full' : 'partial';

        switch (selectedPaymentMethod) {
            case 'stripe':
                return (
                    <StripePaymentForm
                        planId={0} // Not used for invoice payments
                        couponCode=""
                        billingCycle="monthly" // Not used for invoice payments
                        stripeKey={invoice.paymentMethods?.stripe_key || ''}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                        // Invoice-specific props
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                    />
                );
            case 'paypal':
                return (
                    <InvoicePayPalPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        paypalClientId={invoice.paymentMethods?.paypal_client_id || ''}
                        currency={invoice.paymentMethods?.currency || 'USD'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'razorpay':
                return (
                    <InvoiceRazorpayPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        razorpayKey={invoice.paymentMethods?.razorpay_key || ''}
                        currency={invoice.paymentMethods?.currency || 'INR'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'mercadopago':
                return (
                    <InvoiceMercadoPagoPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        accessToken={invoice.paymentMethods?.mercadopago_access_token || ''}
                        currency={invoice.paymentMethods?.currency || 'BRL'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'paystack':
                return (
                    <InvoicePaystackPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        paystackKey={invoice.paymentMethods?.paystack_public_key || ''}
                        currency={invoice.paymentMethods?.currency || 'NGN'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'flutterwave':
                return (
                    <InvoiceFlutterwavePaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        flutterwaveKey={invoice.paymentMethods?.flutterwave_public_key || ''}
                        currency={invoice.paymentMethods?.currency || 'NGN'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'paytabs':
                return (
                    <InvoicePayTabsPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        paytabsClientKey={invoice.paymentMethods?.paytabs_client_key || ''}
                        currency={invoice.paymentMethods?.currency || 'AED'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'skrill':
                return (
                    <InvoiceSkrillPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        skrillMerchantId={invoice.paymentMethods?.skrill_merchant_id || ''}
                        currency={invoice.paymentMethods?.currency || 'USD'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'coingate':
                return (
                    <InvoiceCoingatePaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        coingateApiToken={invoice.paymentMethods?.coingate_api_token || ''}
                        currency={invoice.paymentMethods?.currency || 'USD'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'bank':
                return (
                    <InvoiceBankTransferForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        bankDetails={invoice.paymentMethods?.bank_detail || 'Bank details not configured'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'payfast':
                return (
                    <InvoicePayfastPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        payfastMerchantId={invoice.paymentMethods?.payfast_merchant_id || ''}
                        currency={invoice.paymentMethods?.currency || 'ZAR'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'tap':
                return (
                    <InvoiceTapPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        tapSecretKey={invoice.paymentMethods?.tap_secret_key || ''}
                        currency={invoice.paymentMethods?.currency || 'USD'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'xendit':
                return (
                    <InvoiceXenditPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        xenditApiKey={invoice.paymentMethods?.xendit_api_key || ''}
                        currency={invoice.paymentMethods?.currency || 'USD'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'paytr':
                return (
                    <InvoicePayTRPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        paytrMerchantId={invoice.paymentMethods?.paytr_merchant_id || ''}
                        currency={invoice.paymentMethods?.currency || 'TRY'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'mollie':
                return (
                    <InvoiceMolliePaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        mollieApiKey={invoice.paymentMethods?.mollie_api_key || ''}
                        currency={invoice.paymentMethods?.currency || 'EUR'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'toyyibpay':
                return (
                    <InvoiceToyyibPayPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        toyyibpayCategoryCode={invoice.paymentMethods?.toyyibpay_category_code || ''}
                        currency={invoice.paymentMethods?.currency || 'MYR'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'paymentwall':
                return (
                    <InvoicePaymentWallPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        paymentwallPublicKey={invoice.paymentMethods?.paymentwall_public_key || ''}
                        currency={invoice.paymentMethods?.currency || 'USD'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'sspay':
                return (
                    <InvoiceSSPayPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        sspayCategoryCode={invoice.paymentMethods?.sspay_category_code || ''}
                        currency={invoice.paymentMethods?.currency || 'MYR'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'benefit':
                return (
                    <InvoiceBenefitPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        benefitSecretKey={invoice.paymentMethods?.benefit_secret_key || ''}
                        currency={invoice.paymentMethods?.currency || 'BHD'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'iyzipay':
                return (
                    <InvoiceIyzipayPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        iyzipayPublicKey={invoice.paymentMethods?.iyzipay_public_key || ''}
                        currency={invoice.paymentMethods?.currency || 'USD'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'aamarpay':
                return (
                    <InvoiceAamarpayPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        aamarpayStoreId={invoice.paymentMethods?.aamarpay_store_id || ''}
                        currency={invoice.paymentMethods?.currency || 'BDT'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'midtrans':
                return (
                    <InvoiceMidtransPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        midtransClientKey={invoice.paymentMethods?.midtrans_secret_key || ''}
                        currency={invoice.paymentMethods?.currency || 'IDR'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'yookassa':
                return (
                    <InvoiceYooKassaPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        yookassaShopId={invoice.paymentMethods?.yookassa_shop_id || ''}
                        currency={invoice.paymentMethods?.currency || 'RUB'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'paiement':
                return (
                    <InvoicePaiementPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        paiementMerchantId={invoice.paymentMethods?.paiement_merchant_id || ''}
                        currency={invoice.paymentMethods?.currency || 'XOF'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'cinetpay':
                return (
                    <InvoiceCinetPayPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        cinetpaySiteId={invoice.paymentMethods?.cinetpay_site_id || ''}
                        currency={invoice.paymentMethods?.currency || 'XOF'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'payhere':
                return (
                    <InvoicePayHerePaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        payhereMerchantId={invoice.paymentMethods?.payhere_merchant_id || ''}
                        currency={invoice.paymentMethods?.currency || 'LKR'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'fedapay':
                return (
                    <InvoiceFedaPayPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        fedapayPublicKey={invoice.paymentMethods?.fedapay_public_key || ''}
                        currency={invoice.paymentMethods?.currency || 'XOF'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'authorizenet':
                return (
                    <InvoiceAuthorizeNetPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        authorizenetMerchantId={invoice.paymentMethods?.authorizenet_merchant_id || ''}
                        currency={invoice.paymentMethods?.currency || 'USD'}
                        isSandbox={invoice.paymentMethods?.authorizenet_mode === 'sandbox'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'khalti':
                return (
                    <InvoiceKhaltiPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        khaltiPublicKey={invoice.paymentMethods?.khalti_public_key || ''}
                        currency={invoice.paymentMethods?.currency || 'NPR'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'easebuzz':
                return (
                    <InvoiceEasebuzzPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        easebuzzMerchantKey={invoice.paymentMethods?.easebuzz_merchant_key || ''}
                        currency={invoice.paymentMethods?.currency || 'INR'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'ozow':
                return (
                    <InvoiceOzowPaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        ozowSiteKey={invoice.paymentMethods?.ozow_site_key || ''}
                        currency={invoice.paymentMethods?.currency || 'ZAR'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
                    />
                );
            case 'cashfree':
                return (
                    <InvoiceCashfreePaymentForm
                        invoiceId={invoice.id}
                        amount={amount}
                        paymentType={paymentType}
                        cashfreeAppId={invoice.paymentMethods?.cashfree_public_key || ''}
                        mode={invoice.paymentMethods?.cashfree_mode || 'sandbox'}
                        currency={invoice.paymentMethods?.currency || 'INR'}
                        onSuccess={onSuccess}
                        onCancel={handlePaymentCancel}
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
            {/* Invoice Summary */}
            <Card>
                <CardContent className="p-4">
                    <div className="flex justify-between items-center">
                        <div>
                            <h3 className="font-medium">Invoice #{invoice.invoice_number}</h3>
                            <p className="text-sm text-muted-foreground">{invoice.name}</p>
                        </div>
                        <div className="text-right">
                            <div className="text-lg font-bold">{formatCurrency(invoice.total_amount)}</div>
                            <div className="text-sm text-muted-foreground">Total</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Payment Amount */}
            <div className="space-y-3">
                <Label htmlFor="amount">{t('Payment Amount')}</Label>
                <Input
                    id="amount"
                    type="number"
                    value={amount}
                    onChange={(e) => onAmountChange(parseFloat(e.target.value) || 0)}
                    max={dueAmount}
                    min="0.01"
                    step="0.01"
                    placeholder="0.00"
                />
                <div className="text-sm text-muted-foreground space-y-1">
                    <p>Total: {formatCurrency(invoice.total_amount)}</p>
                    <p>Paid: {formatCurrency(paidAmount)}</p>
                    <p className="font-medium">Due: {formatCurrency(dueAmount)}</p>
                </div>
            </div>

            {/* Payment Methods */}
            <div className="space-y-3">
                <Label>{t('Select Payment Method')}</Label>
                {paymentMethods.length === 0 ? (
                    <div className="p-4 text-center border rounded-md bg-muted/50">
                        <p className="text-sm text-muted-foreground mb-2">
                            {t('No payment methods configured')}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {t('Please contact the company to set up payment methods.')}
                        </p>
                    </div>
                ) : (
                    <div className="space-y-2">
                        {paymentMethods.map((method) => (
                            <Card
                                key={method.id}
                                className={`cursor-pointer transition-colors ${
                                    selectedPaymentMethod === method.id
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

            {/* Actions */}
            <div className="flex gap-3">
                <Button variant="outline" onClick={onCancel} className="flex-1">
                    {t('Cancel')}
                </Button>
                <Button
                    onClick={handlePayNow}
                    disabled={paymentMethods.length === 0 || amount <= 0 || amount > dueAmount}
                    className="flex-1"
                >
                    {t('Pay')} {formatCurrency(amount)}
                </Button>
            </div>
        </div>
    );
}
