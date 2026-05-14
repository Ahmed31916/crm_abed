import { PageTemplate } from '@/components/page-template';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { useEffect, useRef, useState } from 'react';
import { Settings as SettingsIcon, Building, DollarSign, Users, RefreshCw, Palette, BookOpen, Award, FileText, Mail, Bell, Link2, CreditCard, Calendar, HardDrive, Shield, Bot, Cookie, Search, Webhook, Wallet, MessageSquare, ShoppingBag, Slack } from 'lucide-react';
import { ScrollArea } from '@/components/ui/scroll-area';
import SystemSettings from './components/system-settings';
import CompanySystemSettings from './components/company-system-settings';
import { usePage } from '@inertiajs/react';

import CurrencySettings from './components/currency-settings';
import EmailNotificationSettings from './components/email-notification-settings';
import TwilioNotificationSettings from './components/twilio-notification-settings';
import SlackNotificationSettings from './components/slack-notification-settings';

import BrandSettings from './components/brand-settings';
import EmailSettings from './components/email-settings';
import PaymentSettings from './components/payment-settings';

import RecaptchaSettings from './components/recaptcha-settings';
import ChatGptSettings from './components/chatgpt-settings';
import CookieSettings from './components/cookie-settings';
import SeoSettings from './components/seo-settings';
import CacheSettings from './components/cache-settings';
import WebhookSettings from './components/webhook-settings';
import GoogleCalendarSettings from './components/google-calendar-settings';
import InvoiceTemplateSettings from './components/invoice-template-settings';
import QuoteTemplateSettings from './components/quote-template-settings';
import SalesOrderTemplateSettings from './components/sales-order-template-settings';

import StorageSettings from './components/storage-settings';
import { Toaster } from '@/components/ui/toaster';
import { useTranslation } from 'react-i18next';
import { hasPermission, hasRole } from '@/utils/authorization';
import { useLayout } from '@/contexts/LayoutContext';

