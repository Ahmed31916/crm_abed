import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import {
    Shield,
    Key,
    Copy,
    Check,
    ArrowRight,
    Sparkles,
    Crown,
    Calendar,
    User,
    AlertTriangle,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import { Button } from '@/components/ui/button';

interface Props {
    licenseKey: string;
    planName: string;
    planPrice: string | number;
    planDuration: string;
    expiresAt: string;
    currencySymbol?: string;
    issuedTo?: string;
    licenseId?: string;
    isActivated?: boolean;
}

export default function LicenseKey({
    licenseKey,
    planName,
    planPrice,
    planDuration,
    expiresAt,
    currencySymbol = '$',
    issuedTo = '',
    licenseId = '',
    isActivated = false,
}: Props) {
    const { t } = useTranslation();
    const [copied, setCopied] = useState(false);

    // Copy license key to clipboard
    const copyLicenseKey = async () => {
        try {
            await navigator.clipboard.writeText(licenseKey);
            setCopied(true);
            toast.success(t('License key copied to clipboard'));
            setTimeout(() => setCopied(false), 3000);
        } catch {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = licenseKey;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            setCopied(true);
            toast.success(t('License key copied to clipboard'));
            setTimeout(() => setCopied(false), 3000);
        }
    };

    // Navigate to dashboard
    const goToDashboard = () => {
        router.get(route('dashboard'));
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 flex items-center justify-center p-4">
            <div className="w-full max-w-2xl">
                {/* Success Icon */}
                <div className="text-center mb-8">
                    <div className="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/30 mb-4">
                        <Shield className="h-10 w-10 text-green-600 dark:text-green-400" />
                    </div>
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        {t('License Key Generated Successfully!')}
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400 text-lg">
                        {t('Your subscription is now active. Please save your license key below.')}
                    </p>
                </div>

                {/* Main Card */}
                <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    {/* Plan Info Header */}
                    <div className="bg-gradient-to-r from-primary to-primary/80 p-6 text-white">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="flex items-center gap-2 mb-1">
                                    <Crown className="h-5 w-5" />
                                    <span className="text-sm font-medium opacity-90">{t('Plan')}</span>
                                </div>
                                <h2 className="text-2xl font-bold">{planName}</h2>
                            </div>
                            <div className="text-right">
                                <div className="text-3xl font-bold">
                                    {currencySymbol}{planPrice}
                                </div>
                                <span className="text-sm opacity-90">/{t(planDuration.toLowerCase())}</span>
                            </div>
                        </div>
                    </div>

                    {/* Details Section */}
                    <div className="p-6 space-y-6">
                        {/* Plan Details Grid */}
                        <div className="grid grid-cols-2 gap-4">
                            {issuedTo && (
                                <div className="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                                    <div className="flex items-center gap-2 text-gray-500 dark:text-gray-400 mb-1">
                                        <User className="h-4 w-4" />
                                        <span className="text-xs font-medium uppercase tracking-wide">{t('Issued To')}</span>
                                    </div>
                                    <p className="text-sm font-semibold text-gray-900 dark:text-white truncate">{issuedTo}</p>
                                </div>
                            )}
                            <div className="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                                <div className="flex items-center gap-2 text-gray-500 dark:text-gray-400 mb-1">
                                    <Calendar className="h-4 w-4" />
                                    <span className="text-xs font-medium uppercase tracking-wide">{t('Expires')}</span>
                                </div>
                                <p className="text-sm font-semibold text-gray-900 dark:text-white">{expiresAt}</p>
                            </div>
                            {isActivated && (
                                <div className="bg-green-50 dark:bg-green-900/20 rounded-xl p-4">
                                    <div className="flex items-center gap-2 text-green-600 dark:text-green-400 mb-1">
                                        <Check className="h-4 w-4" />
                                        <span className="text-xs font-medium uppercase tracking-wide">{t('Status')}</span>
                                    </div>
                                    <p className="text-sm font-semibold text-green-700 dark:text-green-300">{t('Activated')}</p>
                                </div>
                            )}
                            {licenseId && (
                                <div className="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                                    <div className="flex items-center gap-2 text-gray-500 dark:text-gray-400 mb-1">
                                        <Key className="h-4 w-4" />
                                        <span className="text-xs font-medium uppercase tracking-wide">{t('License ID')}</span>
                                    </div>
                                    <p className="text-xs font-mono text-gray-900 dark:text-white truncate">{licenseId}</p>
                                </div>
                            )}
                        </div>

                        {/* License Key Display */}
                        <div className="space-y-3">
                            <label className="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <Key className="h-4 w-4 text-primary" />
                                {t('Your License Key')}
                            </label>
                            <div className="relative">
                                <div className="flex items-center gap-2 bg-gray-900 dark:bg-gray-950 rounded-xl p-5 border-2 border-gray-700">
                                    <code className="flex-1 text-center text-xl sm:text-2xl font-mono text-green-400 tracking-widest select-all break-all">
                                        {licenseKey}
                                    </code>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={copyLicenseKey}
                                        className="text-gray-400 hover:text-white hover:bg-gray-800 shrink-0"
                                    >
                                        {copied ? (
                                            <Check className="h-5 w-5 text-green-400" />
                                        ) : (
                                            <Copy className="h-5 w-5" />
                                        )}
                                    </Button>
                                </div>
                            </div>

                            {/* Warning Message */}
                            <div className="flex items-start gap-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                                <AlertTriangle className="h-5 w-5 text-amber-500 shrink-0 mt-0.5" />
                                <div>
                                    <p className="text-sm font-medium text-amber-800 dark:text-amber-200">
                                        {t('Important: Save Your License Key')}
                                    </p>
                                    <p className="text-xs text-amber-700 dark:text-amber-300 mt-1">
                                        {t('Please copy and save this license key in a secure location. You will need it to activate your application. This key is linked to your account and hardware.')}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="px-6 pb-6 space-y-3">
                        <Button
                            onClick={goToDashboard}
                            className="w-full bg-primary hover:bg-primary/90 text-white font-medium py-3 text-base"
                        >
                            {t('Go to Dashboard')}
                            <ArrowRight className="h-5 w-5 ml-2" />
                        </Button>
                        <Button
                            variant="outline"
                            onClick={copyLicenseKey}
                            className="w-full py-3 text-base"
                        >
                            {copied ? (
                                <>
                                    <Check className="h-5 w-5 mr-2 text-green-500" />
                                    {t('Copied!')}
                                </>
                            ) : (
                                <>
                                    <Copy className="h-5 w-5 mr-2" />
                                    {t('Copy License Key')}
                                </>
                            )}
                        </Button>
                    </div>
                </div>

                {/* Footer Note */}
                <div className="text-center mt-6">
                    <p className="text-sm text-gray-500 dark:text-gray-400 flex items-center justify-center gap-1.5">
                        <Sparkles className="h-4 w-4 text-primary" />
                        {t('Your license key has been saved to your account and can be accessed anytime from your profile settings.')}
                    </p>
                </div>
            </div>
        </div>
    );
}
