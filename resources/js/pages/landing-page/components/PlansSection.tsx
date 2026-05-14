import React, { useState } from 'react';
import { Check, ArrowRight } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { useScrollAnimation } from '../../../hooks/useScrollAnimation';
import { useTranslation } from 'react-i18next';

// Simple encryption function for plan ID
const encryptPlanId = (planId: number): string => {
  const key = 'Sales Saas 2024';
  const str = planId.toString();
  let encrypted = '';
  for (let i = 0; i < str.length; i++) {
    encrypted += String.fromCharCode(str.charCodeAt(i) ^ key.charCodeAt(i % key.length));
  }
  return btoa(encrypted);
};

interface Plan {
  id: number;
  name: string;
  description: string;
  price: number;
  yearly_price?: number;
  duration: string;
  features?: string[];
  stats?: {
    users: string;
    projects: string;
    contacts: string;
    accounts: string;
    storage: string;
  };
  is_popular?: boolean;
  is_plan_enable: string;
}

interface PlansSectionProps {
  brandColor?: string;
  plans: Plan[];
  settings?: any;
  sectionData?: {
    title?: string;
    subtitle?: string;
    faq_text?: string;
  };
}

function PlansSection({ plans, settings, sectionData, brandColor = '#3b82f6' }: PlansSectionProps) {
  const [billingCycle, setBillingCycle] = useState<'monthly' | 'yearly'>('monthly');
  const { ref, isVisible } = useScrollAnimation();
  const { t } = useTranslation();

  // Filter enabled plans
  const enabledPlans = plans.filter(plan => plan.is_plan_enable === 'on');

  // Default plans if none provided
  const defaultPlans = [
    {
      id: 1,
      name: t('Starter'),
      description: t('Perfect for small teams getting started with CRM'),
      price: 0,
      yearly_price: 0,
      duration: 'month',
      features: [
        t('CRM & Lead Management'),
        t('Basic Pipeline Tracking'),
        t('Contact Management'),
        t('Basic Reports'),
        t('Email Support')
      ],
      stats: {
        users: '3',
        projects: '5',
        contacts: '100',
        accounts: '50',
        storage: '1GB'
      },
      is_popular: false,
      is_plan_enable: 'on'
    },
    {
      id: 2,
      name: t('Professional'),
      description: t('Ideal for growing businesses and sales teams'),
      price: 29,
      yearly_price: 290,
      duration: 'month',
      features: [
        t('Advanced CRM & Lead Management'),
        t('Opportunity & Pipeline Management'),
        t('Quotes & Orders'),
        t('Invoices & Payments'),
        t('Projects & Tasks'),
        t('Advanced Reports & Analytics'),
        t('Priority Support')
      ],
      stats: {
        users: '10',
        projects: '25',
        contacts: '1000',
        accounts: '500',
        storage: '10GB'
      },
      is_popular: true,
      is_plan_enable: 'on'
    },
    {
      id: 3,
      name: t('Enterprise'),
      description: t('For large organizations with advanced needs'),
      price: 99,
      yearly_price: 990,
      duration: 'month',
      features: [
        t('Everything in Professional'),
        t('Unlimited Users & Projects'),
        t('Custom Branding'),
        t('API Access'),
        t('Advanced Integrations'),
        t('Dedicated Support'),
        t('Custom Features'),
        t('White Label Solution')
      ],
      stats: {
        users: 'Unlimited',
        projects: 'Unlimited',
        contacts: 'Unlimited',
        accounts: 'Unlimited',
        storage: '100GB'
      },
      is_popular: false,
      is_plan_enable: 'on'
    }
  ];

  const displayPlans = enabledPlans.length > 0 ? enabledPlans : defaultPlans;

  const formatCurrency = (amount: string | number) => {
    if (typeof window !== 'undefined' && window.appSettings?.formatCurrency) {
      // Use numeric value if available, otherwise parse the string
      const numericAmount = typeof amount === 'number' ? amount : parseFloat(amount);
      return window.appSettings.formatCurrency(numericAmount, { showSymbol: true });
    }
    // Fallback if appSettings is not available
    return amount;
  };

  const getPrice = (plan: Plan) => {
    if (billingCycle === 'yearly' && plan.yearly_price) {
      return plan.yearly_price;
    }
    return plan.price;
  };
  const isDark = document.documentElement.classList.contains('dark');


  return (
    <section id="pricing" className="py-12 sm:py-16 lg:py-20 bg-white dark:bg-gray-900" ref={ref}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className={`text-center mb-8 sm:mb-12 lg:mb-16 transition-all duration-700 ${isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'}`}>
          <h2 className="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
            {sectionData?.title || t('Choose Your Plan')}
          </h2>
          <p className="text-lg text-gray-600 dark:text-gray-400 max-w-3xl mx-auto mb-8 leading-relaxed font-medium">
            {sectionData?.subtitle || t('Start with our free plan and upgrade as your business grows.')}
          </p>

          {/* Billing Toggle */}
          <div className="flex items-center justify-center gap-4">
            <span className={`text-sm ${billingCycle === 'monthly' ? 'text-gray-900 dark:text-white font-semibold' : 'text-gray-500 dark:text-gray-400'}`}>
              {t('Monthly')}
            </span>
            <button
              onClick={() => setBillingCycle(billingCycle === 'monthly' ? 'yearly' : 'monthly')}
              className="relative inline-flex h-6 w-11 items-center rounded-full transition-colors cursor-pointer"
              style={{ backgroundColor: billingCycle === 'yearly' ? brandColor : '#e5e7eb' }}
            >
              <span
                className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                  billingCycle === 'yearly'
                    ? 'translate-x-6 rtl:-translate-x-6'
                    : 'translate-x-1 rtl:-translate-x-1'
                }`}
              />
            </button>
            <span className={`text-sm ${billingCycle === 'yearly' ? 'text-gray-900 dark:text-white font-semibold' : 'text-gray-500 dark:text-gray-400'}`}>
              {t('Yearly')}
            </span>
            {/* {billingCycle === 'yearly' && (
              <span className="bg-gray-100 text-gray-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border">
                Save 20%
              </span>
            )} */}
          </div>
        </div>

        <div className={`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 transition-all duration-700 delay-300 ${isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'}`}>
          {displayPlans.map((plan) => (
            <div
              key={plan.id}
              className={`group relative h-full flex flex-col ${
                plan.is_popular
                  ? 'z-10 scale-[1.02]'
                  : ''
              }`}
            >
              {/* Card with decorative elements */}
              <div
                className="absolute inset-0 rounded-2xl border shadow-lg transition-all duration-300 group-hover:shadow-xl overflow-hidden"
                style={{
                  background: isDark
                    ? 'linear-gradient(to bottom right, rgb(55 65 81), rgb(31 41 55))'
                    : plan.is_popular
                    ? `linear-gradient(to bottom right, ${brandColor}20, ${brandColor}10, transparent)`
                    : 'linear-gradient(to bottom right, rgb(243 244 246 / 0.8), rgb(249 250 251 / 0.5), transparent)',
                  borderColor: isDark ? 'rgb(75 85 99)' : (plan.is_popular ? `${brandColor}30` : 'rgb(229 231 235 / 0.8)')
                }}
              >
                {/* Decorative background elements */}
                <div
                  className="absolute top-0 right-0 w-32 h-32 rounded-full -mr-16 -mt-16 opacity-70"
                  style={{ background: `linear-gradient(to bottom right, ${brandColor}10, transparent)` }}
                ></div>
                <div
                  className="absolute bottom-0 left-0 w-24 h-24 rounded-full -ml-12 -mb-12 opacity-50"
                  style={{ background: `linear-gradient(to top right, ${brandColor}10, transparent)` }}
                ></div>
              </div>

              {/* Recommended indicator */}
              {plan.is_popular && (
                <div className="absolute -top-4 left-0 right-0 flex justify-center z-20">
                  <div
                    className="text-white px-4 py-1.5 rounded-full shadow-lg flex items-center gap-1.5 text-sm font-medium"
                    style={{ backgroundColor: brandColor }}
                  >
                    <Check className="h-4 w-4" />
                    {t('Recommended')}
                  </div>
                </div>
              )}

              {/* Content container */}
              <div className="relative z-10 flex flex-col h-full p-6 pt-8">
                {/* Plan header */}
                <div className="mb-6">
                  <h3
                    className="text-2xl font-bold mb-2 dark:text-white"
                    style={{ color: plan.is_popular ? brandColor : 'inherit' }}
                  >
                    {plan.name}
                  </h3>
                  <div className="flex items-baseline gap-1.5 mb-3">
                    <span
                      className="text-3xl font-extrabold dark:text-white"
                      style={{ color: plan.is_popular ? brandColor : 'inherit' }}
                    >
                      {getPrice(plan) === 0 ? '$0' : formatCurrency(getPrice(plan))}
                    </span>
                    <span className="text-muted-foreground dark:text-gray-400 text-sm">
                      /{billingCycle === 'yearly' ? t('year') : t('month')}
                    </span>
                  </div>
                  <p className="text-sm text-muted-foreground dark:text-gray-400 leading-relaxed line-clamp-2 mb-3">
                    {plan.description}
                  </p>
                </div>

                {/* Divider with icon */}
                <div className="relative flex items-center my-4">
                  <div className="flex-grow border-t border-gray-200 dark:border-gray-700"></div>
                  <div
                    className="mx-3 p-1.5 rounded-full"
                    style={{ backgroundColor: `${brandColor}10`, color: brandColor }}
                  >
                    <Check className="h-4 w-4" />
                  </div>
                  <div className="flex-grow border-t border-gray-200 dark:border-gray-700"></div>
                </div>

                {/* Usage limits */}
                <div className="mb-4">
                  <h4 className="text-sm font-semibold uppercase tracking-wider text-muted-foreground dark:text-gray-400 mb-3">
                    {t('Usage Limits')}
                  </h4>
                  <div className="grid grid-cols-2 gap-2">
                    <div className="bg-white/50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                      <div className="text-lg font-bold" style={{ color: brandColor }}>
                        {plan.stats?.users || 'Unlimited'}
                      </div>
                      <div className="text-xs text-muted-foreground dark:text-gray-400">{t('Users')}</div>
                    </div>
                    <div className="bg-white/50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                      <div className="text-lg font-bold" style={{ color: brandColor }}>
                        {plan.stats?.projects || 'Unlimited'}
                      </div>
                      <div className="text-xs text-muted-foreground dark:text-gray-400">{t('Projects')}</div>
                    </div>
                    <div className="bg-white/50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                      <div className="text-lg font-bold" style={{ color: brandColor }}>
                        {plan.stats?.contacts || 'Unlimited'}
                      </div>
                      <div className="text-xs text-muted-foreground dark:text-gray-400">{t('Contacts')}</div>
                    </div>
                    <div className="bg-white/50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                      <div className="text-lg font-bold" style={{ color: brandColor }}>
                        {plan.stats?.accounts || 'Unlimited'}
                      </div>
                      <div className="text-xs text-muted-foreground dark:text-gray-400">{t('Accounts')}</div>
                    </div>
                    <div className="bg-white/50 dark:bg-gray-700/50 rounded-lg p-2 text-center col-span-2">
                      <div className="text-lg font-bold" style={{ color: brandColor }}>
                        {plan.stats?.storage || '10GB'}
                      </div>
                      <div className="text-xs text-muted-foreground dark:text-gray-400">{t('Storage')}</div>
                    </div>
                  </div>
                </div>

                {/* Features */}
                <div className="mb-6 flex-1">
                  <h4 className="text-sm font-semibold uppercase tracking-wider text-muted-foreground dark:text-gray-400 mb-3">
                    {t('Features')}
                  </h4>
                  <ul className="space-y-2.5">
                    {(plan.features || []).map((feature, index) => (
                      <li key={index} className="flex items-center gap-3">
                        <div
                          className="flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center"
                          style={{ backgroundColor: `${brandColor}10`, color: brandColor }}
                        >
                          <Check className="h-3.5 w-3.5" />
                        </div>
                        <span className="text-sm font-medium dark:text-gray-300">
                          {feature}
                        </span>
                      </li>
                    ))}
                  </ul>
                </div>

                {/* Actions */}
                <div className="mt-auto pt-4 border-t border-gray-200 dark:border-gray-700">
                  <Link
                    href={route('register', { plan: encryptPlanId(plan.id) })}
                    className="block w-full text-center py-3 px-6 rounded-lg font-semibold transition-colors hover:opacity-90"
                    style={{
                      backgroundColor: plan.is_popular ? brandColor : (isDark ? 'rgb(55, 65, 81)' : 'rgb(243, 244, 246)'),
                      color: plan.is_popular ? 'white' : (isDark ? 'white' : 'rgb(17, 24, 39)')
                    }}
                  >
                    {plan.price === 0 ? t('Start Free') : t('Get Started')}
                    <ArrowRight className="w-4 h-4 inline-block ml-2" />
                  </Link>
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* FAQ Link */}
        {sectionData?.faq_text && (
          <div className="text-center mt-8 sm:mt-12">
            <p className="text-gray-600 dark:text-gray-400">
              {sectionData.faq_text}
            </p>
          </div>
        )}
      </div>
    </section>
  );
}

export default PlansSection;
