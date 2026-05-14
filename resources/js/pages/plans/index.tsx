import React, { useState, useEffect } from 'react';
import { PageTemplate } from '@/components/page-template';
import { Card, CardContent, CardHeader, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Button } from '@/components/ui/button';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { router, usePage } from '@inertiajs/react';
import {
    CheckCircle2,
    X,
    Pencil,
    Trash2,
    Globe,
    FileText,
    Bot,
    BarChart2,
    Mail,
    Box,
    Store,
    Users,
    HardDrive,
    Plus,
    Sparkles,
    Info,
    Crown,
    Zap,
    Clock,
    Banknote,
    CreditCard,
    IndianRupee,
    Wallet,
    Coins,
    Edit
} from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { useForm } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { PlanSubscriptionModal } from '@/components/plan-subscription-modal';

interface Plan {
    id: number;
    name: string;
    price: string | number;
    formatted_price?: string;
    duration: string;
    description: string;
    trial_days: number;
    features: string[];
    stats: {
        users: number | string;
        projects: number | string;
        contacts: number | string;
        accounts: number | string;
        storage: string;
    };
    status: boolean;
    recommended?: boolean;
    is_default?: boolean;
    is_current?: boolean;
    is_trial_available?: boolean;
}

interface Props {
    plans: Plan[];
    billingCycle: 'monthly' | 'yearly';
    hasDefaultPlan?: boolean;
    isAdmin?: boolean;
    currentPlan?: any;
    userTrialUsed?: boolean;
    paymentMethods?: any[];
    currency?: string;
    currencySymbol?: string;
}

