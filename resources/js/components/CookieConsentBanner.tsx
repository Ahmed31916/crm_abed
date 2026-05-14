import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { X } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { useBrand } from '@/contexts/BrandContext';
import { THEME_COLORS } from '@/hooks/use-appearance';
import { Switch } from '@/components/ui/switch';
import { toast } from '@/components/custom-toast';

export default function CookieConsentBanner() {
    const { t } = useTranslation();
    const { props } = usePage();
    const [isVisible, setIsVisible] = useState(false);
    const [showModal, setShowModal] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const { themeColor, customColor } = useBrand();
    const primaryColor = themeColor === 'custom' ? customColor : THEME_COLORS[themeColor as keyof typeof THEME_COLORS];

    const globalSettings = (props as any).globalSettings || {};
    const settings = {
        cookieTitle: globalSettings.cookieTitle || 'Cookie Consent',
        cookieDescription: globalSettings.cookieDescription || 'We use cookies to enhance your browsing experience and provide personalized content.',
        strictlyCookieTitle: globalSettings.strictlyCookieTitle || 'Strictly Necessary Cookies',
        strictlyCookieDescription: globalSettings.strictlyCookieDescription || 'These cookies are essential for the website to function properly.',
        contactUsDescription: globalSettings.contactUsDescription || 'If you have any questions about our cookie policy, please contact us.',
        contactUsUrl: globalSettings.contactUsUrl || '#'
    };

    useEffect(() => {
        const enableLogging = globalSettings.enableLogging === '1' || globalSettings.enableLogging === 1 || globalSettings.enableLogging === true;
        const isDemoMode = globalSettings.is_demo === '1' || globalSettings.is_demo === 1 || globalSettings.is_demo === true;
        const isDashboard = window.location.pathname.includes('/dashboard');
        const authUser = (props as any).auth?.user;

        if (!enableLogging) {
            return;
        }

        if (isDemoMode && !isDashboard) {
            return;
        }

        if (isDemoMode) {
            // Only show cookie popup if user is company type and on dashboard
            if (authUser?.type === 'company' && isDashboard) {
                const consent = localStorage.getItem('cookie-consent');
                if (!consent) {
                    setIsVisible(true);
                }
            }
        } else {
            const consent = localStorage.getItem('cookie-consent');
            if (!consent) {
                setIsVisible(true);
            }
        }
    }, []);

    const getLocationData = async () => {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout

            const response = await fetch('https://ipapi.co/json/', {
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json'
                }
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            // Validate essential data
            return {
                ip: data.ip || 'unknown',
                country: data.country_name || 'unknown',
                city: data.city || 'unknown',
                region: data.region || 'unknown',
                ...data
            };
        } catch (error: any) {
            return {
                ip: 'unknown',
                country: 'unknown',
                city: 'unknown',
                region: 'unknown',
                error: error.message || 'Location fetch failed'
            };
        }
    };

    const saveCookieConsent = async (consentType: string, preferences: any) => {
        // Try to send to backend, but don't fail if it doesn't work
        try {
            const locationData = await getLocationData();
            const consentData = {
                ...preferences,
                timestamp: new Date().toISOString(),
                consentType,
                userAgent: navigator.userAgent || 'unknown',
                language: navigator.language || 'unknown',
                url: window.location.href,
                ...locationData
            };
            // Always store in localStorage first (this is the primary requirement)
            localStorage.setItem('cookie-consent', JSON.stringify({ accepted: true, timestamp: Date.now() }));

            // Send to backend to store in CSV
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                return;
            }

            const response = await fetch(route('cookie.consent.store'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(consentData)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error('Server returned error');
            }
        } catch (error) {
        }
    };

    const acceptAll = async () => {
        if (isLoading) return;
        setIsLoading(true);

        // const preferences = {
        //   necessary: true,
        //   analytics: true,
        //   marketing: true
        // };

        // await saveCookieConsent('accept_all', preferences);
        // setIsVisible(false);
        // setIsLoading(false);

        try {
            const preferences = {
                necessary: true,
                analytics: true,
                marketing: true
            };

            await saveCookieConsent('accept_all', preferences);
            setIsVisible(false);
        } catch (error) {
            toast.error(t('Failed to save cookie preferences'));
        } finally {
            setIsLoading(false);
        }
    };

    const acceptNecessary = async () => {
        if (isLoading) return;
        setIsLoading(true);

        const preferences = {
            necessary: true,
            analytics: false,
            marketing: false
        };

        await saveCookieConsent('necessary_only', preferences);
        setIsVisible(false);
        setIsLoading(false);
    };

    if (!isVisible) return null;

    return (
        <>
            {/* Main Cookie Banner */}
            <div className="fixed bottom-4 left-1/2 transform -translate-x-1/2 z-50 max-w-md w-full">
                <Card className="p-4 shadow-lg border">
                    <div className="flex justify-between items-start mb-3">
                        <h3 className="font-semibold text-sm">
                            {settings.cookieTitle || t('Cookie Consent')}
                        </h3>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setIsVisible(false)}
                            className="h-6 w-6 p-0"
                        >
                            <X className="h-4 w-4" />
                        </Button>
                    </div>

                    <p className="text-sm text-muted-foreground mb-4">
                        {settings.cookieDescription || t('We use cookies to enhance your browsing experience and provide personalized content.')}
                    </p>

                    <div className="flex flex-col gap-2">
                        <div className="flex gap-2">
                            <Button
                                onClick={acceptAll}
                                size="sm"
                                className="flex-1 text-white hover:opacity-90"
                                style={{ backgroundColor: primaryColor }}
                                disabled={isLoading}
                            >
                                {isLoading ? t('Saving...') : t('Accept All')}
                            </Button>
                            <Button
                                onClick={acceptNecessary}
                                variant="outline"
                                size="sm"
                                className="flex-1"
                                style={{ borderColor: primaryColor, color: primaryColor }}
                                disabled={isLoading}
                            >
                                {isLoading ? t('Saving...') : t('Necessary Only')}
                            </Button>
                        </div>
                        <Button
                            onClick={() => setShowModal(true)}
                            variant="ghost"
                            size="sm"
                            className="text-sm underline"
                        >
                            {t('Let me choose')}
                        </Button>
                    </div>

                    {settings.contactUsUrl && (
                        <p className="text-xs text-muted-foreground mt-2">
                            {settings.contactUsDescription || t('Questions about our cookie policy?')}{' '}
                            <a href={settings.contactUsUrl} className="underline">
                                {t('Contact us')}
                            </a>
                        </p>
                    )}
                </Card>
            </div>

            {/* Cookie Preferences Modal */}
            {showModal && (
                <div className="fixed inset-0 z-[60] flex items-center justify-center p-4">
                    <Card className="w-full max-w-md max-h-[80vh] overflow-y-auto">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-lg font-semibold">{settings.cookieTitle || t('Cookie Preferences')}</h3>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setShowModal(false)}
                                    className="h-6 w-6 p-0"
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            </div>

                            <div className="space-y-4">
                                {/* Strictly Necessary Cookies */}
                                <div className="flex items-center justify-between">
                                    <div className="flex-1">
                                        <h4 className="font-medium text-sm">{settings.strictlyCookieTitle || t('Strictly Necessary Cookies')}</h4>
                                        <p className="text-xs text-muted-foreground mt-1">
                                            {settings.strictlyCookieDescription || t('These cookies are essential for the website to function properly.')}
                                        </p>
                                    </div>
                                    <Switch checked={true} disabled={true} />
                                </div>


                            </div>

                            <div className="flex gap-2 mt-6">
                                <Button
                                    onClick={async () => {
                                        await acceptNecessary();
                                        setShowModal(false);
                                    }}
                                    variant="outline"
                                    size="sm"
                                    className="flex-1"
                                    style={{ borderColor: primaryColor, color: primaryColor }}
                                    disabled={isLoading}
                                >
                                    {isLoading ? t('Saving...') : t('Save Preferences')}
                                </Button>
                                <Button
                                    onClick={async () => {
                                        await acceptAll();
                                        setShowModal(false);
                                    }}
                                    size="sm"
                                    className="flex-1 text-white hover:opacity-90"
                                    style={{ backgroundColor: primaryColor }}
                                    disabled={isLoading}
                                >
                                    {isLoading ? t('Saving...') : t('Accept All')}
                                </Button>
                            </div>
                        </div>
                    </Card>
                </div>
            )}
        </>
    );
}