export default function Settings() {
    const { t } = useTranslation();
    const { position } = useLayout();
    const { systemSettings = {}, cacheSize = '0.00', timezones = {}, dateFormats = {}, timeFormats = {}, paymentSettings = {}, webhooks = [], auth = {}} = usePage().props as any;
    const [activeSection, setActiveSection] = useState('system-settings');

    // Define all possible sidebar navigation items
    const allSidebarNavItems: (NavItem & { permission?: string; role?: string })[] = [
        {
            title: t('System Settings'),
            href: '#system-settings',
            icon: <SettingsIcon className="h-4 w-4 mr-2" />,
            permission: 'manage-system-settings'
        },
        {
            title: t('Brand Settings'),
            href: '#brand-settings',
            icon: <Palette className="h-4 w-4 mr-2" />,
            permission: 'manage-brand-settings'
        },
        {
            title: t('Currency Settings'),
            href: '#currency-settings',
            icon: <DollarSign className="h-4 w-4 mr-2" />,
            permission: 'manage-currency-settings'
        },
        {
            title: t('Email Settings'),
            href: '#email-settings',
            icon: <Mail className="h-4 w-4 mr-2" />,
            permission: 'manage-email-settings'
        },
        {
            title: t('Email Notification Settings'),
            href: '#email-notification-settings',
            icon: <Bell className="h-4 w-4 mr-2" />,
            permission: 'manage-email-notifications'
        },
        {
            title: t('Twilio Settings'),
            href: '#twilio-notification-settings',
            icon: <MessageSquare className="h-4 w-4 mr-2" />,
            permission: 'manage-twilio-notifications'
        },
        {
            title: t('Slack Settings'),
            href: '#slack-notification-settings',
            icon: <Slack className="h-4 w-4 mr-2" />,
            permission: 'manage-twilio-notifications'
        },
        {
            title: t('Payment Settings'),
            href: '#payment-settings',
            icon: <CreditCard className="h-4 w-4 mr-2" />,
            permission: 'manage-payment-settings'
        },
        {
            title: t('Payment Settings'),
            href: '#company-payment-settings',
            icon: <CreditCard className="h-4 w-4 mr-2" />,
            permission: 'settings'
        },
        {
            title: t('Quote Templates'),
            href: '#quote-templates',
            icon: <FileText className="h-4 w-4 mr-2" />,
            role: 'company',
            permission: 'manage-quotes-settings'
        },
        {
            title: t('Sales Order Templates'),
            href: '#sales-order-templates',
            icon: <ShoppingBag className="h-4 w-4 mr-2" />,
            role: 'company',
            permission: 'manage-sales-orders-settings'
        },
        {
            title: t('Invoice Templates'),
            href: '#invoice-templates',
            icon: <FileText className="h-4 w-4 mr-2" />,
            role: 'company',
            permission: 'manage-invoices-settings'
        },

        {
            title: t('ReCaptcha Settings'),
            href: '#recaptcha-settings',
            icon: <Shield className="h-4 w-4 mr-2" />,
            permission: 'manage-recaptcha-settings'
        },
        {
            title: t('Chat GPT Settings'),
            href: '#chatgpt-settings',
            icon: <Bot className="h-4 w-4 mr-2" />,
            permission: 'manage-chatgpt-settings'
        },
        {
            title: t('Cookie Settings'),
            href: '#cookie-settings',
            icon: <Cookie className="h-4 w-4 mr-2" />,
            permission: 'manage-cookie-settings'
        },
        {
            title: t('SEO Settings'),
            href: '#seo-settings',
            icon: <Search className="h-4 w-4 mr-2" />,
            permission: 'manage-seo-settings'
        },
        {
            title: t('Storage Settings'),
            href: '#storage-settings',
            icon: <HardDrive className="h-4 w-4 mr-2" />,
            permission: 'manage-storage-settings'
        },
        {
            title: t('Cache Settings'),
            href: '#cache-settings',
            icon: <HardDrive className="h-4 w-4 mr-2" />,
            permission: 'manage-cache-settings'
        },
        {
            title: t('Google Calendar Settings'),
            href: '#google-calendar-settings',
            icon: <Calendar className="h-4 w-4 mr-2" />,
            permission: 'settings'
        },

    ];

    if (!hasRole(auth.roles, 'superadmin')) {
        allSidebarNavItems.push({
            title: t('Webhook Settings'),
            href: '#webhook-settings',
            icon: <Webhook className="h-4 w-4 mr-2" />,
            permission: 'manage-webhook-settings'
        });
    }
    // Filter sidebar items based on user permissions
    const sidebarNavItems = allSidebarNavItems.filter(item => {
        // Check for both role and permission if both exist
        if (item.role && item.permission) {
            return hasRole(auth.roles, item.role) && hasPermission(auth.permissions, item.permission);
        }
        // Check for role-based access
        if (item.role) {
            return hasRole(auth.roles, item.role);
        }
        // If no permission is required or user has the permission
        if (!item.permission || hasPermission(auth.permissions, item.permission)) {
            return true;
        }
        // For company users, only show specific settings
        if (hasRole(auth.roles, 'company')) {
            // Only allow system settings, email settings, brand settings, currency settings, webhook settings, email notifications, and settings
            return ['manage-system-settings', 'manage-email-settings', 'manage-brand-settings', 'manage-currency-settings', 'manage-webhook-settings', 'manage-email-notifications', 'manage-twilio-notifications','manage-quotes-settings','manage-sales-orders-settings','manage-invoices-settings', 'settings'].includes(item.permission);
        }
        return false;
    });

    // Refs for each section
    const systemSettingsRef = useRef<HTMLDivElement>(null);
    const brandSettingsRef = useRef<HTMLDivElement>(null);

    const currencySettingsRef = useRef<HTMLDivElement>(null);
    const emailSettingsRef = useRef<HTMLDivElement>(null);
    const paymentSettingsRef = useRef<HTMLDivElement>(null);
    const companyPaymentSettingsRef = useRef<HTMLDivElement>(null);
    const quoteTemplatesRef = useRef<HTMLDivElement>(null);
    const salesOrderTemplatesRef = useRef<HTMLDivElement>(null);
    const invoiceTemplatesRef = useRef<HTMLDivElement>(null);
    const emailNotificationSettingsRef = useRef<HTMLDivElement>(null);
    const twilioNotificationSettingsRef = useRef<HTMLDivElement>(null);
    const slackNotificationSettingsRef = useRef<HTMLDivElement>(null);

    const recaptchaSettingsRef = useRef<HTMLDivElement>(null);
    const chatgptSettingsRef = useRef<HTMLDivElement>(null);
    const cookieSettingsRef = useRef<HTMLDivElement>(null);
    const seoSettingsRef = useRef<HTMLDivElement>(null);
    const cacheSettingsRef = useRef<HTMLDivElement>(null);
    const webhookSettingsRef = useRef<HTMLDivElement>(null);
    const googleCalendarSettingsRef = useRef<HTMLDivElement>(null);

    const storageSettingsRef = useRef<HTMLDivElement>(null);


    // Smart scroll functionality
    useEffect(() => {
        const handleScroll = () => {
            const scrollPosition = window.scrollY + 100; // Add offset for better UX

            // Get positions of each section
            const systemSettingsPosition = systemSettingsRef.current?.offsetTop || 0;
            const brandSettingsPosition = brandSettingsRef.current?.offsetTop || 0;

            const currencySettingsPosition = currencySettingsRef.current?.offsetTop || 0;
            const emailSettingsPosition = emailSettingsRef.current?.offsetTop || 0;
            const paymentSettingsPosition = paymentSettingsRef.current?.offsetTop || 0;
            const companyPaymentSettingsPosition = companyPaymentSettingsRef.current?.offsetTop || 0;
            const quoteTemplatesPosition = quoteTemplatesRef.current?.offsetTop || 0;
            const salesOrderTemplatesPosition = salesOrderTemplatesRef.current?.offsetTop || 0;
            const invoiceTemplatesPosition = invoiceTemplatesRef.current?.offsetTop || 0;
            const emailNotificationSettingsPosition = emailNotificationSettingsRef.current?.offsetTop || 0;
            const twilioNotificationSettingsPosition = twilioNotificationSettingsRef.current?.offsetTop || 0;
            const slackNotificationSettingsPosition = slackNotificationSettingsRef.current?.offsetTop || 0;

            const recaptchaSettingsPosition = recaptchaSettingsRef.current?.offsetTop || 0;
            const chatgptSettingsPosition = chatgptSettingsRef.current?.offsetTop || 0;
            const cookieSettingsPosition = cookieSettingsRef.current?.offsetTop || 0;
            const seoSettingsPosition = seoSettingsRef.current?.offsetTop || 0;
            const cacheSettingsPosition = cacheSettingsRef.current?.offsetTop || 0;
            const webhookSettingsPosition = webhookSettingsRef.current?.offsetTop || 0;
            const googleCalendarSettingsPosition = googleCalendarSettingsRef.current?.offsetTop || 0;

            const storageSettingsPosition = storageSettingsRef.current?.offsetTop || 0;

            // Determine active section based on scroll position
            if (scrollPosition >= storageSettingsPosition) {
                setActiveSection('storage-settings');

            } else if (scrollPosition >= googleCalendarSettingsPosition) {
                setActiveSection('google-calendar-settings');
            } else if (scrollPosition >= webhookSettingsPosition) {
                setActiveSection('webhook-settings');
            } else if (scrollPosition >= cacheSettingsPosition) {
                setActiveSection('cache-settings');
            } else if (scrollPosition >= seoSettingsPosition) {
                setActiveSection('seo-settings');
            } else if (scrollPosition >= cookieSettingsPosition) {
                setActiveSection('cookie-settings');
            } else if (scrollPosition >= chatgptSettingsPosition) {
                setActiveSection('chatgpt-settings');
            } else if (scrollPosition >= recaptchaSettingsPosition) {
                setActiveSection('recaptcha-settings');
            } else if (scrollPosition >= slackNotificationSettingsPosition) {
                setActiveSection('slack-notification-settings');
            } else if (scrollPosition >= twilioNotificationSettingsPosition) {
                setActiveSection('twilio-notification-settings');
            } else if (scrollPosition >= emailNotificationSettingsPosition) {
                setActiveSection('email-notification-settings');
            } else if (scrollPosition >= salesOrderTemplatesPosition) {
                setActiveSection('sales-order-templates');
            } else if (scrollPosition >= quoteTemplatesPosition) {
                setActiveSection('quote-templates');
            } else if (scrollPosition >= invoiceTemplatesPosition) {
                setActiveSection('invoice-templates');
            } else if (scrollPosition >= companyPaymentSettingsPosition) {
                setActiveSection('company-payment-settings');
            } else if (scrollPosition >= paymentSettingsPosition) {
                setActiveSection('payment-settings');
            } else if (scrollPosition >= emailSettingsPosition) {
                setActiveSection('email-settings');
            } else if (scrollPosition >= currencySettingsPosition) {
                setActiveSection('currency-settings');

            } else if (scrollPosition >= brandSettingsPosition) {
                setActiveSection('brand-settings');
            } else {
                setActiveSection('system-settings');
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

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Settings') }
    ];

    return (
        <PageTemplate
            title={t('Settings')}
            url="/settings"
            breadcrumbs={breadcrumbs}
        >
            <div className={`flex flex-col md:flex-row gap-8`} dir={position === 'right' ? 'rtl' : 'ltr'}>
                {/* Sidebar Navigation */}
                <div className="md:w-64 flex-shrink-0">
                    <div className="sticky top-20">
                        <ScrollArea className="h-[calc(100vh-5rem)]">
                            <div className={`space-y-1 ${position === 'right' ? 'pl-4' : 'pr-4'}`}>
                                {/* <div className="pr-4 space-y-1"> */}
                                {sidebarNavItems.map((item) => (
                                    <Button
                                        key={item.href}
                                        variant="ghost"
                                        className={cn('w-full justify-start', {
                                            'bg-muted font-medium': activeSection === item.href.replace('#', ''),
                                        })}
                                        onClick={() => handleNavClick(item.href)}
                                    >
                                        {item.icon}
                                        {item.title}
                                    </Button>
                                ))}
                            </div>
                        </ScrollArea>
                    </div>
                </div>

                {/* Main Content */}
                <div className="flex-1">
                    {/* System Settings Section */}
                    {(hasPermission(auth.permissions, 'manage-system-settings') || hasRole(auth.roles, 'superadmin')) && (
                        <section id="system-settings" ref={systemSettingsRef} className="mb-8">
                            <SystemSettings
                                settings={systemSettings}
                                timezones={timezones}
                                dateFormats={dateFormats}
                                timeFormats={timeFormats}
                            />
                        </section>
                    )}

                    {/* Company System Settings Section */}
                    {hasRole(auth.roles, 'company') && (
                        <section id="system-settings" ref={systemSettingsRef} className="mb-8">
                            <CompanySystemSettings
                                settings={systemSettings}
                                timezones={timezones}
                                dateFormats={dateFormats}
                                timeFormats={timeFormats}
                            />
                        </section>
                    )}

                    {/* Brand Settings Section */}
                    {(hasPermission(auth.permissions, 'manage-brand-settings') || hasRole(auth.roles, 'superadmin')) && (
                        <section id="brand-settings" ref={brandSettingsRef} className="mb-8">
                            <BrandSettings />
                        </section>
                    )}



                    {/* Currency Settings Section */}
                    {(hasPermission(auth.permissions, 'manage-currency-settings') || hasRole(auth.roles, 'superadmin') || hasRole(auth.roles, 'company')) && (
                        <section id="currency-settings" ref={currencySettingsRef} className="mb-8">
                            <CurrencySettings />
                        </section>
                    )}

                    {/* Email Settings Section */}
                    {(hasPermission(auth.permissions, 'manage-email-settings') || hasRole(auth.roles, 'superadmin')) && (
                        <section id="email-settings" ref={emailSettingsRef} className="mb-8">
                            <EmailSettings />
                        </section>
                    )}
                    {/* Email Notification Settings Section */}
                    {(hasPermission(auth.permissions, 'manage-email-notifications') || hasRole(auth.roles, 'company')) && (
                        <section id="email-notification-settings" ref={emailNotificationSettingsRef} className="mb-8">
                            <EmailNotificationSettings />
                        </section>
                    )}
                    {/* Twilio Notification Settings Section */}
                    {(hasPermission(auth.permissions, 'manage-twilio-notifications') || hasRole(auth.roles, 'company')) && (
                        <section id="twilio-notification-settings" ref={twilioNotificationSettingsRef} className="mb-8">
                            <TwilioNotificationSettings />
                        </section>
                    )}
                    {/* Slack Notification Settings Section */}
                    {(hasPermission(auth.permissions, 'manage-slack-notifications') || hasRole(auth.roles, 'company')) && (
                        <section id="slack-notification-settings" ref={slackNotificationSettingsRef} className="mb-8">
                            <SlackNotificationSettings />
                        </section>
                    )}

                    {/* Payment Settings Section */}
                    {(hasPermission(auth.permissions, 'manage-payment-settings') || hasRole(auth.roles, 'superadmin')) && (
                        <section id="payment-settings" ref={paymentSettingsRef} className="mb-8">
                            <PaymentSettings settings={paymentSettings} />
                        </section>
                    )}

                    {/* Company Payment Settings Section */}
                    {(hasPermission(auth.permissions, 'settings') || hasRole(auth.roles, 'company')) && (
                        <section id="company-payment-settings" ref={companyPaymentSettingsRef} className="mb-8">
                            <PaymentSettings settings={paymentSettings} />
                        </section>
                    )}

                    {/* Quote Templates Section */}
                    {(hasRole(auth.roles, 'company') && hasPermission(auth.permissions, 'manage-quotes-settings')) && (
                        <section id="quote-templates" ref={quoteTemplatesRef} className="mb-8">
                            <QuoteTemplateSettings />
                        </section>
                    )}

                    {/* Sales Order Templates Section */}
                    {(hasRole(auth.roles, 'company') && hasPermission(auth.permissions, 'manage-sales-orders-settings')) && (
                        <section id="sales-order-templates" ref={salesOrderTemplatesRef} className="mb-8">
                            <SalesOrderTemplateSettings />
                        </section>
                    )}

                    {/* Invoice Templates Section */}
                    {(hasRole(auth.roles, 'company') && hasPermission(auth.permissions, 'manage-invoices-settings')) && (
                        <section id="invoice-templates" ref={invoiceTemplatesRef} className="mb-8">
                            <InvoiceTemplateSettings />
                        </section>
                    )}

                    {/* ReCaptcha Settings Section */}
                    {(hasPermission(auth.permissions, 'manage-recaptcha-settings') || hasRole(auth.roles, 'superadmin')) && (
                        <section id="recaptcha-settings" ref={recaptchaSettingsRef} className="mb-8">
                            <RecaptchaSettings settings={systemSettings} />
                        </section>
                    )}

                    {/* Chat GPT Settings Section */}
                    {(hasPermission(auth.permissions, 'manage-chatgpt-settings') || hasRole(auth.roles, 'superadmin')) && (
                        <section id="chatgpt-settings" ref={chatgptSettingsRef} className="mb-8">
                            <ChatGptSettings settings={systemSettings} />
                        </section>
                    )}

                    {/* Cookie Settings Section */}
                    {(hasPermission(auth.permissions, 'manage-cookie-settings') || hasRole(auth.roles, 'superadmin')) && (
                        <section id="cookie-settings" ref={cookieSettingsRef} className="mb-8">
                            <CookieSettings settings={systemSettings}/>
                        </section>
                    )}

                    {/* SEO Settings Section */}
                    {(hasPermission(auth.permissions, 'manage-seo-settings') || hasRole(auth.roles, 'superadmin')) && (
                        <section id="seo-settings" ref={seoSettingsRef} className="mb-8">
                            <SeoSettings settings={systemSettings} />
                        </section>
                    )}

                    {/* Storage Settings Section */}
                    {(hasPermission(auth.permissions, 'manage-storage-settings') || hasRole(auth.roles, 'superadmin')) && (
                        <section id="storage-settings" ref={storageSettingsRef} className="mb-8">
                            <StorageSettings settings={systemSettings} />
                        </section>
                    )}

                    {/* Cache Settings Section */}
                    {(hasPermission(auth.permissions, 'manage-cache-settings') || hasRole(auth.roles, 'superadmin')) && (
                        <section id="cache-settings" ref={cacheSettingsRef} className="mb-8">
                            <CacheSettings cacheSize={cacheSize} />
                        </section>
                    )}

                    {/* Google Calendar Settings Section */}
                    {(hasPermission(auth.permissions, 'settings') || hasRole(auth.roles, 'company')) && (
                        <section id="google-calendar-settings" ref={googleCalendarSettingsRef} className="mb-8">
                            <GoogleCalendarSettings settings={systemSettings} />
                        </section>
                    )}




                    {/* Webhook Settings Section */}
                    {(hasPermission(auth.permissions, 'manage-webhook-settings') || hasRole(auth.roles, 'company')) && (
                        <section id="webhook-settings" ref={webhookSettingsRef} className="mb-8">
                            <WebhookSettings webhooks={webhooks} />
                        </section>
                    )}

                </div>
            </div>
            <Toaster />
        </PageTemplate>
    );
}
