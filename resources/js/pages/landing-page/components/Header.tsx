import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { useBrand } from '@/contexts/BrandContext';
import { useBrandTheme } from '@/hooks/use-brand-theme';
import { getDisplayUrl, isRegistrationEnabled } from '@/utils/helper';

interface CustomPage {
    id: number;
    title: string;
    slug: string;
}

interface HeaderProps {
    brandColor?: string;
    settings: {
        company_name: string;
        config_sections:
        {
            theme: {
                logo_dark: string;
                logo_light: string;
            }
        }
    };
    sectionData?: any;
    customPages?: CustomPage[];
}

export default function Header({ settings, sectionData, customPages = [], brandColor = '#3b82f6' }: HeaderProps) {
    const { t } = useTranslation();
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isScrolled, setIsScrolled] = useState(false);
    const { auth } = usePage().props as any;

    useEffect(() => {
        const handleScroll = () => {
            setIsScrolled(window.scrollY > 20);
        };
        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    const menuItems = customPages.map(page => ({
        name: page.title,
        href: route('custom-page.show', page.slug)
    }));

    const isTransparent = sectionData?.transparent;
    const backgroundColor = sectionData?.background_color || '#ffffff';
    const textColor = sectionData?.text_color || '#1f2937';
    const buttonStyle = sectionData?.button_style || 'solid';

    const getHeaderClasses = () => {
        if (isTransparent) {
            return isScrolled
                ? 'bg-white/95 backdrop-blur-xl shadow-lg border-b border-gray-200/50'
                : 'bg-transparent';
        }
        return isScrolled
            ? 'shadow-lg border-b border-gray-200/50'
            : '';
    };

    const getHeaderStyle = () => {
        if (isTransparent) return {};
        return { backgroundColor };
    };

    const getButtonStyles = () => {
        if (buttonStyle === 'outline') {
            return {
                default: { backgroundColor: 'transparent', color: brandColor, borderColor: brandColor },
                hover: { backgroundColor: brandColor, color: 'white' },
                hoverLeave: { backgroundColor: 'transparent', color: brandColor }
            };
        }
        if (buttonStyle === 'gradient') {
            return {
                default: { background: `linear-gradient(120deg, ${brandColor} 50%, #ffffff 100%)`, color: 'white', borderColor: brandColor },
                hover: { background: `linear-gradient(120deg, #ffffff 50%, ${brandColor} 100%)`, color: brandColor },
                hoverLeave: { background: `linear-gradient(120deg, ${brandColor} 50%, #ffffff 100%)`, color: 'white' }
            };
        }
        // solid
        return {
            default: { backgroundColor: brandColor, color: 'white', borderColor: brandColor },
            hover: { backgroundColor: 'white', color: brandColor },
            hoverLeave: { backgroundColor: brandColor, color: 'white' }
        };
    };

    const btnStyles = getButtonStyles();

    return (
        <header
            className={`sticky top-0 left-0 right-0 z-50 transition-all duration-300 ${getHeaderClasses()}`}
            style={getHeaderStyle()}
        >
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center h-16">
                    {/* Logo */}
                    <div className="flex-shrink-0">
                        <Link
                            href={route("home")}
                            className="text-2xl font-bold lg:max-w-[180px] max-w-[140px]"
                            onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                            onMouseLeave={(e) => e.currentTarget.style.color = ''}
                        >
                            {(() => {
                                const isDark = document.documentElement.classList.contains('dark');
                                const currentLogo = isDark ? settings.config_sections.theme.logo_light : settings.config_sections.theme.logo_dark;
                                const displayUrl = currentLogo ? getDisplayUrl(currentLogo) : '';

                                return displayUrl ? (
                                    <img
                                        key={`${currentLogo}-${Date.now()}`}
                                        src={displayUrl}
                                        alt="Logo"
                                        className="h-8 w-auto transition-all duration-200"
                                    />
                                ) : (
                                    <div className="h-12 text-inherit font-semibold flex items-center text-lg tracking-tight">
                                        Sales Saas
                                    </div>
                                );
                            })()}
                        </Link>
                    </div>

                    {/* Desktop Navigation */}
                    <nav className="hidden md:flex items-center space-x-8" role="navigation" aria-label="Main navigation">
                        <Link
                            href={route('home')}
                            className="text-sm font-medium transition-colors relative group"
                            style={{ color: textColor }}
                            onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                            onMouseLeave={(e) => e.currentTarget.style.color = textColor}
                        >
                            {t("Home")}
                            <span
                                className="absolute -bottom-1 left-0 w-0 h-0.5 transition-all group-hover:w-full"
                                style={{ backgroundColor: brandColor }}
                                aria-hidden="true"
                            ></span>
                        </Link>
                        {menuItems.map((item) => (
                            <Link
                                key={item.name}
                                href={item.href}
                                className="text-sm font-medium transition-colors relative group"
                                style={{ color: textColor }}
                                onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                                onMouseLeave={(e) => e.currentTarget.style.color = textColor}
                            >
                                {item.name}
                                <span
                                    className="absolute -bottom-1 left-0 w-0 h-0.5 transition-all group-hover:w-full"
                                    style={{ backgroundColor: brandColor }}
                                    aria-hidden="true"
                                ></span>
                            </Link>
                        ))}
                    </nav>

                    {/* Auth Buttons */}
                    <div className="hidden md:flex items-center gap-4">
                        {!auth?.user ?
                            <>
                                <Link
                                    href={route('login')}
                                    className="text-sm font-medium transition-colors"
                                    style={{ color: textColor }}
                                    onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                                    onMouseLeave={(e) => e.currentTarget.style.color = textColor}
                                >
                                    {t("Login")}
                                </Link>
                                {isRegistrationEnabled() && <Link
                                    href={route('register')}
                                    className="px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors border"
                                    style={btnStyles.default}
                                    onMouseEnter={(e) => { Object.assign(e.currentTarget.style, btnStyles.hover); }}
                                    onMouseLeave={(e) => { Object.assign(e.currentTarget.style, btnStyles.hoverLeave); }}
                                >
                                    {t("Get Started")}
                                </Link>}
                            </> : <Link
                                href={route('dashboard')}
                                className="px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors border"
                                style={btnStyles.default}
                                onMouseEnter={(e) => { Object.assign(e.currentTarget.style, btnStyles.hover); }}
                                onMouseLeave={(e) => { Object.assign(e.currentTarget.style, btnStyles.hoverLeave); }}
                            >
                                {t("Dashboard")}
                            </Link>
                        }
                    </div>

                    {/* Mobile menu button */}
                    <div className="md:hidden">
                        <button
                            onClick={() => setIsMenuOpen(!isMenuOpen)}
                            className="p-2 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer"
                            style={{ color: textColor }}
                            aria-label={isMenuOpen ? t('Close navigation menu') : t('Open navigation menu')}
                            aria-expanded={isMenuOpen}
                            aria-controls="mobile-menu"
                        >
                            {isMenuOpen ? <X size={20} /> : <Menu size={20} />}
                        </button>
                    </div>
                </div>

                {/* Mobile Navigation */}
                {isMenuOpen && (
                    <div className="md:hidden border-t border-gray-200" id="mobile-menu">
                        <div
                            className="px-4 py-6 space-y-4"
                            style={isTransparent ? { backgroundColor: 'white' } : { backgroundColor }}
                        >
                            <Link
                                href={route('home')}
                                className="block text-base font-medium transition-colors"
                                style={{ color: textColor }}
                                onClick={() => setIsMenuOpen(false)}
                            >
                                {t("Home")}
                            </Link>
                            {menuItems.map((item) => (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className="block text-base font-medium transition-colors"
                                    style={{ color: textColor }}
                                    onClick={() => setIsMenuOpen(false)}
                                >
                                    {item.name}
                                </Link>
                            ))}
                            <div className="pt-4 space-y-3 border-t border-gray-200">
                                <Link
                                    href={route('login')}
                                    className="block w-full text-center py-2.5 text-sm font-medium transition-colors"
                                    style={{ color: textColor }}
                                    onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                                    onMouseLeave={(e) => e.currentTarget.style.color = textColor}
                                >
                                    {t("Login")}
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="block w-full text-center py-2.5 rounded-lg text-sm font-semibold transition-colors border"
                                    style={btnStyles.default}
                                    onMouseEnter={(e) => { Object.assign(e.currentTarget.style, btnStyles.hover); }}
                                    onMouseLeave={(e) => { Object.assign(e.currentTarget.style, btnStyles.hoverLeave); }}
                                >
                                    {t("Get Started")}
                                </Link>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </header>
    );
}
