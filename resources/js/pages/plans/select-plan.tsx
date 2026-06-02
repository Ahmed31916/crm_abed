import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { router, usePage, useForm } from '@inertiajs/react';
import {
    CheckCircle2,
    X,
    Globe,
    FileText,
    Bot,
    BarChart2,
    Mail,
    Box,
    Store,
    Users,
    HardDrive,
    Sparkles,
    Crown,
    Zap,
    Key,
    Loader2,
    ArrowRight,
    Shield,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';

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
    currentPlan?: any;
    userTrialUsed?: boolean;
    currency?: string;
    currencySymbol?: string;
    hasLicenseKey?: boolean;
}

export default function SelectPlan({
    plans: initialPlans,
    billingCycle: initialBillingCycle = 'monthly',
    currentPlan,
    userTrialUsed,
    currency,
    currencySymbol = '$',
    hasLicenseKey
}: Props) {
    const { t } = useTranslation();
    const { flash } = usePage().props as any;
    const [plans, setPlans] = useState<Plan[]>(initialPlans);
    const [billingCycle, setBillingCycle] = useState<'monthly' | 'yearly'>(initialBillingCycle);
    const [selectedPlanId, setSelectedPlanId] = useState<number | null>(null);

    const { processing } = useForm();

    // Update plans when initialPlans changes
    useEffect(() => {
        setPlans(initialPlans);
    }, [initialPlans]);

    // Show flash messages
    useEffect(() => {
        if (flash?.error) {
            toast.error(flash.error);
        }
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.warning) {
            toast.warning(flash.warning);
        }
    }, [flash]);

    // Function to handle billing cycle change
    const handleBillingCycleChange = (value: 'monthly' | 'yearly') => {
        setBillingCycle(value);
        router.get(route('plans.index'), { billing_cycle: value }, { preserveState: true });
    };

    // Handle plan selection - subscribe and generate license key
    const handleSubscribeAndGenerate = (planId: number) => {
        if (processing) return;

        setSelectedPlanId(planId);

        router.post(route('license.generate'), {
            plan_id: planId,
            billing_cycle: billingCycle,
        }, {
            onSuccess: (page) => {
                setSelectedPlanId(null);
            },
            onError: (errors) => {
                setSelectedPlanId(null);
                if (typeof errors === 'string') {
                    toast.error(errors);
                } else {
                    toast.error(`${t('Failed to generate license key')}: ${Object.values(errors).join(', ')}`);
                }
            }
        });
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

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                {/* Header */}
                <div className="flex flex-col items-center text-center mb-12">
                    <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 mb-6">
                        <Sparkles className="h-8 w-8 text-primary" />
                    </div>
                    <div className="max-w-3xl mx-auto mb-8">
                        <h1 className="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                            {t("Choose Your Plan")}
                        </h1>
                        <p className="text-lg text-gray-600 dark:text-gray-400">
                            {t("Select the perfect plan for your business needs. After selecting, you'll receive your license key to activate your account.")}
                        </p>
                    </div>

                    {/* Billing Cycle Toggle */}
                    <div className="bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
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
                </div>

                {/* Plans Grid */}
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
                                    ? 'border-primary shadow-xl bg-white dark:bg-gray-800'
                                    : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-gray-300 hover:shadow-lg'
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
                                    {plan.is_current && (
                                        <span className="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            <Crown className="h-3 w-3 mr-1" />
                                            {t("Current")}
                                        </span>
                                    )}
                                </div>

                                {/* Card Header */}
                                <div className={`p-6 text-center border-b border-gray-100 dark:border-gray-700 ${plan.recommended ? 'pt-10' : ''}`}>
                                    <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                        {plan.name}
                                    </h3>
                                    <div className="mb-4">
                                        <div className="flex items-center justify-center">
                                            <span className="text-4xl font-bold text-gray-900 dark:text-white">
                                                {currencySymbol}{plan.price}
                                            </span>
                                            <span className="text-gray-500 dark:text-gray-400 ml-1">
                                                /{t(plan.duration.toLowerCase())}
                                            </span>
                                        </div>
                                    </div>
                                    <p className="text-gray-600 dark:text-gray-400 text-sm mb-4">
                                        {plan.description}
                                    </p>
                                    {plan.trial_days > 0 && (
                                        <div className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                            <Zap className="h-3 w-3 mr-1" />
                                            {t("{{days}} days free trial", { days: plan.trial_days })}
                                        </div>
                                    )}
                                </div>

                                {/* Card Content */}
                                <div className="flex flex-col flex-1 p-6">
                                    {/* Usage Stats */}
                                    <div className="mb-6">
                                        <h4 className="text-sm font-semibold text-gray-900 dark:text-white mb-3 uppercase tracking-wide">
                                            {t("What's Included")}
                                        </h4>
                                        <div className="space-y-3">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    {statIcons.users}
                                                    <span className="text-sm text-gray-700 dark:text-gray-300">{t("Users")}</span>
                                                </div>
                                                <span className="text-sm font-semibold text-gray-900 dark:text-white">{plan.stats.users}</span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    {statIcons.projects}
                                                    <span className="text-sm text-gray-700 dark:text-gray-300">{t("Projects")}</span>
                                                </div>
                                                <span className="text-sm font-semibold text-gray-900 dark:text-white">{plan.stats.projects}</span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    {statIcons.contacts}
                                                    <span className="text-sm text-gray-700 dark:text-gray-300">{t("Contacts")}</span>
                                                </div>
                                                <span className="text-sm font-semibold text-gray-900 dark:text-white">{plan.stats.contacts}</span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    {statIcons.accounts}
                                                    <span className="text-sm text-gray-700 dark:text-gray-300">{t("Accounts")}</span>
                                                </div>
                                                <span className="text-sm font-semibold text-gray-900 dark:text-white">{plan.stats.accounts}</span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    {statIcons.storage}
                                                    <span className="text-sm text-gray-700 dark:text-gray-300">{t("Storage")}</span>
                                                </div>
                                                <span className="text-sm font-semibold text-gray-900 dark:text-white">{plan.stats.storage}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Features */}
                                    <div className="mb-6 flex-1">
                                        <h4 className="text-sm font-semibold text-gray-900 dark:text-white mb-3 uppercase tracking-wide">
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
                                                        <span className={`text-sm ${included ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400'}`}>
                                                            {t(feature)}
                                                        </span>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    </div>

                                    {/* Action Button - Subscribe & Generate License Key */}
                                    <div className="mt-auto">
                                        {plan.is_current ? (
                                            <Button disabled className="w-full bg-green-100 text-green-800 border-green-200">
                                                <Crown className="h-4 w-4 mr-2" />
                                                {t('Current Plan')}
                                            </Button>
                                        ) : (
                                            <Button
                                                onClick={() => handleSubscribeAndGenerate(plan.id)}
                                                disabled={processing}
                                                className={`w-full text-white font-medium py-2.5 transition-all duration-200 ${
                                                    plan.recommended
                                                        ? 'bg-primary hover:bg-primary/90 shadow-lg hover:shadow-xl'
                                                        : 'bg-gray-900 hover:bg-gray-800 dark:bg-primary dark:hover:bg-primary/90'
                                                }`}
                                            >
                                                {processing && selectedPlanId === plan.id ? (
                                                    <>
                                                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                                        {t('Generating License Key...')}
                                                    </>
                                                ) : (
                                                    <>
                                                        <Key className="h-4 w-4 mr-2" />
                                                        {t('Subscribe & Generate License Key')}
                                                    </>
                                                )}
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {/* No Plans Available Message */}
                {plans.length === 0 && (
                    <div className="text-center py-12">
                        <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                            <BarChart2 className="h-8 w-8 text-gray-400" />
                        </div>
                        <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                            {t('No Plans Available')}
                        </h3>
                        <p className="text-gray-500 dark:text-gray-400">
                            {t('There are currently no plans available. Please contact the administrator.')}
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
}
