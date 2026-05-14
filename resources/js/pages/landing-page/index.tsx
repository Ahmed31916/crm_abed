import React, { useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import Header from './components/Header';
import HeroSection from './components/HeroSection';
import FeaturesSection from './components/FeaturesSection';
import ScreenshotsSection from './components/ScreenshotsSection';
import WhyChooseUs from './components/WhyChooseUs';
// import TemplatesSection from './components/TemplatesSection';
import AboutUs from './components/AboutUs';
import TeamSection from './components/TeamSection';
import TestimonialsSection from './components/TestimonialsSection';
import PlansSection from './components/PlansSection';
import FaqSection from './components/FaqSection';
import NewsletterSection from './components/NewsletterSection';
import ContactSection from './components/ContactSection';
import Footer from './components/Footer';
import { useBrand } from '@/contexts/BrandContext';
import { THEME_COLORS, useAppearance } from '@/hooks/use-appearance';
import { isDemoMode, getCookie } from '@/utils/cookie-utils';
import { getDisplayUrl } from '@/utils/helper';

interface Plan {
    id: number;
    name: string;
    description: string;
    price: number;
    yearly_price?: number;
    duration: string;
    features?: string[];
    is_popular?: boolean;
    is_plan_enable: string;
}

interface Testimonial {
    id: number;
    name: string;
    role: string;
    company?: string;
    content: string;
    avatar?: string;
    rating: number;
}

interface Faq {
    id: number;
    question: string;
    answer: string;
}

interface LandingSettings {
    company_name: string;
    contact_email: string;
    contact_phone: string;
    contact_address: string;
    config_sections?: {
        sections: Array<{
            key: string;
            [key: string]: any;
        }>;
        theme?: {
            primary_color?: string;
            secondary_color?: string;
            accent_color?: string;
            logo_light?: string;
            logo_dark?: string;
            favicon?: string;
        };
        seo?: {
            meta_title?: string;
            meta_description?: string;
            meta_keywords?: string;
        };
        custom_css?: string;
        custom_js?: string;
        section_order?: string[];
        section_visibility?: {
            [key: string]: boolean;
        };
    };
}

interface CustomPage {
    id: number;
    title: string;
    slug: string;
}

interface PageProps {
    plans: Plan[];
    testimonials: Testimonial[];
    faqs: Faq[];
    customPages: CustomPage[];
    settings: LandingSettings;
    flash?: {
        success?: string;
        error?: string;
    };
}

export default function LandingPage() {
    const { plans, testimonials, faqs, customPages = [], settings, flash } = usePage<PageProps>().props;
    const globalSettings = (usePage().props as any).globalSettings;
    const userLanguage = (usePage().props as any).userLanguage;

    // Get brand colors - prioritize config_sections theme over brand context
    const { themeColor, customColor } = useBrand();
    const configPrimaryColor = settings.config_sections?.theme?.primary_color;
    const primaryColor = configPrimaryColor || (themeColor === 'custom' ? customColor : THEME_COLORS[themeColor as keyof typeof THEME_COLORS]);
    useEffect(() => {
        const favicon = settings.config_sections?.theme?.favicon;
        if (!favicon) return;

        // Convert relative path to full URL if needed
        const faviconUrl = getDisplayUrl(favicon);

        // Update favicon in document head
        let link = document.querySelector("link[rel*='icon']") as HTMLLinkElement;

        if (!link) {
            link = document.createElement('link');
            link.rel = 'icon';
            document.head.appendChild(link);
        }

        link.href = faviconUrl;
    }, []);

    // RTL Support for landing page - Apply immediately and persist
    const applyRTLDirection = React.useCallback(() => {
        const isDemo = globalSettings?.is_demo || false;
        const currentLang = userLanguage || globalSettings?.defaultLanguage || 'en';
        const isRTLLanguage = ['ar', 'he'].includes(currentLang);
        let dir = 'ltr';

        const getCookie = (name: string): string | null => {
            if (typeof document === 'undefined') return null;
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) {
                const cookieValue = parts.pop()?.split(';').shift();
                return cookieValue ? decodeURIComponent(cookieValue) : null;
            }
            return null;
        };

        // Check RTL setting from cookies/globalSettings
        const layoutDirection = isDemo ? getCookie('layoutDirection') : globalSettings?.layoutDirection;
        const isRTLSetting = layoutDirection === 'right';

        // Apply RTL if: 1) Language is ar/he OR 2) RTL setting is enabled
        if (isRTLLanguage || isRTLSetting) {
            dir = 'rtl';
        }

        // Apply direction immediately
        document.documentElement.dir = dir;
        document.documentElement.setAttribute('dir', dir);
        document.body.dir = dir;

        return dir;
    }, [userLanguage, globalSettings?.defaultLanguage, globalSettings?.is_demo, globalSettings?.layoutDirection]);

    // Apply RTL on mount and when dependencies change
    React.useLayoutEffect(() => {
        const direction = applyRTLDirection();

        // Ensure direction persists after any DOM changes
        const observer = new MutationObserver(() => {
            if (document.documentElement.dir !== direction) {
                document.documentElement.dir = direction;
                document.documentElement.setAttribute('dir', direction);
            }
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['dir']
        });

        return () => observer.disconnect();
    }, [applyRTLDirection]);

    // Apply theme mode (dark/light) to landing page
    React.useEffect(() => {
        let themeMode = 'light'; // default

        if (isDemoMode()) {
            // In demo mode, get theme from cookies
            try {
                const themeSettings = getCookie('themeSettings');
                if (themeSettings) {
                    const parsed = JSON.parse(themeSettings);
                    themeMode = parsed.appearance || 'light';
                }
            } catch (error) {
                // Use default
            }
        } else {
            // In live mode, get theme from database
            themeMode = globalSettings?.themeMode || 'light';
        }

        // Apply theme mode
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const isDark = themeMode === 'dark' || (themeMode === 'system' && prefersDark);

        document.documentElement.classList.toggle('dark', isDark);
        document.body.classList.toggle('dark', isDark);
    }, [globalSettings?.themeMode]);
    // SEO Meta tags
    React.useEffect(() => {
        const seo = settings.config_sections?.seo;
        if (seo?.meta_title) {
            document.title = seo.meta_title;
        }
        if (seo?.meta_description) {
            let metaDesc = document.querySelector('meta[name="description"]');
            if (!metaDesc) {
                metaDesc = document.createElement('meta');
                metaDesc.setAttribute('name', 'description');
                document.head.appendChild(metaDesc);
            }
            metaDesc.setAttribute('content', seo.meta_description);
        }
    }, [settings.config_sections?.seo]);

    // Custom CSS
    React.useEffect(() => {
        const customCss = settings.config_sections?.custom_css;
        if (customCss) {
            const styleId = 'landing-custom-css';
            let styleElement = document.getElementById(styleId);
            if (!styleElement) {
                styleElement = document.createElement('style');
                styleElement.id = styleId;
                document.head.appendChild(styleElement);
            }
            styleElement.textContent = customCss;
        }
    }, [settings.config_sections?.custom_css]);

    // Custom JS
    React.useEffect(() => {
        const customJs = settings.config_sections?.custom_js;
        if (customJs) {
            const scriptId = 'landing-custom-js';
            let scriptElement = document.getElementById(scriptId);
            if (scriptElement) {
                scriptElement.remove();
            }
            scriptElement = document.createElement('script');
            scriptElement.id = scriptId;
            scriptElement.textContent = customJs;
            document.body.appendChild(scriptElement);
        }
    }, [settings.config_sections?.custom_js]);

    // Get section data helper
    const getSectionData = (key: string) => {
        return settings.config_sections?.sections?.find(section => section.key === key) || {};
    };

    // Get section visibility
    const isSectionVisible = (key: string) => {
        return settings.config_sections?.section_visibility?.[key] !== false;
    };

    // Get section order or use default
    const sectionOrder = settings.config_sections?.section_order || [
        'header', 'hero', 'features', 'screenshots', 'why_choose_us', 'templates', 'about',
        'team', 'testimonials', 'plans', 'faq', 'newsletter', 'contact', 'footer'
    ];

    // Component mapping
    const sectionComponents = {
        header: () => isSectionVisible('header') && (
            <Header
                settings={settings}
                sectionData={getSectionData('header')}
                customPages={customPages}
                brandColor={primaryColor}
            />
        ),
        hero: () => isSectionVisible('hero') && (
            <HeroSection
                settings={settings}
                sectionData={getSectionData('hero')}
                brandColor={primaryColor}
            />
        ),
        features: () => isSectionVisible('features') && (
            <FeaturesSection
                settings={settings}
                sectionData={getSectionData('features')}
                brandColor={primaryColor}
            />
        ),
        screenshots: () => isSectionVisible('screenshots') && (
            <ScreenshotsSection
                settings={settings}
                sectionData={getSectionData('screenshots')}
                brandColor={primaryColor}
            />
        ),
        why_choose_us: () => isSectionVisible('why_choose_us') && (
            <WhyChooseUs
                settings={settings}
                sectionData={getSectionData('why_choose_us')}
                brandColor={primaryColor}
            />
        ),
        // templates: () => isSectionVisible('templates') && (
        //   <TemplatesSection
        //     settings={settings}
        //     sectionData={getSectionData('templates')}
        //     brandColor={primaryColor}
        //   />
        // ),
        about: () => isSectionVisible('about') && (
            <AboutUs
                settings={settings}
                sectionData={getSectionData('about')}
                brandColor={primaryColor}
            />
        ),
        team: () => isSectionVisible('team') && (
            <TeamSection
                settings={settings}
                sectionData={getSectionData('team')}
                brandColor={primaryColor}
            />
        ),
        testimonials: () => isSectionVisible('testimonials') && (
            <TestimonialsSection
                testimonials={testimonials}
                settings={settings}
                sectionData={getSectionData('testimonials')}
                brandColor={primaryColor}
            />
        ),
        plans: () => isSectionVisible('plans') && (
            <PlansSection
                plans={plans}
                settings={settings}
                sectionData={getSectionData('plans')}
                brandColor={primaryColor}
            />
        ),
        faq: () => isSectionVisible('faq') && (
            <FaqSection
                faqs={faqs}
                settings={settings}
                sectionData={getSectionData('faq')}
                brandColor={primaryColor}
            />
        ),
        newsletter: () => isSectionVisible('newsletter') && (
            <NewsletterSection
                flash={flash}
                settings={settings}
                sectionData={getSectionData('newsletter')}
                brandColor={primaryColor}
            />
        ),
        contact: () => isSectionVisible('contact') && (
            <ContactSection
                flash={flash}
                settings={settings}
                sectionData={getSectionData('contact')}
                brandColor={primaryColor}
            />
        ),
        footer: () => isSectionVisible('footer') && (
            <Footer
                settings={settings}
                sectionData={getSectionData('footer')}
                brandColor={primaryColor}
            />
        )
    };
    const seo = settings.config_sections?.seo;
    // const globalSettings = (usePage().props as any).globalSettings;
    const pageTitle = seo?.meta_title || globalSettings?.title_text || 'Sales SaaS';
    return (
        <>
            <Head title={pageTitle}>
                {seo?.meta_title && (
                    <meta name="title" content={seo.meta_title} />
                )}
                {seo?.meta_description && (
                    <meta name="description" content={seo.meta_description} />
                )}
            </Head>
            <div
                className="min-h-screen bg-white"
                style={{
                    scrollBehavior: 'smooth',
                    '--brand-color': primaryColor,
                    '--primary-color': settings.config_sections?.theme?.primary_color || primaryColor,
                    '--secondary-color': settings.config_sections?.theme?.secondary_color || '#8b5cf6',
                    '--accent-color': settings.config_sections?.theme?.accent_color || '#10b77f'
                } as React.CSSProperties}
            >
                {sectionOrder.map((sectionKey) => {
                    const Component = sectionComponents[sectionKey as keyof typeof sectionComponents];
                    return Component ? <React.Fragment key={sectionKey}>{Component()}</React.Fragment> : null;
                })}
            </div>
        </>
    );
}
