import { PageTemplate } from '@/components/page-template';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { useEffect, useRef, useState } from 'react';
import { User, Lock, Camera } from 'lucide-react';
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
    }
];

export default function ProfileSettings({ mustVerifyEmail, status }: { mustVerifyEmail?: boolean; status?: string }) {
    const { t } = useTranslation();
    const { auth, globalSettings } = usePage<SharedData>().props as any;
    const [activeSection, setActiveSection] = useState('profile');

    // Refs for each section
    const profileRef = useRef<HTMLDivElement>(null);
    const passwordRef = useRef<HTMLDivElement>(null);

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
                setProfileData(prev => ({ ...prev, avatar: null }));
                setProfileErrors({});
                if (!globalSettings?.is_demo) {
                    toast.dismiss();
                }
                if ((page.props as any).flash?.success) {
                    toast.success(t((page.props as any).flash.success));
                }
                else if ((page.props as any).flash?.error) {
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
                    toast.error(t('Failed to update profile: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            },
        });
    };

    // Handle avatar file selection
    const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) setProfileData(prev => ({ ...prev, avatar: file }));
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
                // else if ((page.props as any).flash?.error) {
                //   toast.error(t((page.props as any).flash.error));
                // }
            },
            onError: (errors) => {
                setPasswordErrors(errors as Record<string, string>);
                if (!globalSettings?.is_demo) {
                    toast.dismiss();
                }
                if ((errors as any).current_password) {
                    setPasswordData(prev => ({ ...prev, current_password: '' }));
                    currentPasswordInput.current?.focus();
                }
                if ((errors as any).password) {
                    setPasswordData(prev => ({ ...prev, password: '', password_confirmation: '' }));
                    passwordInput.current?.focus();
                }
                if (typeof errors === 'string') {
                    toast.error(t(errors));
                } else {
                    toast.error(t('Failed to update password: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            },
        });
    };

    // Smart scroll functionality
    useEffect(() => {
        const handleScroll = () => {
            const scrollPosition = window.scrollY + 100; // Add offset for better UX

            // Get positions of each section
            const profilePosition = profileRef.current?.offsetTop || 0;
            const passwordPosition = passwordRef.current?.offsetTop || 0;

            // Determine active section based on scroll position
            if (scrollPosition >= passwordPosition) {
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
    }, []);

    // Handle navigation click
    const handleNavClick = (href: string) => {
        const id = href.replace('#', '');
        const element = document.getElementById(id);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth' });
            setActiveSection(id);
        }
    };

    return (
        <PageTemplate
            title={t("Profile Settings")}
            url="/profile"
        >
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
                                <CardTitle className="text-lg font-semibold">{t("Profile Information")}</CardTitle>
                                <CardDescription>{t("Update your account's profile information and email address")}</CardDescription>
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
                                            <Label htmlFor="avatar" className="cursor-pointer inline-flex items-center px-4 py-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground rounded-md font-medium text-sm transition-colors">
                                                <Camera className="h-4 w-4 mr-2" />
                                                {t("Change Avatar")}
                                            </Label>
                                            <Input
                                                id="avatar"
                                                type="file"
                                                accept="image/*"
                                                onChange={handleAvatarChange}
                                                className="hidden"
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                {t("JPG, PNG, GIF up to 2MB")}
                                            </p>
                                        </div>
                                    </div>
                                    <InputError className="mt-2" message={profileErrors.avatar} />

                                    <div className="grid gap-2">
                                        <Label htmlFor="name" required>{t("Name")}</Label>
                                        <Input
                                            id="name"
                                            className="mt-1 block w-full"
                                            value={profileData.name}
                                            onChange={(e) => setProfileData(prev => ({ ...prev, name: e.target.value }))}
                                            autoComplete="name"
                                            placeholder={t("Full name")}
                                        />
                                        <InputError className="mt-2" message={profileErrors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email" required>{t("Email address")}</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            className="mt-1 block w-full"
                                            value={profileData.email}
                                            onChange={(e) => setProfileData(prev => ({ ...prev, email: e.target.value }))}
                                            autoComplete="username"
                                            placeholder={t("Email address")}
                                        />
                                        <InputError className="mt-2" message={profileErrors.email} />
                                    </div>

                                    {mustVerifyEmail && auth?.user?.email_verified_at === null && (
                                        <div>
                                            <p className="text-muted-foreground -mt-4 text-sm">
                                                {t("Your email address is unverified.")}{' '}
                                                <button
                                                    type="button"
                                                    onClick={() => route('verification.send')}
                                                    className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current dark:decoration-neutral-500 cursor-pointer"
                                                >
                                                    {t("Click here to resend the verification email.")}
                                                </button>
                                            </p>

                                            {status === 'verification-link-sent' && (
                                                <div className="mt-2 text-sm font-medium text-green-600">
                                                    {t("A new verification link has been sent to your email address.")}
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    <div className="flex items-center gap-4">
                                        <Button disabled={profileProcessing && !globalSettings?.is_demo}>{t("Save")}</Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </section>

                    {/* Password Section */}
                    <section id="password" ref={passwordRef} className="mb-16">
                        <Card className="shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-lg font-semibold">{t("Update Password")}</CardTitle>
                                <CardDescription>{t("Ensure your account is using a long, random password to stay secure")}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form id="password-form" onSubmit={updatePassword} className="space-y-6">
                                    <div className="grid gap-2">
                                        <Label htmlFor="current_password" required>{t("Current password")}</Label>
                                        <Input
                                            id="current_password"
                                            ref={currentPasswordInput}
                                            value={passwordData.current_password}
                                            onChange={(e) => setPasswordData(prev => ({ ...prev, current_password: e.target.value }))}
                                            type="password"
                                            className="mt-1 block w-full"
                                            autoComplete="current-password"
                                            placeholder="Current password"
                                        />
                                        <InputError message={passwordErrors.current_password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password" required>{t("New password")}</Label>
                                        <Input
                                            id="password"
                                            ref={passwordInput}
                                            value={passwordData.password}
                                            onChange={(e) => setPasswordData(prev => ({ ...prev, password: e.target.value }))}
                                            type="password"
                                            className="mt-1 block w-full"
                                            autoComplete="new-password"
                                            placeholder="New password"
                                        />
                                        <InputError message={passwordErrors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password_confirmation" required>{t("Confirm password")}</Label>
                                        <Input
                                            id="password_confirmation"
                                            value={passwordData.password_confirmation}
                                            onChange={(e) => setPasswordData(prev => ({ ...prev, password_confirmation: e.target.value }))}
                                            type="password"
                                            className="mt-1 block w-full"
                                            autoComplete="new-password"
                                            placeholder="Confirm password"
                                        />
                                        <InputError message={passwordErrors.password_confirmation} />
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <Button disabled={passwordProcessing && !globalSettings?.is_demo}>{t("Save")}</Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </section>
                </div>
            </div>
        </PageTemplate>
    );
}
