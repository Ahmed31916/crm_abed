import { useForm } from '@inertiajs/react';
import { Mail, Lock, User, Building2, Phone, Globe } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import AuthLayout from '@/layouts/auth-layout';
import AuthButton from '@/components/auth/auth-button';
import Recaptcha from '@/components/recaptcha';
import { useBrand } from '@/contexts/BrandContext';
import { THEME_COLORS } from '@/hooks/use-appearance';
import { isDemoMode } from '@/utils/cookie-utils';
import { Checkbox } from '@/components/ui/checkbox';
import { getTermsAndConditionsUrl } from '@/utils/helper';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type RegisterForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    terms: boolean;
    company_name: string;
    phone: string;
    country_id: string;
    hardware_id: string;
    api_environment: string;
    recaptcha_token?: string;
    plan_id?: string;
    referral_code?: string;
};

interface Country {
    id: number;
    name: string;
    code?: string;
    phone_code?: string;
}

export default function Register({ referralCode, planId, countries, hardwareId, apiEnvironment }: { 
    referralCode?: string; 
    planId?: string; 
    countries: Country[];
    hardwareId?: string;
    apiEnvironment?: string;
}) {
    const { t } = useTranslation();
    const [recaptchaToken, setRecaptchaToken] = useState<string>('');
    const { themeColor, customColor } = useBrand();
    const primaryColor = themeColor === 'custom' ? customColor : THEME_COLORS[themeColor as keyof typeof THEME_COLORS];
    const { data, setData, post, processing, errors, reset } = useForm<RegisterForm>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        terms: false,
        company_name: '',
        phone: '',
        country_id: '',
        hardware_id: hardwareId || '',
        // IMPORTANT: Only 'test' is recognized; any other value (including empty) = 'production'
        api_environment: apiEnvironment === 'test' ? 'test' : 'production',
        plan_id: planId,
        referral_code: referralCode,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            data: { ...data, recaptcha_token: recaptchaToken },
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout
            title={t("Create your account")}
            description={t("Enter your details below to get started")}
        >
            <form className="space-y-5" onSubmit={submit}>
                <div className="space-y-4">
                    {/* Full Name */}
                    <div className="relative">
                        <Label htmlFor="name" className="text-gray-700 dark:text-gray-300 font-medium mb-2 block" required>{t("Full name")}</Label>
                        <div className="relative">
                            <Input
                                id="name"
                                type="text"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder={t("Enter your full name")}
                                className="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg transition-all duration-200"
                                style={{ '--tw-ring-color': primaryColor } as React.CSSProperties}
                            />
                        </div>
                        <InputError message={errors.name} />
                    </div>

                    {/* Company Name */}
                    <div className="relative">
                        <Label htmlFor="company_name" className="text-gray-700 dark:text-gray-300 font-medium mb-2 block" required>{t("Company name")}</Label>
                        <div className="relative">
                            <Input
                                id="company_name"
                                type="text"
                                required
                                tabIndex={2}
                                value={data.company_name}
                                onChange={(e) => setData('company_name', e.target.value)}
                                placeholder={t("Enter your company name")}
                                className="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg transition-all duration-200"
                                style={{ '--tw-ring-color': primaryColor } as React.CSSProperties}
                            />
                        </div>
                        <InputError message={errors.company_name} />
                    </div>

                    {/* Phone */}
                    <div className="relative">
                        <Label htmlFor="phone" className="text-gray-700 dark:text-gray-300 font-medium mb-2 block" required>{t("Phone number")}</Label>
                        <div className="relative">
                            <Input
                                id="phone"
                                type="tel"
                                required
                                tabIndex={3}
                                autoComplete="tel"
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                                placeholder={t("Enter your phone number")}
                                className="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg transition-all duration-200"
                                style={{ '--tw-ring-color': primaryColor } as React.CSSProperties}
                            />
                        </div>
                        <InputError message={errors.phone} />
                    </div>

                    {/* Country Select */}
                    <div className="relative">
                        <Label htmlFor="country_id" className="text-gray-700 dark:text-gray-300 font-medium mb-2 block" required>{t("Country")}</Label>
                        <Select
                            value={data.country_id}
                            onValueChange={(value) => setData('country_id', value)}
                        >
                            <SelectTrigger
                                className="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg transition-all duration-200"
                                style={{ '--tw-ring-color': primaryColor } as React.CSSProperties}
                            >
                                <SelectValue placeholder={t("Select your country")} />
                            </SelectTrigger>
                            <SelectContent className="max-h-60">
                                {countries && countries.map((country) => (
                                    <SelectItem key={country.id} value={String(country.id)}>
                                        {country.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.country_id} />
                    </div>

                    {/* Email */}
                    <div className="relative">
                        <Label htmlFor="email" className="text-gray-700 dark:text-gray-300 font-medium mb-2 block" required>{t("Email address")}</Label>
                        <div className="relative">
                            <Input
                                id="email"
                                type="email"
                                required
                                tabIndex={5}
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

                    {/* Password */}
                    <div>
                        <Label htmlFor="password" className="text-gray-700 dark:text-gray-300 font-medium mb-2 block" required>{t("Password")}</Label>
                        <div className="relative">
                            <Input
                                id="password"
                                type="password"
                                required
                                tabIndex={6}
                                autoComplete="new-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder={t("Enter your password")}
                                className="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg transition-all duration-200"
                                style={{ '--tw-ring-color': primaryColor } as React.CSSProperties}
                            />
                        </div>
                        <InputError message={errors.password} />
                    </div>

                    {/* Confirm Password */}
                    <div>
                        <Label htmlFor="password_confirmation" className="text-gray-700 dark:text-gray-300 font-medium mb-2 block" required>{t("Confirm password")}</Label>
                        <div className="relative">
                            <Input
                                id="password_confirmation"
                                type="password"
                                required
                                tabIndex={7}
                                autoComplete="new-password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                placeholder={t("Confirm your password")}
                                className="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg transition-all duration-200"
                                style={{ '--tw-ring-color': primaryColor } as React.CSSProperties}
                            />
                        </div>
                        <InputError message={errors.password_confirmation} />
                    </div>

                    {/* Hidden Hardware ID */}
                    {data.hardware_id && (
                        <input type="hidden" name="hardware_id" value={data.hardware_id} />
                    )}

                    {/* Hidden API Environment */}
                    <input type="hidden" name="api_environment" value={data.api_environment} />

                    {/* Terms */}
                    <div className="flex items-center !mt-4 !mb-5">
                        <Checkbox
                            id="terms"
                            checked={data.terms}
                            onClick={() => setData('terms', !data.terms)}
                            tabIndex={8}
                            className="w-[14px] h-[14px] border border-gray-300 rounded"
                        />
                        <Label htmlFor="terms" className="ml-2 text-gray-600 dark:text-gray-400 text-sm" required>{t("I agree to the")}{' '}
                            <a
                                href={isDemoMode() ? route('home') : (getTermsAndConditionsUrl() || route('home'))}
                                target="_blank"
                                rel="noopener noreferrer"
                                style={{ color: primaryColor }}
                            >
                                {t("Terms and Conditions")}
                            </a>
                        </Label>
                    </div>
                    <InputError message={errors.terms} />
                </div>

                <Recaptcha
                    onVerify={setRecaptchaToken}
                    onExpired={() => setRecaptchaToken('')}
                    onError={() => setRecaptchaToken('')}
                />

                <AuthButton
                    tabIndex={9}
                    processing={processing}
                    className="w-full text-white py-2.5 text-sm font-medium tracking-wide transition-all duration-200 rounded-md shadow-md hover:shadow-lg transform hover:scale-[1.02]"
                    style={{ backgroundColor: primaryColor }}
                >
                    {t("Create Account")}
                </AuthButton>

                <div className="text-center">
                    <p className="text-sm text-gray-500">{t("Already have an account?")}{' '}
                        <TextLink
                            href={route('login')}
                            className="font-medium hover:underline"
                            style={{ color: primaryColor }}
                            tabIndex={10}
                        >
                            {t("Sign in")}
                        </TextLink>
                    </p>
                </div>
            </form>
        </AuthLayout>
    );
}