export default function Plans({ plans: initialPlans, billingCycle: initialBillingCycle = 'monthly', hasDefaultPlan, isAdmin = false, currentPlan, userTrialUsed, paymentMethods = [], currency, currencySymbol }: Props) {
    const { t } = useTranslation();
    const { flash } = usePage().props as any;
    const [plans, setPlans] = useState<Plan[]>(initialPlans);
    const [billingCycle, setBillingCycle] = useState<'monthly' | 'yearly'>(initialBillingCycle);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [planToDelete, setPlanToDelete] = useState<Plan | null>(null);
    const [isSubscriptionModalOpen, setIsSubscriptionModalOpen] = useState(false);
    const [selectedPlan, setSelectedPlan] = useState<Plan | null>(null);

    const { post, processing } = useForm();

    // Helper function to safely format currency
    const formatCurrency = (amount: string | number) => {
        if (typeof window !== 'undefined' && window.appSettings?.formatCurrency) {
            // Use numeric value if available, otherwise parse the string
            const numericAmount = typeof amount === 'number' ? amount : parseFloat(amount);
            return window.appSettings.formatCurrency(numericAmount, { showSymbol: true });
        }
        // Fallback if appSettings is not available
        return amount;
    };

    // Update plans when initialPlans changes
    useEffect(() => {
        setPlans(initialPlans);
    }, [initialPlans]);

    // Update selected plan when plans change and modal is open
    useEffect(() => {
        if (selectedPlan && isSubscriptionModalOpen) {
            const updatedPlan = plans.find(p => p.id === selectedPlan.id);
            if (updatedPlan && updatedPlan.price !== selectedPlan.price) {
                setSelectedPlan({ ...updatedPlan, paymentMethods: selectedPlan.paymentMethods });
            }
        }
    }, [plans, isSubscriptionModalOpen]);

    // Show flash messages
    useEffect(() => {
        if (flash?.error) {
            toast.error(flash.error);
        }
        if (flash?.success) {
            toast.success(flash.success);
        }
    }, []);

    // Function to handle billing cycle change
    const handleBillingCycleChange = (value: 'monthly' | 'yearly') => {
        setBillingCycle(value);
        router.get(route('plans.index'), { billing_cycle: value }, { preserveState: true });
    };

    // Company plan actions
    const handlePlanRequest = (planId: number) => {
        toast.loading(t('Submitting plan request...'));

        router.post(route('plans.request'), {
            plan_id: planId,
            billing_cycle: billingCycle
        }, {
            onSuccess: (page) => {
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash.error) {
                    toast.error(t(page.props.flash.error));
                }
            },
            onError: (errors) => {
                toast.dismiss();
                if (typeof errors === 'string') {
                    toast.error(errors);
                } else {
                    toast.error(`Failed to submit plan request: ${Object.values(errors).join(', ')}`);
                }
            }
        });
    };

    const handleStartTrial = (planId: number) => {
        toast.loading(t('Starting trial...'));

        router.post(route('plans.trial'), {
            plan_id: planId
        }, {
            onSuccess: (page) => {
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash.error) {
                    toast.error(t(page.props.flash.error));
                }
            },
            onError: (errors) => {
                toast.dismiss();
                if (typeof errors === 'string') {
                    toast.error(errors);
                } else {
                    toast.error(`Failed to start trial: ${Object.values(errors).join(', ')}`);
                }
            }
        });
    };

    const handleSubscribe = async (planId: number) => {
        const plan = plans.find(p => p.id === planId);
        if (plan) {
            try {
                const response = await fetch(route('payment.methods'));
                const paymentMethods = await response.json();
                setSelectedPlan({ ...plan, paymentMethods });
                setIsSubscriptionModalOpen(true);
            } catch (error) {
                toast.error(t('Failed to load payment methods'));
            }
        }
    };

    const formatPaymentMethods = (paymentSettings: any) => {
        const methods = [];

        if (paymentSettings?.is_bank_enabled === true || paymentSettings?.is_bank_enabled === '1') {
            methods.push({
                id: 'bank',
                name: t('Bank Transfer'),
                icon: <Banknote className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_stripe_enabled === true || paymentSettings?.is_stripe_enabled === '1') {
            methods.push({
                id: 'stripe',
                name: t('Stripe'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_paypal_enabled === true || paymentSettings?.is_paypal_enabled === '1') {
            methods.push({
                id: 'paypal',
                name: t('PayPal'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_razorpay_enabled === true || paymentSettings?.is_razorpay_enabled === '1') {
            methods.push({
                id: 'razorpay',
                name: t('Razorpay'),
                icon: <IndianRupee className="h-5 w-5" />,
                enabled: true
            });
        }

        if ((paymentSettings?.is_mercadopago_enabled === true || paymentSettings?.is_mercadopago_enabled === '1')) {
            methods.push({
                id: 'mercadopago',
                name: t('MercadoPago'),
                icon: <Wallet className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_paystack_enabled === true || paymentSettings?.is_paystack_enabled === '1') {
            methods.push({
                id: 'paystack',
                name: t('Paystack'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_flutterwave_enabled === true || paymentSettings?.is_flutterwave_enabled === '1') {
            methods.push({
                id: 'flutterwave',
                name: t('Flutterwave'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_paytabs_enabled === true || paymentSettings?.is_paytabs_enabled === '1') {
            methods.push({
                id: 'paytabs',
                name: t('PayTabs'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_skrill_enabled === true || paymentSettings?.is_skrill_enabled === '1') {
            methods.push({
                id: 'skrill',
                name: t('Skrill'),
                icon: <Wallet className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_coingate_enabled === true || paymentSettings?.is_coingate_enabled === '1') {
            methods.push({
                id: 'coingate',
                name: t('CoinGate'),
                icon: <Coins className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_payfast_enabled === true || paymentSettings?.is_payfast_enabled === '1') {
            methods.push({
                id: 'payfast',
                name: t('Payfast'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_tap_enabled === true || paymentSettings?.is_tap_enabled === '1') {
            methods.push({
                id: 'tap',
                name: t('Tap'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_xendit_enabled === true || paymentSettings?.is_xendit_enabled === '1') {
            methods.push({
                id: 'xendit',
                name: t('Xendit'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_paytr_enabled === true || paymentSettings?.is_paytr_enabled === '1') {
            methods.push({
                id: 'paytr',
                name: t('PayTR'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_mollie_enabled === true || paymentSettings?.is_mollie_enabled === '1') {
            methods.push({
                id: 'mollie',
                name: t('Mollie'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_toyyibpay_enabled === true || paymentSettings?.is_toyyibpay_enabled === '1') {
            methods.push({
                id: 'toyyibpay',
                name: t('toyyibPay'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_cashfree_enabled === true || paymentSettings?.is_cashfree_enabled === '1') {
            methods.push({
                id: 'cashfree',
                name: t('Cashfree'),
                icon: <IndianRupee className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_khalti_enabled === true || paymentSettings?.is_khalti_enabled === '1') {
            methods.push({
                id: 'khalti',
                name: t('Khalti'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_iyzipay_enabled === true || paymentSettings?.is_iyzipay_enabled === '1') {
            methods.push({
                id: 'iyzipay',
                name: t('Iyzipay'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_benefit_enabled === true || paymentSettings?.is_benefit_enabled === '1') {
            methods.push({
                id: 'benefit',
                name: t('Benefit'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_ozow_enabled === true || paymentSettings?.is_ozow_enabled === '1') {
            methods.push({
                id: 'ozow',
                name: t('Ozow'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_easebuzz_enabled === true || paymentSettings?.is_easebuzz_enabled === '1') {
            methods.push({
                id: 'easebuzz',
                name: t('Easebuzz'),
                icon: <IndianRupee className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_authorizenet_enabled === true || paymentSettings?.is_authorizenet_enabled === '1') {
            methods.push({
                id: 'authorizenet',
                name: t('AuthorizeNet'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_fedapay_enabled === true || paymentSettings?.is_fedapay_enabled === '1') {
            methods.push({
                id: 'fedapay',
                name: t('FedaPay'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_payhere_enabled === true || paymentSettings?.is_payhere_enabled === '1') {
            methods.push({
                id: 'payhere',
                name: t('PayHere'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_cinetpay_enabled === true || paymentSettings?.is_cinetpay_enabled === '1') {
            methods.push({
                id: 'cinetpay',
                name: t('CinetPay'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_paiement_enabled === true || paymentSettings?.is_paiement_enabled === '1') {
            methods.push({
                id: 'paiement',
                name: t('Paiement Pro'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_nepalste_enabled === true || paymentSettings?.is_nepalste_enabled === '1') {
            methods.push({
                id: 'nepalste',
                name: t('Nepalste'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_yookassa_enabled === true || paymentSettings?.is_yookassa_enabled === '1') {
            methods.push({
                id: 'yookassa',
                name: t('YooKassa'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_aamarpay_enabled === true || paymentSettings?.is_aamarpay_enabled === '1') {
            methods.push({
                id: 'aamarpay',
                name: t('Aamarpay'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_midtrans_enabled === true || paymentSettings?.is_midtrans_enabled === '1') {
            methods.push({
                id: 'midtrans',
                name: t('Midtrans'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_paymentwall_enabled === true || paymentSettings?.is_paymentwall_enabled === '1') {
            methods.push({
                id: 'paymentwall',
                name: t('PaymentWall'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        if (paymentSettings?.is_sspay_enabled === true || paymentSettings?.is_sspay_enabled === '1') {
            methods.push({
                id: 'sspay',
                name: t('SSPay'),
                icon: <CreditCard className="h-5 w-5" />,
                enabled: true
            });
        }

        return methods;
    };

    const getActionButton = (plan: Plan) => {
        // Check if user has active subscription to this plan
        if (currentPlan && currentPlan.id === plan.id && currentPlan.expires_at && new Date(currentPlan.expires_at) > new Date()) {
            return (
                <Button disabled className="w-full bg-green-100 text-green-800 border-green-200">
                    <Crown className="h-4 w-4 mr-2" />
                    {t('Already Subscribed')}
                </Button>
            );
        }

        if (plan.is_current) {
            return (
                <Button disabled className="w-full">
                    <Crown className="h-4 w-4 mr-2" />
                    {t('Current Plan')}
                </Button>
            );
        }

        if (plan.is_trial_available && !userTrialUsed) {
            return (
                <div className="space-y-2">
                    <Button
                        onClick={() => handleStartTrial(plan.id)}
                        disabled={processing}
                        variant="outline"
                        className="w-full"
                    >
                        <Zap className="h-4 w-4 mr-2" />
                        {t('Start {{days}} Day Trial', { days: plan.trial_days })}
                    </Button>
                    <Button
                        onClick={() => handleSubscribe(plan.id)}
                        disabled={processing}
                        className="w-full"
                    >
                        {t('Subscribe Now')}
                    </Button>
                </div>
            );
        }

        return (
            <div className="space-y-2">
                <Button
                    onClick={() => handlePlanRequest(plan.id)}
                    disabled={processing}
                    variant="outline"
                    className="w-full"
                >
                    <Clock className="h-4 w-4 mr-2" />
                    {t('Request Plan')}
                </Button>
                <Button
                    onClick={() => handleSubscribe(plan.id)}
                    disabled={processing || (currentPlan && currentPlan.id === plan.id && currentPlan.expires_at && new Date(currentPlan.expires_at) > new Date())}
                    className="w-full"
                >
                    {currentPlan && currentPlan.id === plan.id && currentPlan.expires_at && new Date(currentPlan.expires_at) > new Date()
                        ? t('Already Subscribed')
                        : t('Subscribe Now')
                    }
                </Button>
            </div>
        );
    };

    // Function to get the appropriate icon for a feature
    const getFeatureIcon = (feature: string) => {
        switch (feature) {
            case 'Custom Domain':
                return <Globe className="h-4 w-4" />;
            case 'Subdomain':
                return <Globe className="h-4 w-4" />;
            case 'PWA':
                return <FileText className="h-4 w-4" />;
            case 'Blog Module':
                return <FileText className="h-4 w-4" />;
            case 'AI Integration':
                return <Bot className="h-4 w-4" />;
            case 'Analytics':
                return <BarChart2 className="h-4 w-4" />;
            case 'Email Support':
                return <Mail className="h-4 w-4" />;
            case 'API Access':
                return <Box className="h-4 w-4" />;
            case 'Priority Support':
                return <Users className="h-4 w-4" />;
            case 'Storage':
                return <HardDrive className="h-4 w-4" />;
            default:
                return <CheckCircle2 className="h-4 w-4" />;
        }
    };

    // Function to check if a feature is included in the plan
    const isFeatureIncluded = (plan: Plan, feature: string) => {
        return plan.features.includes(feature);
    };

    // Function to toggle plan status
    const togglePlanStatus = (planId: number) => {
        // Send request to toggle plan status
        router.post(route('plans.toggle-status', planId), {}, {
            preserveState: true,
            onSuccess: (page) => {
                // Update local state
                setPlans(plans.map(plan =>
                    plan.id === planId ? { ...plan, status: !plan.status } : plan
                ));
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash.error) {
                    toast.error(t(page.props.flash.error));
                }
            }
        });
    };

    // Function to handle delete
    const handleDelete = (plan: Plan) => {
        setPlanToDelete(plan);
        setIsDeleteModalOpen(true);
    };

    // Function to handle delete confirmation
    const handleDeleteConfirm = () => {
        if (planToDelete) {
            router.delete(route('plans.destroy', planToDelete.id), {
                onSuccess: (page) => {
                    setIsDeleteModalOpen(false);
                    setPlanToDelete(null);
                    toast.dismiss();
                    if (page.props.flash.success) {
                        toast.success(t(page.props.flash.success));
                    } else if (page.props.flash.error) {
                        toast.error(t(page.props.flash.error));
                    }
                }
            });
        }
    };

    // Common features to display for all plans
    const commonFeatures = [
        'AI Integration'
    ];

    // Define stat icons
    const statIcons = {
        users: <Users className="h-4 w-4 text-blue-500" />,
        projects: <Box className="h-4 w-4 text-green-500" />,
        contacts: <Mail className="h-4 w-4 text-orange-500" />,
        accounts: <Store className="h-4 w-4 text-purple-500" />,
        storage: <HardDrive className="h-4 w-4 text-yellow-500" />
    };

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Plans') }
    ];

    return (
        <PageTemplate
            title={t("Plans")}
            description={t("Manage subscription plans for your customers")}
            url="/plans"
            breadcrumbs={breadcrumbs}
        >
            <div className="space-y-6 sm:space-y-8">
                {/* Header with controls */}
                <div className="flex flex-col items-center text-center mb-12">
                    <div className="max-w-3xl mx-auto mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 mb-4">
                            {isAdmin ? t("Subscription Plans") : t("Choose Your Plan")}
                        </h1>
                        <p className="text-lg text-gray-600">
                            {isAdmin
                                ? t("Create and manage subscription plans to offer different service tiers to your customers.")
                                : t("Select the perfect plan for your business needs and start growing today.")
                            }
                        </p>
                    </div>
                    <div className="flex flex-col sm:flex-row items-center gap-4">
                        {/* Billing Cycle Toggle */}
                        <div className="bg-gray-100 rounded-lg p-1">
                            <Tabs
                                value={billingCycle}
                                onValueChange={(v) => handleBillingCycleChange(v as 'monthly' | 'yearly')}
                                className="w-full"
                            >
                                <TabsList className="grid w-full grid-cols-2 bg-transparent p-0 h-auto">
                                    <TabsTrigger
                                        value="monthly"
                                        className="px-6 py-2 text-sm font-medium data-[state=active]:bg-white data-[state=active]:text-gray-900 data-[state=active]:shadow-sm rounded-md cursor-pointer"
                                    >
                                        {t("Monthly")}
                                    </TabsTrigger>
                                    <TabsTrigger
                                        value="yearly"
                                        className="px-6 py-2 text-sm font-medium data-[state=active]:bg-white data-[state=active]:text-gray-900 data-[state=active]:shadow-sm rounded-md relative cursor-pointer"
                                    >
                                        {t("Yearly")}
                                        <Badge className="ml-2 bg-green-500 text-white text-xs px-2 py-0.5">
                                            {t("Save 20%")}
                                        </Badge>
                                    </TabsTrigger>
                                </TabsList>
                            </Tabs>
                        </div>

                        {/* Add Plan Button - Admin only */}
                        {isAdmin && (
                            <Button
                                className="bg-primary hover:bg-primary/90 text-white px-6 py-2 font-medium"
                                onClick={() => router.get(route('plans.create'))}
                            >
                                <Plus className="h-4 w-4 mr-2" />
                                {t("Add Plan")}
                            </Button>
                        )}
                    </div>
                </div>

                {/* Plans grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
                    {plans.map((plan) => (
                        <div
                            key={plan.id}
                            className={`relative h-full transition-all duration-200 ${plan.recommended ? 'transform scale-105' : ''}`}
                        >
                            {/* Main Card */}
                            <div className={`
                relative h-full flex flex-col rounded-lg border-2 transition-all duration-200
                ${plan.recommended
                                    ? 'border-primary shadow-xl bg-white'
                                    : 'border-gray-200 bg-white hover:border-gray-300 hover:shadow-lg'
                                }
              `}>
                                {/* Recommended Badge */}
                                {plan.recommended && (
                                    <div className="absolute -top-4 left-1/2 transform -translate-x-1/2 z-10">
                                        <div className="bg-primary text-white px-4 py-1 rounded-full text-sm font-semibold">
                                            {t("Recommended")}
                                        </div>
                                    </div>
                                )}

                                {/* Status Indicators */}
                                <div className="absolute top-4 right-4 z-10 flex flex-col gap-1">
                                    {isAdmin && (
                                        <>
                                            {plan.is_default && (
                                                <span className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                                    {t("Default")}
                                                </span>
                                            )}
                                            <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${plan.status
                                                    ? 'bg-green-50 text-green-700 ring-green-600/20'
                                                    : 'bg-red-50 text-red-700 ring-red-600/20'
                                                }`}>
                                                {plan.status ? t("Active") : t("Inactive")}
                                            </span>
                                        </>
                                    )}
                                    {!isAdmin && plan.is_current && (
                                        <span className="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            <Crown className="h-3 w-3 mr-1" />
                                            {t("Current")}
                                        </span>
                                    )}
                                </div>

                                {/* Card Header */}
                                <div className="p-6 text-center border-b border-gray-100">
                                    <h3 className="text-xl font-bold text-gray-900 mb-2">
                                        {plan.name}
                                    </h3>
                                    <div className="mb-4">
                                        <div className="flex items-center justify-center">
                                            <span className="text-4xl font-bold text-gray-900">
                                                {currencySymbol}{plan.price}
                                            </span>
                                            <span className="text-gray-500 ml-1">
                                                /{t(plan.duration.toLowerCase())}
                                            </span>
                                        </div>
                                    </div>
                                    <p className="text-gray-600 text-sm mb-4">
                                        {plan.description}
                                    </p>
                                    {plan.trial_days > 0 && (
                                        <div className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                            <Zap className="h-3 w-3" />
                                            {t("{{days}} days free trial", { days: plan.trial_days })}
                                        </div>
                                    )}
                                </div>

                                {/* Card Content */}
                                <div className="flex flex-col flex-1 p-6">
                                    {/* Usage Stats */}
                                    <div className="mb-6">
                                        <h4 className="text-sm font-semibold text-gray-900 mb-3 uppercase tracking-wide">
                                            {t("What's Included")}
                                        </h4>
                                        <div className="space-y-3">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    {statIcons.users}
                                                    <span className="text-sm text-gray-700">{t("Users")}</span>
                                                </div>
                                                <span className="text-sm font-semibold text-gray-900">{plan.stats.users}</span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    {statIcons.projects}
                                                    <span className="text-sm text-gray-700">{t("Projects")}</span>
                                                </div>
                                                <span className="text-sm font-semibold text-gray-900">{plan.stats.projects}</span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    {statIcons.contacts}
                                                    <span className="text-sm text-gray-700">{t("Contacts")}</span>
                                                </div>
                                                <span className="text-sm font-semibold text-gray-900">{plan.stats.contacts}</span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    {statIcons.accounts}
                                                    <span className="text-sm text-gray-700">{t("Accounts")}</span>
                                                </div>
                                                <span className="text-sm font-semibold text-gray-900">{plan.stats.accounts}</span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    {statIcons.storage}
                                                    <span className="text-sm text-gray-700">{t("Storage")}</span>
                                                </div>
                                                <span className="text-sm font-semibold text-gray-900">{plan.stats.storage}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Features */}
                                    <div className="mb-6 flex-1">
                                        <h4 className="text-sm font-semibold text-gray-900 mb-3 uppercase tracking-wide">
                                            {t("Features")}
                                        </h4>
                                        <ul className="space-y-2">
                                            {commonFeatures.map((feature, index) => {
                                                const included = isFeatureIncluded(plan, feature);
                                                return (
                                                    <li key={index} className="flex items-center gap-2">
                                                        <div className={`flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center ${included ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'
                                                            }`}>
                                                            {included ? <CheckCircle2 className="h-3 w-3" /> : <X className="h-3 w-3" />}
                                                        </div>
                                                        <span className={`text-sm ${included ? 'text-gray-700' : 'text-gray-400'}`}>
                                                            {t(feature)}
                                                        </span>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    </div>

                                    {/* Actions */}
                                    <div className="mt-auto">
                                        {isAdmin ? (
                                            <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                <div className="flex items-center gap-2">
                                                    <Switch
                                                        checked={plan.status}
                                                        onCheckedChange={() => togglePlanStatus(plan.id)}
                                                        className={plan.status ? "data-[state=checked]:bg-primary" : ""}
                                                    />
                                                    <span className="text-sm text-gray-700">
                                                        {plan.status ? t("Active") : t("Inactive")}
                                                    </span>
                                                </div>
                                                <div className="flex items-center gap-1">
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="text-amber-500 hover:text-amber-700"
                                                                    onClick={() => router.get(route('plans.edit', plan.id))}
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>{t('Edit')}</TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                    {!plan.is_default && (
                                                        <TooltipProvider>
                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        className="text-red-500 hover:text-red-700"
                                                                        onClick={() => handleDelete(plan)}
                                                                    >
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>{t('Delete')}</TooltipContent>
                                                            </Tooltip>
                                                        </TooltipProvider>
                                                    )}
                                                </div>
                                            </div>
                                        ) : (
                                            getActionButton(plan)
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Delete Modal - Admin only */}
                {isAdmin && (
                    <CrudDeleteModal
                        isOpen={isDeleteModalOpen}
                        onClose={() => setIsDeleteModalOpen(false)}
                        onConfirm={handleDeleteConfirm}
                        itemName={planToDelete?.name || ''}
                        entityName="plan"
                    />
                )}

                {/* Subscription Modal - Company only */}
                {!isAdmin && selectedPlan && (
                    <PlanSubscriptionModal
                        isOpen={isSubscriptionModalOpen}
                        onClose={() => {
                            setIsSubscriptionModalOpen(false);
                            setSelectedPlan(null);
                        }}
                        plan={selectedPlan}
                        billingCycle={billingCycle}
                        currencySymbol={currencySymbol}
                        paymentMethods={formatPaymentMethods(selectedPlan.paymentMethods)}
                    />
                )}
            </div>
        </PageTemplate>
    );
}
