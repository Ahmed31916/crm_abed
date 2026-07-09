import { PageTemplate } from '@/components/page-template';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { useEffect, useRef, useState } from 'react';
import { User, Lock, Camera, Key, Shield, Copy, Check, Calendar, Building2, Clock, AlertTriangle, Crown, Eye, EyeOff, Sparkles } from 'lucide-react';
import { usePage, router } from '@inertiajs/react';
import { type SharedData } from '@/types';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { useTranslation } from 'react-i18next';
import { getDisplayUrl } from '@/utils/helper';
import { toast } from '@/components/custom-toast';

// ============================================
// License Info type — passed from ProfileController
// ============================================
interface LicenseInfo {
    show: boolean;
    status: 'active' | 'inactive' | 'trial' | 'expired';
    licenseKey?: string;
    licenseId?: string;
    planName?: string;
    planPrice?: number | string;
    planDuration?: string;
    currencySymbol?: string;
    issuedTo?: string;
    belongsToCompany?: string | null;
    subscribedAt?: string | null;
    expiresAt?: string | null;
    daysRemaining?: number | null;
    isTrial?: boolean;
    trialExpiresAt?: string | null;
    isActivated?: boolean;
    hardwareId?: string | null;
    isStaff?: boolean;
    isLegacy?: boolean;
}

