import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import ReactCountryFlag from 'react-country-flag';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Globe, Plus, Settings } from 'lucide-react';
import { usePage, router } from '@inertiajs/react';
import { hasRole } from '@/utils/authorization';
import { CreateLanguageModal } from '@/components/create-language-modal';
import { useLayout } from '@/contexts/LayoutContext';
import { isDemoMode, setCookie, getCookie } from '@/utils/cookie-utils';

interface Language {
    code: string;
    name: string;
    countryCode: string;
    enabled?: boolean;
}

export const LanguageSwitcher: React.FC = () => {
    const { t, i18n } = useTranslation();
    const { auth, globalSettings } = usePage().props as any;
    const { updatePosition } = useLayout();
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [currentLanguage, setCurrentLanguage] = useState<Language | null>(null);

    const availableLanguages = globalSettings?.availableLanguages || [];

    useEffect(() => {
        const languages = (availableLanguages || []).filter((l: any) => l.enabled !== false);
        const lang = languages.find((l: Language) => l.code === i18n.language) || languages[0];
        setCurrentLanguage(lang);
    }, [i18n.language, availableLanguages]);

    const isAuthenticated = auth?.user;
    const userRoles = auth?.roles || [];
    const isSuperAdmin = isAuthenticated && hasRole('superadmin', userRoles);

    // Allow company users to manage languages when not in SaaS mode
    const isCompanyUser = userRoles.includes('company');

    // RTL languages list
    const rtlLanguages = ['ar', 'he'];

    const handleLanguageChange = async (languageCode: string) => {
        const lang = availableLanguages.find((l: Language) => l.code === languageCode);
        if (lang) {
            setCurrentLanguage(lang);
            try {
                await i18n.changeLanguage(languageCode);

                const isRtl = rtlLanguages.includes(languageCode);
                const newDirection = isRtl ? 'right' : 'left';

                document.documentElement.dir = 'ltr';
                document.documentElement.setAttribute('dir', 'ltr');
                updatePosition(newDirection as 'left' | 'right');

                // Save layoutDirection to database/cookies when RTL language is selected
                if (isAuthenticated && !isDemoMode()) {
                    // Save language change for authenticated non-demo users
                    router.post(route('languages.change'), {
                        language: languageCode
                    }, {
                        preserveScroll: true,
                        onSuccess: () => {
                            // After language is saved, update layoutDirection if RTL
                            router.post(route('settings.brand.update'), {
                                settings: {
                                    titleText:globalSettings?.titleText || 'Sales Saas',
                                    footerText:globalSettings?.footerText || '© 2026 Sales SaaS. All rights reserved.',
                                    layoutDirection: newDirection
                                }
                            }, {
                                preserveScroll: true,
                                onError: (errors) => {
                                }
                            });
                        },
                        onError: (errors) => {
                        }
                    });
                } else {
                    // For demo mode or non-authenticated users, save to cookies
                    setCookie('app_language', languageCode);
                    setCookie('layoutDirection', newDirection);
                }

                window.dispatchEvent(new CustomEvent('languageChanged', {
                    detail: { language: languageCode, direction: newDirection }
                }));

                window.dispatchEvent(new Event('resize'));
            } catch (error) {
            }
        }
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="flex items-center gap-2 rounded-md shadow-sm border bg-white">
                    <Globe className="h-4 w-4" />
                    {currentLanguage && (
                        <>
                            <span className="text-sm font-medium hidden md:inline-block">
                                {currentLanguage.name}
                            </span>
                            <ReactCountryFlag
                                countryCode={currentLanguage.countryCode}
                                svg
                                style={{
                                    width: '1.2em',
                                    height: '1.2em',
                                }}
                            />
                        </>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent className="w-56" align="end" forceMount>
                <DropdownMenuGroup>
                    <div className="max-h-48 overflow-y-auto">
                        {(availableLanguages || []).filter((language: any) => language.enabled !== false).map((language: Language) => (
                            <DropdownMenuItem
                                key={language.code}
                                onClick={() => handleLanguageChange(language.code)}
                                className={`flex items-center gap-2 ${currentLanguage?.code === language.code ? 'bg-accent' : ''}`}
                            >
                                <ReactCountryFlag
                                    countryCode={language.countryCode}
                                    svg
                                    style={{
                                        width: '1.2em',
                                        height: '1.2em',
                                    }}
                                />
                                <span>{language.name}</span>
                            </DropdownMenuItem>
                        ))}
                    </div>
                </DropdownMenuGroup>
                {isSuperAdmin && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            onClick={() => setShowCreateModal(true)}
                            className="justify-start text-primary font-semibold cursor-pointer"
                        >
                            <Plus className="h-4 w-4 mr-2" />
                            {t('Create Language')}
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild className="justify-start text-primary font-semibold cursor-pointer">
                            <a href={route('manage-language')} rel="noopener noreferrer">
                                <Settings className="h-4 w-4 mr-2" />
                                {t('Manage Language')}
                            </a>
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
            <CreateLanguageModal
                open={showCreateModal}
                onOpenChange={setShowCreateModal}
                onSuccess={() => setShowCreateModal(false)}
            />
        </DropdownMenu>
    );
};
