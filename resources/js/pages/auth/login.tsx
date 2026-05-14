import { useForm, router } from '@inertiajs/react';
import { FormEventHandler, useState, useEffect } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import AuthLayout from '@/layouts/auth-layout';
import AuthButton from '@/components/auth/auth-button';
import Recaptcha, { useRecaptchaSettings } from '@/components/recaptcha';
import { useBrand } from '@/contexts/BrandContext';
import { THEME_COLORS } from '@/hooks/use-appearance';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { isRegistrationEnabled } from '@/utils/helper';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
    recaptcha_token?: string;
};

interface Business {
    id: number;
    name: string;
    slug: string;
    business_type: string;
}

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    demoBusinesses?: Business[];
}

export default function Login({ status, canResetPassword, demoBusinesses = [] }: LoginProps) {
    const { t } = useTranslation();
    const [recaptchaToken, setRecaptchaToken] = useState<string>('');
    const { themeColor, customColor } = useBrand();
    const primaryColor = themeColor === 'custom' ? customColor : THEME_COLORS[themeColor as keyof typeof THEME_COLORS];
    const [isDemo, setIsDemo] = useState<boolean>(false);
    const { recaptchaEnabled } = useRecaptchaSettings();
    const [showRecaptchaError, setShowRecaptchaError] = useState<boolean>(false);

    // Always show business buttons by default
    const [showBusinessButtons, setShowBusinessButtons] = useState<boolean>(true);

    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    useEffect(() => {
        // Check if demo mode is enabled
        const isDemoMode = (window as any).isDemo === true;
        setIsDemo(isDemoMode);

        // Set default credentials if in demo mode
        if (isDemoMode) {
            setData({
                email: 'company@example.com',
                password: 'password',
                remember: false
            });
        }
    }, []);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        // Check if reCAPTCHA is enabled and token is missing
        if (recaptchaEnabled && !recaptchaToken) {
            setShowRecaptchaError(true);
            return;
        }

        setShowRecaptchaError(false);
        const formData = { ...data, recaptcha_token: recaptchaToken };
        post(route('login'), {
            data: formData,
            onFinish: () => reset('password')
        });
    };

    // No longer needed as we're using router.post directly in the button handlers

    const openBusinessInNewTab = (businessId: number, slug: string, e: React.MouseEvent) => {
        // Prevent the default form submission
        e.preventDefault();
        e.stopPropagation();

        // Use the same URL structure as in vcard-builder/index.tsx
        const url = route('public.vcard.show.direct', slug);
        window.open(url, '_blank');
    };

    return (
        <AuthLayout
            title={t("Log in to your account")}
            description={t("Enter your credentials to access your account")}
            status={status}
        >
            <form className="space-y-5" onSubmit={submit}>
                <div className="space-y-4">
                    <div className="relative">
                        <Label htmlFor="email" className="text-gray-700 dark:text-gray-300 font-medium mb-2 block" required>{t("Email address")}</Label>
                        <div className="relative">
                            <Input
                                id="email"
                                type="email"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder={t("Enter your email")}
                                className="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg transition-all duration-200"
                                style={{ '--tw-ring-color': primaryColor } as React.CSSProperties}
                            />
                        </div>
                        <InputError message={errors.email} />
                    </div>

                    <div>
                        <div className="flex items-center justify-between mb-2">
                            <Label htmlFor="password" className="text-gray-700 dark:text-gray-300 font-medium" required>{t("Password")}</Label>
                            {canResetPassword && (
                                <TextLink
                                    href={route('password.request')}
                                    className="text-sm transition-colors duration-200 no-underline hover:underline hover:underline-primary"
                                    style={{ color: primaryColor }}
                                    tabIndex={5}
                                >
                                    {t("Forgot password?")}
                                </TextLink>
                            )}
                        </div>
                        <div className="relative">
                            <Input
                                id="password"
                                type="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder={t("Enter your password")}
                                className="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg transition-all duration-200"
                                style={{ '--tw-ring-color': primaryColor } as React.CSSProperties}
                            />
                        </div>
                        <InputError message={errors.password} />
                    </div>

                    <div className="flex items-center !mt-4 !mb-5">
                        <Checkbox
                            id="remember"
                            name="remember"
                            checked={data.remember}
                            onClick={() => setData('remember', !data.remember)}
                            tabIndex={3}
                            className="w-[14px] h-[14px] border border-gray-300 rounded"
                        />
                        <Label htmlFor="remember" className="ml-2 text-sm text-gray-600">{t("Remember me")}</Label>
                    </div>
                </div>

                <Recaptcha
                    onVerify={(token) => {
                        setRecaptchaToken(token);
                        setShowRecaptchaError(false);
                    }}
                    onExpired={() => setRecaptchaToken('')}
                    onError={() => setRecaptchaToken('')}
                />

                {showRecaptchaError && recaptchaEnabled && !recaptchaToken && (
                    <p className="text-sm text-red-600 dark:text-red-400 text-center -mt-2">
                        {t("Please complete the reCAPTCHA verification")}
                    </p>
                )}

                <AuthButton
                    tabIndex={4}
                    processing={processing}
                    className="w-full text-white py-2.5 text-sm font-medium tracking-wide transition-all duration-200 rounded-md shadow-md hover:shadow-lg transform hover:scale-[1.02]"
                    style={{ backgroundColor: primaryColor }}
                >
                    {t("Login")}
                </AuthButton>
                {isRegistrationEnabled() && <div className="text-center">
                    <p className="text-sm text-gray-500">{t("Don't have an account?")}{' '}
                        <TextLink
                            href={route('register')}
                            className="font-medium hover:underline"
                            style={{ color: primaryColor }}
                            tabIndex={6}
                        >
                            {t("Sign up")}
                        </TextLink>
                    </p>
                </div>}

                {isDemo && (
                    <>
                        {/* Divider */}
                        <div className="my-5">
                            <div className="flex items-center">
                                <div className="flex-1 h-px bg-gray-200"></div>
                                <div className="w-2 h-2 rotate-45 mx-4" style={{ backgroundColor: primaryColor }}></div>
                                <div className="flex-1 h-px bg-gray-200"></div>
                            </div>
                        </div>

                        <div>
                            <h3 className="text-sm font-medium text-gray-900 tracking-wider mb-4 text-center">{t('Quick Access')}</h3>
                            <div className="grid grid-cols-2 gap-3">
                                <Button
                                    type="button"
                                    onClick={() => {
                                        if (recaptchaEnabled && !recaptchaToken) return;
                                        router.post(route('login'), {
                                            email: 'superadmin@example.com',
                                            password: 'password',
                                            remember: false,
                                            recaptcha_token: recaptchaToken
                                        });
                                    }}
                                    disabled={recaptchaEnabled && !recaptchaToken}
                                    className="group relative py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                                    style={{ backgroundColor: primaryColor, borderColor: primaryColor }}
                                >
                                    {t('Login as Super Admin')}
                                </Button>

                                <Button
                                    type="button"
                                    onClick={() => {
                                        if (recaptchaEnabled && !recaptchaToken) return;
                                        router.post(route('login'), {
                                            email: 'company@example.com',
                                            password: 'password',
                                            remember: false,
                                            recaptcha_token: recaptchaToken
                                        });
                                    }}
                                    disabled={recaptchaEnabled && !recaptchaToken}
                                    className="group relative py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                                    style={{ backgroundColor: primaryColor, borderColor: primaryColor }}
                                >
                                    {t('Login as Company')}
                                </Button>

                                <Button
                                    type="button"
                                    onClick={() => {
                                        if (recaptchaEnabled && !recaptchaToken) return;
                                        router.post(route('login'), {
                                            email: 'sarahjohnson@example.com',
                                            password: 'password',
                                            remember: false,
                                            recaptcha_token: recaptchaToken
                                        });
                                    }}
                                    disabled={recaptchaEnabled && !recaptchaToken}
                                    className="group relative py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                                    style={{ backgroundColor: primaryColor, borderColor: primaryColor }}
                                >
                                    {t('Login as User')}
                                </Button>
                            </div>
                        </div>
                    </>
                )}
            </form>
        </AuthLayout>
    );
}