export default function ProfileSettings({
    mustVerifyEmail,
    status,
    licenseInfo,
}: {
    mustVerifyEmail?: boolean;
    status?: string;
    licenseInfo?: LicenseInfo;
}) {
    const { t } = useTranslation();
    const { auth, globalSettings } = usePage<SharedData>().props as any;
    const [activeSection, setActiveSection] = useState('profile');

    // Refs for each section
    const profileRef = useRef<HTMLDivElement>(null);
    const passwordRef = useRef<HTMLDivElement>(null);
    const licenseRef = useRef<HTMLDivElement>(null);

    // Password form refs
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    // Profile form state
    const [profileData, setProfileData] = useState({
        name: auth?.user?.name || '',
        email: auth?.user?.email || '',
        avatar: null as File | null,
    });
    const [profileErrors, setProfileErrors] = useState<Record<string, string>>({});
    const [profileProcessing, setProfileProcessing] = useState(false);

    // Password form state
    const [passwordData, setPasswordData] = useState({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const [passwordErrors, setPasswordErrors] = useState<Record<string, string>>({});
    const [passwordProcessing, setPasswordProcessing] = useState(false);

    // License state
    const [showLicenseKey, setShowLicenseKey] = useState(false);
    const [licenseCopied, setLicenseCopied] = useState(false);

    // Build sidebar nav items — License section only shown when licenseInfo.show is true
    const sidebarNavItems: NavItem[] = [
        {
            title: 'Profile',
            href: '#profile',
            icon: <User className="h-4 w-4 mr-2" />,
        },
        {
            title: 'Password',
            href: '#password',
            icon: <Lock className="h-4 w-4 mr-2" />,
        },
        ...(licenseInfo?.show
            ? [
                  {
                      title: 'License Key',
                      href: '#license',
                      icon: <Key className="h-4 w-4 mr-2" />,
                  } as NavItem,
              ]
            : []),
    ];

    // Handle profile form submission
    const submitProfile = (e: React.FormEvent) => {
        e.preventDefault();

        if (!globalSettings?.is_demo) {
            toast.loading(t('Updating profile...'));
        }
        setProfileProcessing(true);

        const formData = new FormData();
        formData.append('name', profileData.name);
        formData.append('email', profileData.email);
        formData.append('_method', 'PATCH');
        if (profileData.avatar) formData.append('avatar', profileData.avatar);

        router.post(route('profile.update'), formData, {
            preserveScroll: true,
            forceFormData: true,
            onFinish: () => setProfileProcessing(false),
            onSuccess: (page) => {
                setProfileData((prev) => ({ ...prev, avatar: null }));
                setProfileErrors({});
                if (!globalSettings?.is_demo) {
                    toast.dismiss();
                }
                if ((page.props as any).flash?.success) {
                    toast.success(t((page.props as any).flash.success));
                } else if ((page.props as any).flash?.error) {
                    toast.error(t((page.props as any).flash.error));
                }
            },
            onError: (errors) => {
                setProfileErrors(errors as Record<string, string>);
                if (!globalSettings?.is_demo) {
                    toast.dismiss();
                }
                if (typeof errors === 'string') {
                    toast.error(t(errors));
                } else {
                    toast.error(
                        t('Failed to update profile: {{errors}}', { errors: Object.values(errors).join(', ') }),
                    );
                }
            },
        });
    };

    // Handle avatar file selection
    const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) setProfileData((prev) => ({ ...prev, avatar: file }));
    };

    // Get avatar URL
    const getAvatarUrl = () => {
        if (profileData.avatar) return URL.createObjectURL(profileData.avatar);
        if (auth?.user?.avatar) return auth.user.avatar;
        return getDisplayUrl('storage/media/avatars/avatar.png');
    };

    // Handle password form submission
    const updatePassword = (e: React.FormEvent) => {
        e.preventDefault();

        if (!globalSettings?.is_demo) {
            toast.loading(t('Updating password...'));
        }
        setPasswordProcessing(true);

        router.put(route('password.update'), passwordData, {
            preserveScroll: true,
            onFinish: () => setPasswordProcessing(false),
            onSuccess: (page) => {
                setPasswordData({ current_password: '', password: '', password_confirmation: '' });
                setPasswordErrors({});
                if (!globalSettings?.is_demo) {
                    toast.dismiss();
                }
                if ((page.props as any).flash?.success) {
                    toast.success(t((page.props as any).flash.success));
                }
            },
            onError: (errors) => {
                setPasswordErrors(errors as Record<string, string>);
                if (!globalSettings?.is_demo) {
                    toast.dismiss();
                }
                if ((errors as any).current_password) {
                    setPasswordData((prev) => ({ ...prev, current_password: '' }));
                    currentPasswordInput.current?.focus();
                }
                if ((errors as any).password) {
                    setPasswordData((prev) => ({ ...prev, password: '', password_confirmation: '' }));
                    passwordInput.current?.focus();
                }
                if (typeof errors === 'string') {
                    toast.error(t(errors));
                } else {
                    toast.error(
                        t('Failed to update password: {{errors}}', { errors: Object.values(errors).join(', ') }),
                    );
                }
            },
        });
    };

    // Copy license key to clipboard
    const copyLicenseKey = async () => {
        if (!licenseInfo?.licenseKey) return;
        try {
            await navigator.clipboard.writeText(licenseInfo.licenseKey);
            setLicenseCopied(true);
            toast.success(t('License key copied to clipboard'));
            setTimeout(() => setLicenseCopied(false), 3000);
        } catch {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = licenseInfo.licenseKey;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            setLicenseCopied(true);
            toast.success(t('License key copied to clipboard'));
            setTimeout(() => setLicenseCopied(false), 3000);
        }
    };

    // Smart scroll functionality
    useEffect(() => {
        const handleScroll = () => {
            const scrollPosition = window.scrollY + 100; // Add offset for better UX

            // Get positions of each section
            const profilePosition = profileRef.current?.offsetTop || 0;
            const passwordPosition = passwordRef.current?.offsetTop || 0;
            const licensePosition = licenseRef.current?.offsetTop || 0;

            // Determine active section based on scroll position
            if (licenseInfo?.show && scrollPosition >= licensePosition) {
                setActiveSection('license');
            } else if (scrollPosition >= passwordPosition) {
                setActiveSection('password');
            } else {
                setActiveSection('profile');
            }
        };

        // Add scroll event listener
        window.addEventListener('scroll', handleScroll);

        // Initial check for hash in URL
        const hash = window.location.hash.replace('#', '');
        if (hash) {
            const element = document.getElementById(hash);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
                setActiveSection(hash);
            }
        }

        return () => {
            window.removeEventListener('scroll', handleScroll);
        };
    }, [licenseInfo?.show]);

    // Handle navigation click
    const handleNavClick = (href: string) => {
        const id = href.replace('#', '');
        const element = document.getElementById(id);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth' });
            setActiveSection(id);
        }
    };

    // ============================================
    // License section helpers
    // ============================================
    const getStatusBadge = () => {
        if (!licenseInfo) return null;
        const { status, isTrial } = licenseInfo;

        const badgeConfig = {
            active: {
                label: t('Active'),
                className: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800',
                icon: <Check className="h-3.5 w-3.5" />,
            },
            trial: {
                label: t('Trial'),
                className: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border-blue-200 dark:border-blue-800',
                icon: <Clock className="h-3.5 w-3.5" />,
            },
            expired: {
                label: t('Expired'),
                className: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800',
                icon: <AlertTriangle className="h-3.5 w-3.5" />,
            },
            inactive: {
                label: t('Inactive'),
                className: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400 border-gray-200 dark:border-gray-700',
                icon: <AlertTriangle className="h-3.5 w-3.5" />,
            },
        };

        const cfg = badgeConfig[status] || badgeConfig.inactive;

        return (
            <span
                className={cn(
                    'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium border',
                    cfg.className,
                )}
            >
                {cfg.icon}
                {cfg.label}
            </span>
        );
    };

    const getDaysRemainingLabel = () => {
        if (!licenseInfo?.daysRemaining && licenseInfo?.daysRemaining !== 0) return null;
        const days = licenseInfo.daysRemaining;

        if (days < 0) {
            return (
                <span className="text-sm text-red-600 dark:text-red-400 font-medium">
                    {t('Expired {{days}} days ago', { days: Math.abs(days) })}
                </span>
            );
        }
        if (days === 0) {
            return (
                <span className="text-sm text-amber-600 dark:text-amber-400 font-medium">
                    {t('Expires today')}
                </span>
            );
        }
        if (days <= 7) {
            return (
                <span className="text-sm text-amber-600 dark:text-amber-400 font-medium">
                    {t('{{days}} days remaining', { days })}
                </span>
            );
        }
        return (
            <span className="text-sm text-muted-foreground">
                {t('{{days}} days remaining', { days })}
            </span>
        );
    };

    // Mask the license key: show only first 4 and last 4 chars
    const getMaskedLicenseKey = () => {
        if (!licenseInfo?.licenseKey) return '';
        const key = licenseInfo.licenseKey;
        if (key.length <= 12) return key;
        return `${key.substring(0, 4)}${'•'.repeat(Math.max(8, key.length - 8))}${key.substring(key.length - 4)}`;
    };

    return (
        <PageTemplate title={t('Profile Settings')} url="/profile">
            <div className="flex flex-col md:flex-row gap-8">
                {/* Sidebar */}
                <div className="md:w-64 flex-shrink-0">
                    <div className="sticky top-20">
                        <div className="space-y-1">
                            {sidebarNavItems.map((item) => (
                                <Button
                                    key={item.href}
                                    variant="ghost"
                                    className={cn('w-full justify-start text-sm', {
                                        'bg-muted font-semibold': activeSection === item.href.replace('#', ''),
                                    })}
                                    onClick={() => handleNavClick(item.href)}
                                >
                                    {item.icon}
                                    {item.title}
                                </Button>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="flex-1">
                    {/* Profile Section */}
                    <section id="profile" ref={profileRef} className="mb-16">
                        <Card className="shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-lg font-semibold">{t('Profile Information')}</CardTitle>
                                <CardDescription>
                                    {t("Update your account's profile information and email address")}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form id="profile-form" onSubmit={submitProfile} className="space-y-6">
                                    {/* Avatar Upload Section */}
                                    <div className="flex items-center space-x-6">
                                        <Avatar className="h-20 w-20">
                                            <AvatarImage
                                                src={getAvatarUrl()}
                                                alt={auth?.user?.name || 'Avatar'}
                                                onError={(e) => {
                                                    // Fallback to default avatar on error
                                                    const target = e.target as HTMLImageElement;
                                                    target.src = getDisplayUrl('storage/media/avatars/avatar.png');
                                                }}
                                            />
                                            <AvatarFallback className="text-lg">
                                                {auth?.user?.name?.charAt(0)?.toUpperCase() || 'U'}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div className="flex flex-col space-y-2">
                                            <Label
                                                htmlFor="avatar"
                                                className="cursor-pointer inline-flex items-center px-4 py-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground rounded-md font-medium text-sm transition-colors"
                                            >
                                                <Camera className="h-4 w-4 mr-2" />
                                                {t('Change Avatar')}
                                            </Label>
                                            <Input
                                                id="avatar"
                                                type="file"
                                                accept="image/*"
                                                onChange={handleAvatarChange}
                                                className="hidden"
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                {t('JPG, PNG, GIF up to 2MB')}
                                            </p>
                                        </div>
                                    </div>
                                    <InputError className="mt-2" message={profileErrors.avatar} />

                                    <div className="grid gap-2">
                                        <Label htmlFor="name" required>
                                            {t('Name')}
                                        </Label>
                                        <Input
                                            id="name"
                                            className="mt-1 block w-full"
                                            value={profileData.name}
                                            onChange={(e) => setProfileData((prev) => ({ ...prev, name: e.target.value }))}
                                            autoComplete="name"
                                            placeholder={t('Full name')}
                                        />
                                        <InputError className="mt-2" message={profileErrors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email" required>
                                            {t('Email address')}
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            className="mt-1 block w-full"
                                            value={profileData.email}
                                            onChange={(e) => setProfileData((prev) => ({ ...prev, email: e.target.value }))}
                                            autoComplete="username"
                                            placeholder={t('Email address')}
                                        />
                                        <InputError className="mt-2" message={profileErrors.email} />
                                    </div>

                                    {mustVerifyEmail && auth?.user?.email_verified_at === null && (
                                        <div>
                                            <p className="text-muted-foreground -mt-4 text-sm">
                                                {t('Your email address is unverified.')}{' '}
                                                <button
                                                    type="button"
                                                    onClick={() => route('verification.send')}
                                                    className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current dark:decoration-neutral-500 cursor-pointer"
                                                >
                                                    {t('Click here to resend the verification email.')}
                                                </button>
                                            </p>

                                            {status === 'verification-link-sent' && (
                                                <div className="mt-2 text-sm font-medium text-green-600">
                                                    {t('A new verification link has been sent to your email address.')}
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    <div className="flex items-center gap-4">
                                        <Button disabled={profileProcessing && !globalSettings?.is_demo}>
                                            {t('Save')}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </section>

                    {/* Password Section */}
                    <section id="password" ref={passwordRef} className="mb-16">
                        <Card className="shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-lg font-semibold">{t('Update Password')}</CardTitle>
                                <CardDescription>
                                    {t('Ensure your account is using a long, random password to stay secure')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form id="password-form" onSubmit={updatePassword} className="space-y-6">
                                    <div className="grid gap-2">
                                        <Label htmlFor="current_password" required>
                                            {t('Current password')}
                                        </Label>
                                        <Input
                                            id="current_password"
                                            ref={currentPasswordInput}
                                            value={passwordData.current_password}
                                            onChange={(e) =>
                                                setPasswordData((prev) => ({ ...prev, current_password: e.target.value }))
                                            }
                                            type="password"
                                            className="mt-1 block w-full"
                                            autoComplete="current-password"
                                            placeholder="Current password"
                                        />
                                        <InputError message={passwordErrors.current_password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password" required>
                                            {t('New password')}
                                        </Label>
                                        <Input
                                            id="password"
                                            ref={passwordInput}
                                            value={passwordData.password}
                                            onChange={(e) =>
                                                setPasswordData((prev) => ({ ...prev, password: e.target.value }))
                                            }
                                            type="password"
                                            className="mt-1 block w-full"
                                            autoComplete="new-password"
                                            placeholder="New password"
                                        />
                                        <InputError message={passwordErrors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password_confirmation" required>
                                            {t('Confirm password')}
                                        </Label>
                                        <Input
                                            id="password_confirmation"
                                            value={passwordData.password_confirmation}
                                            onChange={(e) =>
                                                setPasswordData((prev) => ({
                                                    ...prev,
                                                    password_confirmation: e.target.value,
                                                }))
                                            }
                                            type="password"
                                            className="mt-1 block w-full"
                                            autoComplete="new-password"
                                            placeholder="Confirm password"
                                        />
                                        <InputError message={passwordErrors.password_confirmation} />
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <Button disabled={passwordProcessing && !globalSettings?.is_demo}>
                                            {t('Save')}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </section>

                    {/* ============================================ */}
                    {/* License Key Section */}
                    {/* ============================================ */}
                    {licenseInfo?.show && (
                        <section id="license" ref={licenseRef} className="mb-16">
                            <Card className="shadow-sm border-primary/20">
                                <CardHeader>
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="space-y-1">
                                            <CardTitle className="text-lg font-semibold flex items-center gap-2">
                                                <div className="flex items-center justify-center h-8 w-8 rounded-lg bg-primary/10 text-primary">
                                                    <Shield className="h-4 w-4" />
                                                </div>
                                                {t('License Key')}
                                            </CardTitle>
                                            <CardDescription>
                                                {t('Your subscription details and license key information')}
                                            </CardDescription>
                                        </div>
                                        {getStatusBadge()}
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    {/* Plan Header (gradient) */}
                                    {licenseInfo.planName && (
                                        <div className="bg-gradient-to-r from-primary to-primary/80 rounded-xl p-5 text-white">
                                            <div className="flex items-center justify-between gap-4">
                                                <div>
                                                    <div className="flex items-center gap-2 mb-1 opacity-90">
                                                        <Crown className="h-4 w-4" />
                                                        <span className="text-xs font-medium uppercase tracking-wide">
                                                            {t('Plan')}
                                                        </span>
                                                    </div>
                                                    <h3 className="text-xl font-bold">{licenseInfo.planName}</h3>
                                                    {licenseInfo.planDuration && (
                                                        <p className="text-xs opacity-80 mt-1">
                                                            {t(licenseInfo.planDuration.toLowerCase())}
                                                        </p>
                                                    )}
                                                </div>
                                                {licenseInfo.planPrice !== undefined && (
                                                    <div className="text-right">
                                                        <div className="text-2xl font-bold">
                                                            {licenseInfo.currencySymbol || '$'}
                                                            {licenseInfo.planPrice}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    )}

                                    {/* Details Grid */}
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        {/* Issued To */}
                                        {licenseInfo.issuedTo && (
                                            <div className="bg-muted/40 dark:bg-muted/20 rounded-xl p-4">
                                                <div className="flex items-center gap-2 text-muted-foreground mb-1.5">
                                                    <User className="h-4 w-4" />
                                                    <span className="text-xs font-medium uppercase tracking-wide">
                                                        {t('Issued To')}
                                                    </span>
                                                </div>
                                                <p className="text-sm font-semibold truncate">
                                                    {licenseInfo.issuedTo}
                                                </p>
                                            </div>
                                        )}

                                        {/* Belongs To Company */}
                                        <div className="bg-muted/40 dark:bg-muted/20 rounded-xl p-4">
                                            <div className="flex items-center gap-2 text-muted-foreground mb-1.5">
                                                <Building2 className="h-4 w-4" />
                                                <span className="text-xs font-medium uppercase tracking-wide">
                                                    {t('Belongs To')}
                                                </span>
                                            </div>
                                            <p className="text-sm font-semibold truncate">
                                                {licenseInfo.belongsToCompany || t('Self (Company Owner)')}
                                            </p>
                                            {licenseInfo.isStaff && (
                                                <p className="text-xs text-muted-foreground mt-1">
                                                    {t('Staff account under parent company')}
                                                </p>
                                            )}
                                        </div>

                                        {/* Subscribed At */}
                                        <div className="bg-muted/40 dark:bg-muted/20 rounded-xl p-4">
                                            <div className="flex items-center gap-2 text-muted-foreground mb-1.5">
                                                <Calendar className="h-4 w-4" />
                                                <span className="text-xs font-medium uppercase tracking-wide">
                                                    {t('Subscribed Since')}
                                                </span>
                                            </div>
                                            <p className="text-sm font-semibold">
                                                {licenseInfo.subscribedAt || t('N/A')}
                                            </p>
                                        </div>

                                        {/* Expires At */}
                                        <div className="bg-muted/40 dark:bg-muted/20 rounded-xl p-4">
                                            <div className="flex items-center gap-2 text-muted-foreground mb-1.5">
                                                <Calendar className="h-4 w-4" />
                                                <span className="text-xs font-medium uppercase tracking-wide">
                                                    {t('Expires At')}
                                                </span>
                                            </div>
                                            <p className="text-sm font-semibold">
                                                {licenseInfo.expiresAt || t('N/A')}
                                            </p>
                                            {getDaysRemainingLabel() && (
                                                <div className="mt-1.5">{getDaysRemainingLabel()}</div>
                                            )}
                                        </div>

                                        {/* Activation Status */}
                                        <div
                                            className={cn(
                                                'rounded-xl p-4 border',
                                                licenseInfo.isActivated
                                                    ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
                                                    : 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800',
                                            )}
                                        >
                                            <div
                                                className={cn(
                                                    'flex items-center gap-2 mb-1.5',
                                                    licenseInfo.isActivated
                                                        ? 'text-green-600 dark:text-green-400'
                                                        : 'text-amber-600 dark:text-amber-400',
                                                )}
                                            >
                                                <Check className="h-4 w-4" />
                                                <span className="text-xs font-medium uppercase tracking-wide">
                                                    {t('Activation')}
                                                </span>
                                            </div>
                                            <p
                                                className={cn(
                                                    'text-sm font-semibold',
                                                    licenseInfo.isActivated
                                                        ? 'text-green-700 dark:text-green-300'
                                                        : 'text-amber-700 dark:text-amber-300',
                                                )}
                                            >
                                                {licenseInfo.isActivated ? t('Activated') : t('Not Activated')}
                                            </p>
                                            {licenseInfo.isActivated && licenseInfo.hardwareId && (
                                                <p className="text-xs text-muted-foreground mt-1 font-mono truncate">
                                                    {licenseInfo.hardwareId}
                                                </p>
                                            )}
                                        </div>

                                        {/* License ID */}
                                        {licenseInfo.licenseId && (
                                            <div className="bg-muted/40 dark:bg-muted/20 rounded-xl p-4">
                                                <div className="flex items-center gap-2 text-muted-foreground mb-1.5">
                                                    <Key className="h-4 w-4" />
                                                    <span className="text-xs font-medium uppercase tracking-wide">
                                                        {t('License ID')}
                                                    </span>
                                                </div>
                                                <p className="text-xs font-mono truncate" title={licenseInfo.licenseId}>
                                                    {licenseInfo.licenseId}
                                                </p>
                                            </div>
                                        )}
                                    </div>

                                    {/* Trial Info (if applicable) */}
                                    {licenseInfo.isTrial && licenseInfo.trialExpiresAt && (
                                        <div className="flex items-start gap-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                                            <Sparkles className="h-5 w-5 text-blue-500 shrink-0 mt-0.5" />
                                            <div>
                                                <p className="text-sm font-medium text-blue-800 dark:text-blue-200">
                                                    {t('Trial Period Active')}
                                                </p>
                                                <p className="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                                    {t('Your trial expires on {{date}}.', { date: licenseInfo.trialExpiresAt })}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {/* License Key Display */}
                                    {licenseInfo.licenseKey && (
                                        <div className="space-y-3">
                                            <Label className="text-sm font-semibold flex items-center gap-2">
                                                <Key className="h-4 w-4 text-primary" />
                                                {t('Your License Key')}
                                            </Label>
                                            <div className="relative">
                                                <div className="flex items-center gap-2 bg-gray-900 dark:bg-gray-950 rounded-xl p-4 border-2 border-gray-700">
                                                    <code className="flex-1 text-center text-base sm:text-lg font-mono text-green-400 tracking-widest select-all break-all">
                                                        {showLicenseKey
                                                            ? licenseInfo.licenseKey
                                                            : getMaskedLicenseKey()}
                                                    </code>
                                                    <div className="flex items-center gap-1 shrink-0">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => setShowLicenseKey((s) => !s)}
                                                            className="text-gray-400 hover:text-white hover:bg-gray-800"
                                                            title={showLicenseKey ? t('Hide') : t('Show')}
                                                        >
                                                            {showLicenseKey ? (
                                                                <EyeOff className="h-4 w-4" />
                                                            ) : (
                                                                <Eye className="h-4 w-4" />
                                                            )}
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={copyLicenseKey}
                                                            className="text-gray-400 hover:text-white hover:bg-gray-800"
                                                            title={t('Copy')}
                                                        >
                                                            {licenseCopied ? (
                                                                <Check className="h-4 w-4 text-green-400" />
                                                            ) : (
                                                                <Copy className="h-4 w-4" />
                                                            )}
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                {t(
                                                    'Keep this key secure. It is required to activate your application and is linked to your account.',
                                                )}
                                            </p>
                                        </div>
                                    )}

                                    {/* Legacy user notice */}
                                    {licenseInfo.isLegacy && (
                                        <div className="flex items-start gap-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                                            <AlertTriangle className="h-5 w-5 text-amber-500 shrink-0 mt-0.5" />
                                            <div>
                                                <p className="text-sm font-medium text-amber-800 dark:text-amber-200">
                                                    {t('Legacy License Import')}
                                                </p>
                                                <p className="text-xs text-amber-700 dark:text-amber-300 mt-1">
                                                    {t(
                                                        'This license was imported from a desktop activation. Some plan details may be limited.',
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {/* No license key state */}
                                    {!licenseInfo.licenseKey && (
                                        <div className="flex flex-col items-center justify-center py-8 text-center">
                                            <div className="flex items-center justify-center h-12 w-12 rounded-full bg-muted mb-3">
                                                <Key className="h-6 w-6 text-muted-foreground" />
                                            </div>
                                            <p className="text-sm font-medium text-muted-foreground">
                                                {t('No license key assigned yet.')}
                                            </p>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="mt-3"
                                                onClick={() => router.get(route('plans.index'))}
                                            >
                                                {t('Choose a Plan')}
                                            </Button>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </section>
                    )}
                </div>
            </div>
        </PageTemplate>
    );
}
