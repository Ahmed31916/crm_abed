import React, { useState } from 'react';
import { useForm, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Save, Eye, Settings as SettingsIcon, Type, Info, Phone, Globe, Palette, Image, Users, Code, Search, Layout, Monitor, FileText, Award, Mail, CreditCard, HelpCircle, Trash2, Plus, X, GripVertical, ArrowUpDown } from 'lucide-react';
import MediaPicker from '@/components/MediaPicker';
import { Link } from '@inertiajs/react';
import { PageTemplate } from '@/components/page-template';
import { SettingsSection } from '@/components/settings-section';
import { toast } from '@/components/custom-toast';
import { Toaster } from '@/components/ui/toaster';
import { Separator } from '@/components/ui/separator';
import FeaturesSection from './settings-features';
import AboutSection from './settings-about';
import ContactSection from './settings-contact';
import TemplatesSection from './settings-templates';

import { defaultLandingPageSections } from './templates/default-sections';
import { useBrand } from '@/contexts/BrandContext';
import { THEME_COLORS } from '@/hooks/use-appearance';
import { getDisplayUrl } from '@/utils/helper';



interface Settings {
  company_name: string;
  contact_email: string;
  contact_phone: string;
  contact_address: string;
  config_sections?: {
    sections: Array<{
      key: string;
      [key: string]: any;
    }>;
  };
}

interface PageProps {
  settings: Settings;
  flash?: {
    success?: string;
    error?: string;
  };
}

export default function LandingPageSettings() {
  const { t } = useTranslation();
  const { settings, flash, globalSettings } = usePage<PageProps>().props;
  const { themeColor, customColor } = useBrand();
  const brandColor = themeColor === 'custom' ? customColor : THEME_COLORS[themeColor as keyof typeof THEME_COLORS];
  const [activeSection, setActiveSection] = useState<'general' | 'header' | 'hero' | 'features' | 'screenshots' | 'whychooseus' | 'about' | 'team' | 'testimonials' | 'plans' | 'faq' | 'newsletter' | 'contact' | 'footer' | 'order' | 'advanced'>('general');
  const [isLoading, setIsLoading] = useState(false);
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');

  // Helper function for consistent dark mode styling
  const sectionClasses = "bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm";
  const headingClasses = "text-lg font-semibold text-gray-900 dark:text-gray-100";
  const subheadingClasses = "text-sm text-gray-500 dark:text-gray-400";

  // Helper function to convert full URL to relative path for database storage
  const convertToRelativePath = (url: string): string => {
    if (!url) return url;
    if (!url.startsWith('http')) return url;
    const storageIndex = url.indexOf('/storage/');
    return storageIndex !== -1 ? url.substring(storageIndex) : url;
  };

  const { data, setData, post, processing, errors } = useForm<Settings>({
    company_name: settings.company_name,
    contact_email: settings.contact_email,
    contact_phone: settings.contact_phone,
    contact_address: settings.contact_address,
    config_sections: settings.config_sections && settings.config_sections.sections && settings.config_sections.sections.length > 0
      ? {
          sections: settings.config_sections.sections || [],
          theme: settings.config_sections.theme || defaultLandingPageSections.theme,
          seo: settings.config_sections.seo || defaultLandingPageSections.seo,
          section_order: settings.config_sections.section_order || defaultLandingPageSections.section_order,
          section_visibility: settings.config_sections.section_visibility || defaultLandingPageSections.section_visibility
        }
      : defaultLandingPageSections
  });

  const getSectionData = (key: string) => {
    return data.config_sections?.sections?.find(section => section.key === key) || {};
  };

  const updateSectionData = (key: string, updates: any) => {
    const sections = [...(data.config_sections?.sections || [])];
    const sectionIndex = sections.findIndex(section => section.key === key);

    if (sectionIndex >= 0) {
      sections[sectionIndex] = { ...sections[sectionIndex], ...updates };
    } else {
      sections.push({ key, ...updates });
    }

    setData('config_sections', {
      ...data.config_sections,
      sections
    });
  };

  const updateThemeData = (updates: any) => {
    setData('config_sections', {
      ...data.config_sections,
      theme: { ...data.config_sections?.theme, ...updates }
    });
  };

  const updateSeoData = (updates: any) => {
    setData('config_sections', {
      ...data.config_sections,
      seo: { ...data.config_sections?.seo, ...updates }
    });
  };

  const updateSectionVisibility = (sectionKey: string, visible: boolean) => {
    setData('config_sections', {
      ...data.config_sections,
      section_visibility: { ...data.config_sections?.section_visibility, [sectionKey]: visible }
    });
  };

  const updateSectionOrder = (newOrder: string[]) => {
    setData('config_sections', {
      ...data.config_sections,
      section_order: newOrder
    });
  };

  const handleDragStart = (e: React.DragEvent, index: number) => {
    e.dataTransfer.setData('text/plain', index.toString());
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
  };

  const handleDrop = (e: React.DragEvent, dropIndex: number) => {
    e.preventDefault();
    const dragIndex = parseInt(e.dataTransfer.getData('text/plain'));
    const currentOrder = [...(data.config_sections?.section_order || [])];
    const draggedItem = currentOrder[dragIndex];
    currentOrder.splice(dragIndex, 1);
    currentOrder.splice(dropIndex, 0, draggedItem);
    updateSectionOrder(currentOrder);
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setData(name as keyof Settings, value);
  };

  const saveSettings = () => {
    setIsLoading(true);



    router.post(route('landing-page.settings.update'), data, {
      preserveScroll: true,
      onSuccess: (page) => {
        setIsLoading(false);
        const successMessage = page.props.flash?.success || t('Landing page settings saved successfully');
        const errorMessage = page.props.flash?.error;

        if (successMessage && !errorMessage) {
          toast.success(successMessage);
        } else if (errorMessage) {
          toast.error(errorMessage);
        }
      },
      onError: (errors) => {
        setIsLoading(false);
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save landing page settings');
        toast.error(errorMessage);
      }
    });
  };
  const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Landing Page Settings') }
      ];

  return (
    <PageTemplate
      title={t("Landing Page Settings")}
      url="/landing-page/settings"
    breadcrumbs={breadcrumbs}
      action={
        <div className="flex gap-2">
          <Link
            href={route('landing-page')}
            className="inline-flex items-center gap-2 px-4 py-2 text-white rounded-lg transition-colors"
            style={{ backgroundColor: brandColor }}
          >
            <Eye className="w-4 h-4" />
            {t("View Landing Page")}
          </Link>
        </div>
      }
    >
      <SettingsSection
        title={t('Landing Page Settings')}
        description={t('Customize your landing page content and appearance')}
        action={
          <Button onClick={saveSettings} disabled={isLoading} size="sm">
            <Save className="h-4 w-4 mr-2" />
            {isLoading ? t('Saving...') : t('Save Changes')}
          </Button>
        }
      >
        {/* Mobile Sidebar Toggle */}
        <div className="lg:hidden mb-4">
          <Button
            variant="outline"
            onClick={() => setSidebarOpen(!sidebarOpen)}
            className="w-full flex items-center justify-center gap-2"
          >
            <Layout className="h-4 w-4" />
            {t('Sections')}
            {sidebarOpen ? <X className="h-4 w-4" /> : <Plus className="h-4 w-4" />}
          </Button>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
          {/* Sidebar Navigation */}
          <div className={`lg:col-span-1 ${sidebarOpen ? 'block' : 'hidden lg:block'}`}>
            <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm sticky top-4">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100 px-2">{t('Sections')}</h3>
                <button
                  onClick={() => setSidebarOpen(false)}
                  className="lg:hidden p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                >
                  <X className="h-4 w-4" />
                </button>
              </div>

              {/* Search */}
              <div className="mb-4 px-2">
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                  <Input
                    type="text"
                    placeholder={t('Search sections...')}
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="pl-10 h-8 text-sm border-gray-200 dark:border-gray-700 dark:bg-gray-900"
                  />
                </div>
              </div>
              <nav className="space-y-1">
                {(() => {
                  const allSections = [
                    { key: 'general', label: t('General'), icon: SettingsIcon, group: 'Setup' },
                    { key: 'order', label: t('Order'), icon: ArrowUpDown, group: 'Setup' },
                    { key: 'advanced', label: t('Advanced'), icon: Code, group: 'Setup' },
                    { key: 'header', label: t('Header'), icon: Layout, group: 'Layout' },
                    { key: 'hero', label: t('Hero'), icon: Type, group: 'Layout' },
                    { key: 'footer', label: t('Footer'), icon: Globe, group: 'Layout' },
                    { key: 'features', label: t('Features'), icon: Award, group: 'Content' },
                    { key: 'screenshots', label: t('Screenshots'), icon: Monitor, group: 'Content' },
                    { key: 'whychooseus', label: t('Why Us'), icon: Users, group: 'Content' },
                    { key: 'about', label: t('About'), icon: Info, group: 'Content' },
                    { key: 'team', label: t('Team'), icon: Users, group: 'Social' },
                    { key: 'testimonials', label: t('Reviews'), icon: Award, group: 'Social' },
                    { key: 'plans', label: t('Plans'), icon: CreditCard, group: 'Social' },
                    { key: 'faq', label: t('FAQ'), icon: HelpCircle, group: 'Engagement' },
                    { key: 'newsletter', label: t('Newsletter'), icon: Mail, group: 'Engagement' },
                    { key: 'contact', label: t('Contact'), icon: Phone, group: 'Engagement' }
                  ];

                  const filteredSections = searchTerm
                    ? allSections.filter(section =>
                      section.label.toLowerCase().includes(searchTerm.toLowerCase()) ||
                      section.group.toLowerCase().includes(searchTerm.toLowerCase())
                    )
                    : allSections;

                  if (searchTerm && filteredSections.length > 0) {
                    // Show filtered results without groups
                    return (
                      <div className="space-y-1">
                        {filteredSections.map(section => {
                          const IconComponent = section.icon;
                          return (
                            <button
                              key={section.key}
                              onClick={() => {
                                setActiveSection(section.key as any);
                                setSidebarOpen(false);
                                setSearchTerm('');
                              }}
                              className={`w-full flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors text-left cursor-pointer ${activeSection === section.key
                                  ? 'text-white dark:text-white shadow-sm'
                                  : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                }`}
                              style={activeSection === section.key ? {
                                backgroundColor: brandColor
                              } : {}}
                            >
                              <IconComponent className="h-4 w-4" />
                              <div>
                                <div>{section.label}</div>
                                <div className="text-xs opacity-75">{section.group}</div>
                              </div>
                            </button>
                          );
                        })}
                      </div>
                    );
                  }

                  if (searchTerm && filteredSections.length === 0) {
                    return (
                      <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                        <Search className="h-8 w-8 mx-auto mb-2 opacity-50" />
                        <p className="text-sm">{t('No sections found')}</p>
                      </div>
                    );
                  }

                  // Show grouped sections when no search
                  return (
                    <>
                      {/* Setup Group */}
                      <div className="mb-4">
                        <div className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-2 mb-2">
                          {t('Setup')}
                        </div>
                        {[
                          { key: 'general', label: t('General'), icon: SettingsIcon },
                          { key: 'order', label: t('Order'), icon: ArrowUpDown },
                          { key: 'advanced', label: t('Advanced'), icon: Code }
                        ].map(section => (
                          <button
                            key={section.key}
                            onClick={() => {
                              setActiveSection(section.key as any);
                              setSidebarOpen(false);
                            }}
                            className={`w-full flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors text-left cursor-pointer ${activeSection === section.key
                                ? 'text-white dark:text-white shadow-sm'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                              }`}
                            style={activeSection === section.key ? {
                              backgroundColor: brandColor
                            } : {}}
                          >
                            <section.icon className="h-4 w-4" />
                            {section.label}
                          </button>
                        ))}
                      </div>

                      {/* Layout Group */}
                      <div className="mb-4">
                        <div className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-2 mb-2">
                          {t('Layout')}
                        </div>
                        {[
                          { key: 'header', label: t('Header'), icon: Layout },
                          { key: 'hero', label: t('Hero'), icon: Type },
                          { key: 'footer', label: t('Footer'), icon: Globe }
                        ].map(section => (
                          <button
                            key={section.key}
                            onClick={() => {
                              setActiveSection(section.key as any);
                              setSidebarOpen(false);
                            }}
                            className={`w-full flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors text-left cursor-pointer ${activeSection === section.key
                                ? 'text-white dark:text-white shadow-sm'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                              }`}
                            style={activeSection === section.key ? {
                              backgroundColor: brandColor
                            } : {}}
                          >
                            <section.icon className="h-4 w-4" />
                            {section.label}
                          </button>
                        ))}
                      </div>

                      {/* Content Group */}
                      <div className="mb-4">
                        <div className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-2 mb-2">
                          {t('Content')}
                        </div>
                        {[
                          { key: 'features', label: t('Features'), icon: Award },
                          { key: 'screenshots', label: t('Screenshots'), icon: Monitor },
                          { key: 'whychooseus', label: t('Why Us'), icon: Users },
                          { key: 'about', label: t('About'), icon: Info }
                        ].map(section => (
                          <button
                            key={section.key}
                            onClick={() => {
                              setActiveSection(section.key as any);
                              setSidebarOpen(false);
                            }}
                            className={`w-full flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors text-left cursor-pointer ${activeSection === section.key
                                ? 'text-white dark:text-white shadow-sm'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                              }`}
                            style={activeSection === section.key ? {
                              backgroundColor: brandColor
                            } : {}}
                          >
                            <section.icon className="h-4 w-4" />
                            {section.label}
                          </button>
                        ))}
                      </div>

                      {/* Social Group */}
                      <div className="mb-4">
                        <div className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-2 mb-2">
                          {t('Social')}
                        </div>
                        {[
                          { key: 'team', label: t('Team'), icon: Users },
                          { key: 'testimonials', label: t('Reviews'), icon: Award },
                          { key: 'plans', label: t('Plans'), icon: CreditCard }
                        ].map(section => (
                          <button
                            key={section.key}
                            onClick={() => {
                              setActiveSection(section.key as any);
                              setSidebarOpen(false);
                            }}
                            className={`w-full flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors text-left cursor-pointer ${activeSection === section.key
                                ? 'text-white dark:text-white shadow-sm'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                              }`}
                            style={activeSection === section.key ? {
                              backgroundColor: brandColor
                            } : {}}
                          >
                            <section.icon className="h-4 w-4" />
                            {section.label}
                          </button>
                        ))}
                      </div>

                      {/* Engagement Group */}
                      <div className="mb-4">
                        <div className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider px-2 mb-2">
                          {t('Engagement')}
                        </div>
                        {[
                          { key: 'faq', label: t('FAQ'), icon: HelpCircle },
                          { key: 'newsletter', label: t('Newsletter'), icon: Mail },
                          { key: 'contact', label: t('Contact'), icon: Phone }
                        ].map(section => (
                          <button
                            key={section.key}
                            onClick={() => {
                              setActiveSection(section.key as any);
                              setSidebarOpen(false);
                            }}
                            className={`w-full flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors text-left cursor-pointer ${activeSection === section.key
                                ? 'text-white dark:text-white shadow-sm'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                              }`}
                            style={activeSection === section.key ? {
                              backgroundColor: brandColor
                            } : {}}
                          >
                            <section.icon className="h-4 w-4" />
                            {section.label}
                          </button>
                        ))}
                      </div>
                    </>
                  );
                })()}
              </nav>
            </div>
          </div>

          {/* Main Content */}
          <div className="lg:col-span-3 space-y-6">
            {/* Section Header */}
            <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
              <div className="flex items-center gap-3">
                <div className="p-2 rounded-lg" style={{ backgroundColor: brandColor + '20' }}>
                  {(() => {
                    const sectionIcons = {
                      general: SettingsIcon,
                      order: ArrowUpDown,
                      advanced: Code,
                      header: Layout,
                      hero: Type,
                      footer: Globe,
                      features: Award,
                      screenshots: Monitor,
                      whychooseus: Users,
                      about: Info,
                      team: Users,
                      testimonials: Award,
                      plans: CreditCard,
                      faq: HelpCircle,
                      newsletter: Mail,
                      contact: Phone
                    };
                    const IconComponent = sectionIcons[activeSection] || SettingsIcon;
                    return <IconComponent className="h-5 w-5" style={{ color: brandColor }} />;
                  })()}
                </div>
                <div>
                  <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    {(() => {
                      const sectionTitles = {
                        general: t('General Settings'),
                        order: t('Section Order'),
                        advanced: t('Advanced Settings'),
                        header: t('Header Configuration'),
                        hero: t('Hero Section'),
                        footer: t('Footer Settings'),
                        features: t('Features Section'),
                        screenshots: t('Screenshots Gallery'),
                        whychooseus: t('Why Choose Us'),
                        about: t('About Section'),
                        team: t('Team Section'),
                        testimonials: t('Testimonials'),
                        plans: t('Pricing Plans'),
                        faq: t('FAQ Section'),
                        newsletter: t('Newsletter'),
                        contact: t('Contact Section')
                      };
                      return sectionTitles[activeSection] || t('Settings');
                    })()}
                  </h2>
                  <p className="text-sm text-gray-500 dark:text-gray-400">
                    {(() => {
                      const sectionDescriptions = {
                        general: t('Configure basic company information and theme colors'),
                        order: t('Drag and drop to reorder sections on your landing page'),
                        advanced: t('SEO settings, custom CSS and JavaScript'),
                        header: t('Customize header appearance and navigation'),
                        hero: t('Configure the main hero section with title, subtitle and CTA'),
                        footer: t('Footer content, links and social media'),
                        features: t('Showcase your product features and benefits'),
                        screenshots: t('Display application screenshots and demos'),
                        whychooseus: t('Highlight reasons why customers should choose you'),
                        about: t('Tell your company story and mission'),
                        team: t('Showcase your team members and their roles'),
                        testimonials: t('Display customer reviews and testimonials'),
                        plans: t('Configure pricing plans and features'),
                        faq: t('Frequently asked questions and answers'),
                        newsletter: t('Newsletter subscription and benefits'),
                        contact: t('Contact form and company information')
                      };
                      return sectionDescriptions[activeSection] || t('Customize your landing page');
                    })()}
                  </p>
                </div>
              </div>
            </div>

            {/* General Section */}
            {activeSection === 'general' && (
              <div className="space-y-6">
                <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                      <Type className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">{t('Company Information')}</h3>
                      <p className="text-sm text-gray-500 dark:text-gray-400">{t('Basic company details for your landing page')}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="company_name" className="text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2" required>
                        <SettingsIcon className="h-4 w-4" style={{ color: brandColor }} />
                        {t('Company Name')}
                      </Label>
                      <Input
                        id="company_name"
                        name="company_name"
                        value={data.company_name}
                        onChange={handleInputChange}
                        placeholder={t('Your Company Name')}
                        className="h-10 border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        style={{ '--tw-ring-color': brandColor + '33' } as React.CSSProperties}
                      />
                      {errors.company_name && (
                        <div className="flex items-center gap-2 text-red-600 text-sm bg-red-50 p-2 rounded-md border border-red-200">
                          <X className="h-4 w-4" />
                          {errors.company_name}
                        </div>
                      )}
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="contact_email" required>{t('Contact Email')}</Label>
                      <Input
                        id="contact_email"
                        name="contact_email"
                        type="email"
                        value={data.contact_email}
                        onChange={handleInputChange}
                        placeholder={t('support@company.com')}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="contact_phone" required>{t('Contact Phone')}</Label>
                      <Input
                        id="contact_phone"
                        name="contact_phone"
                        value={data.contact_phone}
                        onChange={handleInputChange}
                        placeholder={t('+1 (555) 123-4567')}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="contact_address" required>{t('Contact Address')}</Label>
                      <Input
                        id="contact_address"
                        name="contact_address"
                        value={data.contact_address}
                        onChange={handleInputChange}
                        placeholder={t('123 Business Ave, City, State')}
                      />
                    </div>
                  </div>

                  <div className="mt-6 p-4 bg-gradient-to-r rounded-lg border" style={{ backgroundColor: brandColor + '10', borderColor: brandColor + '30' }}>
                    <h4 className="text-sm font-medium mb-3" style={{ color: brandColor }}>{t('Theme Colors')}</h4>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div className="space-y-3">
                        <Label htmlFor="general_primary_color">{t('Primary Color')}</Label>
                        <div className="flex gap-2">
                          <Input
                            id="general_primary_color"
                            type="color"
                            value={data.config_sections?.theme?.primary_color || '#3b82f6'}
                            onChange={(e) => updateThemeData({ primary_color: e.target.value })}
                            className="w-16 h-10 p-1 cursor-pointer"
                          />
                          <Input
                            value={data.config_sections?.theme?.primary_color || '#3b82f6'}
                            onChange={(e) => updateThemeData({ primary_color: e.target.value })}
                            placeholder="#3b82f6"
                          />
                        </div>
                      </div>

                      <div className="space-y-3">
                        <Label htmlFor="general_secondary_color">{t('Secondary Color')}</Label>
                        <div className="flex gap-2">
                          <Input
                            id="general_secondary_color"
                            type="color"
                            value={data.config_sections?.theme?.secondary_color || '#8b5cf6'}
                            onChange={(e) => updateThemeData({ secondary_color: e.target.value })}
                            className="w-16 h-10 p-1 cursor-pointer"
                          />
                          <Input
                            value={data.config_sections?.theme?.secondary_color || '#8b5cf6'}
                            onChange={(e) => updateThemeData({ secondary_color: e.target.value })}
                            placeholder="#8b5cf6"
                          />
                        </div>
                      </div>

                      <div className="space-y-3">
                        <Label htmlFor="general_accent_color">{t('Accent Color')}</Label>
                        <div className="flex gap-2">
                          <Input
                            id="general_accent_color"
                            type="color"
                            value={data.config_sections?.theme?.accent_color || '#10b77f'}
                            onChange={(e) => updateThemeData({ accent_color: e.target.value })}
                            className="w-16 h-10 p-1 cursor-pointer"
                          />
                          <Input
                            value={data.config_sections?.theme?.accent_color || '#10b77f'}
                            onChange={(e) => updateThemeData({ accent_color: e.target.value })}
                            placeholder="#10b77f"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Header Section */}
            {activeSection === 'header' && (
              <div className="space-y-6">
                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                      <div className="p-2 bg-green-100 rounded-lg">
                        <Layout className="h-5 w-5 text-green-600" />
                      </div>
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900">{t('Header Style')}</h3>
                        <p className="text-sm text-gray-500">{t('Customize your header appearance and behavior')}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Label className="text-sm">{t('Enable Section')}</Label>
                      <Switch
                        checked={data.config_sections?.section_visibility?.header !== false}
                        onCheckedChange={(checked) => updateSectionVisibility('header', checked)}
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-3">
                      <div className="flex items-center justify-between">
                        <Label htmlFor="header_transparent">{t('Transparent Header')}</Label>
                        <Switch
                          id="header_transparent"
                          checked={getSectionData('header').transparent || false}
                          onCheckedChange={(checked) => updateSectionData('header', { transparent: checked })}
                        />
                      </div>
                      <p className="text-xs text-muted-foreground">
                        {t('Make header background transparent')}
                      </p>
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="header_background_color">{t('Background Color')}</Label>
                      <div className="flex gap-2">
                        <Input
                          id="header_background_color"
                          type="color"
                          value={getSectionData('header').background_color || '#ffffff'}
                          onChange={(e) => updateSectionData('header', { background_color: e.target.value })}
                          className="w-16 h-10 p-1 cursor-pointer"
                          disabled={getSectionData('header').transparent}
                        />
                        <Input
                          value={getSectionData('header').background_color || '#ffffff'}
                          onChange={(e) => updateSectionData('header', { background_color: e.target.value })}
                          placeholder="#ffffff"
                          disabled={getSectionData('header').transparent}
                        />
                      </div>
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="header_text_color">{t('Text Color')}</Label>
                      <div className="flex gap-2">
                        <Input
                          id="header_text_color"
                          type="color"
                          value={getSectionData('header').text_color || '#1f2937'}
                          onChange={(e) => updateSectionData('header', { text_color: e.target.value })}
                          className="w-16 h-10 p-1 cursor-pointer"
                        />
                        <Input
                          value={getSectionData('header').text_color || '#1f2937'}
                          onChange={(e) => updateSectionData('header', { text_color: e.target.value })}
                          placeholder="#1f2937"
                        />
                      </div>
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="header_button_style">{t('Button Style')}</Label>
                      <select
                        id="header_button_style"
                        value={getSectionData('header').button_style || 'solid'}
                        onChange={(e) => updateSectionData('header', { button_style: e.target.value })}
                        className="w-full p-2 border border-gray-300 rounded-md cursor-pointer"
                      >
                        <option value="gradient">Gradient</option>
                        <option value="solid">Solid</option>
                        <option value="outline">Outline</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Hero Section */}
            {activeSection === 'hero' && (
              <div className="space-y-6">
                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                      <div className="p-2 bg-orange-100 rounded-lg">
                        <Layout className="h-5 w-5 text-orange-600" />
                      </div>
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900">{t('Hero Layout')}</h3>
                        <p className="text-sm text-gray-500">{t('Configure hero section layout and dimensions')}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Label className="text-sm">{t('Enable Section')}</Label>
                      <Switch
                        checked={data.config_sections?.section_visibility?.hero !== false}
                        onCheckedChange={(checked) => updateSectionVisibility('hero', checked)}
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="hero_layout">{t('Layout Style')}</Label>
                      <select
                        id="hero_layout"
                        value={getSectionData('hero').layout || 'image-right'}
                        onChange={(e) => updateSectionData('hero', { layout: e.target.value })}
                        className="w-full p-2 border border-gray-300 rounded-md cursor-pointer"
                      >
                        <option value="image-right">Content Left, Image Right</option>
                        <option value="image-left">Image Left, Content Right</option>
                        <option value="full-width">Full Width</option>
                        <option value="centered">Centered Content</option>
                      </select>
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="hero_height">{t('Section Height')}</Label>
                      <Input
                        id="hero_height"
                        type="number"
                        value={getSectionData('hero').height || 600}
                        onChange={(e) => updateSectionData('hero', { height: parseInt(e.target.value) })}
                        min="300"
                        max="1000"
                      />
                      <p className="text-xs text-muted-foreground">
                        {t('Height in pixels (300-1000)')}
                      </p>
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-purple-100 rounded-lg">
                      <Type className="h-5 w-5 text-purple-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Hero Content')}</h3>
                      <p className="text-sm text-gray-500">{t('Main headline and supporting text')}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="hero_title" className="text-sm font-medium text-gray-900 flex items-center gap-2">
                        <Type className="h-4 w-4" style={{ color: brandColor }} />
                        {t('Hero Title')}
                      </Label>
                      <Input
                        id="hero_title"
                        value={getSectionData('hero').title || ''}
                        onChange={(e) => updateSectionData('hero', { title: e.target.value })}
                        placeholder={t("Your main headline")}
                        className="h-10 border-gray-200"
                        style={{ '--tw-ring-color': brandColor + '33' } as React.CSSProperties}
                      />
                      {errors.hero_title && (
                        <div className="flex items-center gap-2 text-red-600 text-sm bg-red-50 p-2 rounded-md border border-red-200">
                          <X className="h-4 w-4" />
                          {errors.hero_title}
                        </div>
                      )}
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="hero_subtitle" className="text-sm font-medium text-gray-900 flex items-center gap-2">
                        <FileText className="h-4 w-4" style={{ color: brandColor }} />
                        {t('Hero Subtitle')}
                      </Label>
                      <Textarea
                        id="hero_subtitle"
                        value={getSectionData('hero').subtitle || ''}
                        onChange={(e) => updateSectionData('hero', { subtitle: e.target.value })}
                        placeholder={t("Supporting text for your headline")}
                        rows={3}
                        className="border-gray-200 resize-none"
                        style={{ '--tw-ring-color': brandColor + '33' } as React.CSSProperties}
                      />
                      {errors.hero_subtitle && (
                        <div className="flex items-center gap-2 text-red-600 text-sm bg-red-50 p-2 rounded-md border border-red-200">
                          <X className="h-4 w-4" />
                          {errors.hero_subtitle}
                        </div>
                      )}
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="hero_announcement_text">{t('Announcement Badge')}</Label>
                      <Input
                        id="hero_announcement_text"
                        value={getSectionData('hero').announcement_text || ''}
                        onChange={(e) => updateSectionData('hero', { announcement_text: e.target.value })}
                        placeholder={t("🚀 New: Advanced Analytics Dashboard")}
                      />
                      <p className="text-xs text-muted-foreground">
                        {t('Small announcement text shown above the title')}
                      </p>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                      <div className="space-y-3">
                        <Label htmlFor="hero_primary_button_text">{t('Primary Button Text')}</Label>
                        <Input
                          id="hero_primary_button_text"
                          value={getSectionData('hero').primary_button_text || ''}
                          onChange={(e) => updateSectionData('hero', { primary_button_text: e.target.value })}
                          placeholder={t("Start Free Trial")}
                        />
                      </div>

                      <div className="space-y-3">
                        <Label htmlFor="hero_secondary_button_text">{t('Secondary Button Text')}</Label>
                        <Input
                          id="hero_secondary_button_text"
                          value={getSectionData('hero').secondary_button_text || ''}
                          onChange={(e) => updateSectionData('hero', { secondary_button_text: e.target.value })}
                          placeholder={t("Login")}
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-indigo-100 rounded-lg">
                      <SettingsIcon className="h-5 w-5 text-indigo-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Hero Statistics')}</h3>
                      <p className="text-sm text-gray-500">{t('Add compelling statistics to your hero section')}</p>
                    </div>
                  </div>

                  <div className="space-y-4">
                    {(getSectionData('hero').stats || []).map((stat, index) => (
                      <div key={index} className="grid grid-cols-2 gap-4 p-4 border rounded-lg">
                        <div className="space-y-3">
                          <Label htmlFor={`hero_stats_${index}_value`}>{t('Value')}</Label>
                          <Input
                            id={`hero_stats_${index}_value`}
                            value={stat.value || ''}
                            onChange={(e) => {
                              const newStats = [...(getSectionData('hero').stats || [])];
                              newStats[index] = { ...newStats[index], value: e.target.value };
                              updateSectionData('hero', { stats: newStats });
                            }}
                            placeholder="10K+"
                          />
                        </div>
                        <div className="space-y-3">
                          <Label htmlFor={`hero_stats_${index}_label`}>{t('Label')}</Label>
                          <div className="flex gap-2">
                            <Input
                              id={`hero_stats_${index}_label`}
                              value={stat.label || ''}
                              onChange={(e) => {
                                const newStats = [...(getSectionData('hero').stats || [])];
                                newStats[index] = { ...newStats[index], label: e.target.value };
                                updateSectionData('hero', { stats: newStats });
                              }}
                              placeholder={t("Active Users")}
                            />
                            <Button
                              type="button"
                              variant="outline"
                              size="sm"
                              className="text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200"
                              onClick={() => {
                                const newStats = (getSectionData('hero').stats || []).filter((_, i) => i !== index);
                                updateSectionData('hero', { stats: newStats });
                              }}
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </div>
                      </div>
                    ))}

                    <Button
                      type="button"
                      variant="outline"
                      className="border-2"
                      style={{ color: brandColor, borderColor: brandColor }}
                      onClick={() => {
                        const newStats = [...(getSectionData('hero').stats || []), { value: '', label: '' }];
                        updateSectionData('hero', { stats: newStats });
                      }}
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      {t('Add Statistic')}
                    </Button>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-pink-100 rounded-lg">
                      <Image className="h-5 w-5 text-pink-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Hero Image')}</h3>
                      <p className="text-sm text-gray-500">{t('Configure hero section imagery')}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-3">
                      <MediaPicker
                        label={t('Hero Image')}
                        value={getDisplayUrl(getSectionData('hero').image || '')}
                        onChange={(value) => {
                          updateSectionData('hero', { image: convertToRelativePath(value) });
                        }}
                        placeholder={t('Select hero image...')}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="hero_image_position">{t('Image Position')}</Label>
                      <select
                        id="hero_image_position"
                        value={getSectionData('hero').image_position || 'right'}
                        onChange={(e) => updateSectionData('hero', { image_position: e.target.value })}
                        className="w-full p-2 border border-gray-300 rounded-md cursor-pointer"
                      >
                        <option value="right">{t('Right Side')}</option>
                        <option value="left">{t('Left Side')}</option>
                        <option value="center">{t('Center')}</option>
                        <option value="background">{t('Background')}</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-violet-100 rounded-lg">
                      <Palette className="h-5 w-5 text-violet-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Hero Colors')}</h3>
                      <p className="text-sm text-gray-500">{t('Customize hero section colors and overlays')}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="hero_background_color">{t("Background Color")}</Label>
                      <div className="flex gap-2">
                        <Input
                          id="hero_background_color"
                          type="color"
                          value={getSectionData('hero').background_color || '#f8fafc'}
                          onChange={(e) => updateSectionData('hero', { background_color: e.target.value })}
                          className="w-16 h-10 p-1 cursor-pointer"
                        />
                        <Input
                          value={getSectionData('hero').background_color || '#f8fafc'}
                          onChange={(e) => updateSectionData('hero', { background_color: e.target.value })}
                          placeholder="#f8fafc"
                        />
                      </div>
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="hero_text_color">{t("Text Color")}</Label>
                      <div className="flex gap-2">
                        <Input
                          id="hero_text_color"
                          type="color"
                          value={getSectionData('hero').text_color || '#1f2937'}
                          onChange={(e) => updateSectionData('hero', { text_color: e.target.value })}
                          className="w-16 h-10 p-1 cursor-pointer"
                        />
                        <Input
                          value={getSectionData('hero').text_color || '#1f2937'}
                          onChange={(e) => updateSectionData('hero', { text_color: e.target.value })}
                          placeholder="#1f2937"
                        />
                      </div>
                    </div>

                    <div className="space-y-3">
                      <div className="flex items-center justify-between">
                        <Label htmlFor="hero_overlay">{t('Background Overlay')}</Label>
                        <Switch
                          id="hero_overlay"
                          checked={getSectionData('hero').overlay || false}
                          onCheckedChange={(checked) => updateSectionData('hero', { overlay: checked })}
                        />
                      </div>
                      <p className="text-xs text-muted-foreground">
                        {t('Add overlay on background image')}
                      </p>
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="hero_overlay_color">{t('Overlay Color')}</Label>
                      <Input
                        id="hero_overlay_color"
                        value={getSectionData('hero').overlay_color || 'rgba(0,0,0,0.5)'}
                        onChange={(e) => updateSectionData('hero', { overlay_color: e.target.value })}
                        placeholder="rgba(0,0,0,0.5)"
                        disabled={!getSectionData('hero').overlay}
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Features Section */}
            {activeSection === 'features' && (
              <FeaturesSection
                data={data}
                setData={setData}
                errors={errors}
                handleInputChange={handleInputChange}
                getSectionData={getSectionData}
                updateSectionData={updateSectionData}
                updateSectionVisibility={updateSectionVisibility}
                t={t}
              />
            )}

            {/* Screenshots Section */}
            {activeSection === 'screenshots' && (
              <div className="space-y-6">
                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                      <div className="p-2 bg-blue-100 rounded-lg">
                        <Type className="h-5 w-5 text-blue-600" />
                      </div>
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900">{t('Screenshots Content')}</h3>
                        <p className="text-sm text-gray-500">{t('Section title and description')}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Label className="text-sm">{t('Enable Section')}</Label>
                      <Switch
                        checked={data.config_sections?.section_visibility?.screenshots !== false}
                        onCheckedChange={(checked) => updateSectionVisibility('screenshots', checked)}
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="screenshots_title">{t('Section Title')}</Label>
                      <Input
                        id="screenshots_title"
                        value={getSectionData('screenshots').title || ''}
                        onChange={(e) => updateSectionData('screenshots', { title: e.target.value })}
                        placeholder={t("See Advocate in Action")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="screenshots_subtitle">{t('Section Subtitle')}</Label>
                      <Textarea
                        id="screenshots_subtitle"
                        value={getSectionData('screenshots').subtitle || ''}
                        onChange={(e) => updateSectionData('screenshots', { subtitle: e.target.value })}
                        placeholder={t("Explore our intuitive interface and powerful features...")}
                        rows={3}
                      />
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-purple-100 rounded-lg">
                      <Monitor className="h-5 w-5 text-purple-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Screenshots Gallery')}</h3>
                      <p className="text-sm text-gray-500">{t('Manage application screenshots')}</p>
                    </div>
                  </div>

                  <div className="space-y-4">
                    {(getSectionData('screenshots').screenshots_list || []).map((screenshot, index) => (
                      <div key={index} className="bg-gray-50 border border-gray-200 rounded-xl p-5">
                        <div className="flex items-center justify-between mb-4">
                          <h4 className="font-semibold text-gray-900 flex items-center gap-2">
                            <span className="w-6 h-6 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center text-xs font-bold">{index + 1}</span>
                            {t('Screenshot')} {index + 1}
                          </h4>
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200"
                            onClick={() => {
                              const newScreenshots = (getSectionData('screenshots').screenshots_list || []).filter((_, i) => i !== index);
                              updateSectionData('screenshots', { screenshots_list: newScreenshots });
                            }}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>

                        <div className="grid grid-cols-1 gap-4">
                          <div className="space-y-3">
                            <MediaPicker
                              label={t('Screenshot Image')}
                              value={getDisplayUrl(screenshot.src || '')}
                              onChange={(value) => {
                                const newScreenshots = [...(getSectionData('screenshots').screenshots_list || [])];
                                newScreenshots[index] = { ...newScreenshots[index], src: convertToRelativePath(value) };
                                updateSectionData('screenshots', { screenshots_list: newScreenshots });
                              }}
                              placeholder={t('Select screenshot image...')}
                            />
                          </div>

                          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-3">
                              <Label htmlFor={`screenshot_${index}_title`}>{t('Title')}</Label>
                              <Input
                                id={`screenshot_${index}_title`}
                                value={screenshot.title || ''}
                                onChange={(e) => {
                                  const newScreenshots = [...(getSectionData('screenshots').screenshots_list || [])];
                                  newScreenshots[index] = { ...newScreenshots[index], title: e.target.value };
                                  updateSectionData('screenshots', { screenshots_list: newScreenshots });
                                }}
                                placeholder={t("Dashboard Overview")}
                              />
                            </div>

                            <div className="space-y-3">
                              <Label htmlFor={`screenshot_${index}_alt`}>{t('Alt Text')}</Label>
                              <Input
                                id={`screenshot_${index}_alt`}
                                value={screenshot.alt || ''}
                                onChange={(e) => {
                                  const newScreenshots = [...(getSectionData('screenshots').screenshots_list || [])];
                                  newScreenshots[index] = { ...newScreenshots[index], alt: e.target.value };
                                  updateSectionData('screenshots', { screenshots_list: newScreenshots });
                                }}
                                placeholder={t("Advocate Dashboard Overview")}
                              />
                            </div>
                          </div>

                          <div className="space-y-3">
                            <Label htmlFor={`screenshot_${index}_description`}>{t('Description')}</Label>
                            <Textarea
                              id={`screenshot_${index}_description`}
                              value={screenshot.description || ''}
                              onChange={(e) => {
                                const newScreenshots = [...(getSectionData('screenshots').screenshots_list || [])];
                                newScreenshots[index] = { ...newScreenshots[index], description: e.target.value };
                                updateSectionData('screenshots', { screenshots_list: newScreenshots });
                              }}
                              placeholder={t("Comprehensive dashboard with all your digital cards and analytics")}
                              rows={2}
                            />
                          </div>
                        </div>
                      </div>
                    ))}

                    <Button
                      type="button"
                      variant="outline"
                      className="w-full border-2"
                      style={{ color: brandColor, borderColor: brandColor }}
                      onClick={() => {
                        const newScreenshots = [...(getSectionData('screenshots').screenshots_list || []), { src: '', alt: '', title: '', description: '' }];
                        updateSectionData('screenshots', { screenshots_list: newScreenshots });
                      }}
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      {t('Add Screenshot')}
                    </Button>
                  </div>
                </div>
              </div>
            )}

            {/* Templates Section */}
            {/* {activeSection === 'templates' && (
              <TemplatesSection
                data={data}
                setData={setData}
                errors={errors}
                handleInputChange={handleInputChange}
                getSectionData={getSectionData}
                updateSectionData={updateSectionData}
                updateSectionVisibility={updateSectionVisibility}
                t={t}
              />
            )} */}

            {/* WhyChooseUs Section */}
            {activeSection === 'whychooseus' && (
              <div className="space-y-6">
                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                      <div className="p-2 bg-emerald-100 rounded-lg">
                        <Type className="h-5 w-5 text-emerald-600" />
                      </div>
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900">{t('Why Choose Us Content')}</h3>
                        <p className="text-sm text-gray-500">{t('Main section title and description')}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Label className="text-sm">{t('Enable Section')}</Label>
                      <Switch
                        checked={data.config_sections?.section_visibility?.why_choose_us !== false}
                        onCheckedChange={(checked) => updateSectionVisibility('why_choose_us', checked)}
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="why_choose_us_title" className="text-sm font-medium text-gray-900 flex items-center gap-2">
                        <Type className="h-4 w-4 text-emerald-600" />
                        {t('Section Title')}
                      </Label>
                      <Input
                        id="why_choose_us_title"
                        value={getSectionData('why_choose_us').title || ''}
                        onChange={(e) => updateSectionData('why_choose_us', { title: e.target.value })}
                        placeholder={t("Why Choose Advocate?")}
                        className="h-10 border-gray-200 focus:border-emerald-500 focus:ring-emerald-500/20"
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="why_choose_us_subtitle">{t('Section Subtitle')}</Label>
                      <Textarea
                        id="why_choose_us_subtitle"
                        value={getSectionData('why_choose_us').subtitle || ''}
                        onChange={(e) => updateSectionData('why_choose_us', { subtitle: e.target.value })}
                        placeholder={t("We're not just another digital business card platform...")}
                        rows={3}
                      />
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-amber-100 rounded-lg">
                      <Award className="h-5 w-5 text-amber-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Reasons to Choose Us')}</h3>
                      <p className="text-sm text-gray-500">{t('Key benefits and advantages')}</p>
                    </div>
                  </div>

                  <div className="space-y-4">
                    {(getSectionData('why_choose_us').reasons || []).map((reason, index) => (
                      <div key={index} className="bg-gray-50 border border-gray-200 rounded-xl p-5">
                        <div className="flex items-center justify-between mb-4">
                          <h4 className="font-semibold text-gray-900 flex items-center gap-2">
                            <span className="w-6 h-6 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center text-xs font-bold">{index + 1}</span>
                            {t('Reason')} {index + 1}
                          </h4>
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200"
                            onClick={() => {
                              const newReasons = (getSectionData('why_choose_us').reasons || []).filter((_, i) => i !== index);
                              updateSectionData('why_choose_us', { reasons: newReasons });
                            }}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                          <div className="space-y-3">
                            <Label htmlFor={`reason_${index}_title`}>{t('Title')}</Label>
                            <Input
                              id={`reason_${index}_title`}
                              value={reason.title || ''}
                              onChange={(e) => {
                                const newReasons = [...(getSectionData('why_choose_us').reasons || [])];
                                newReasons[index] = { ...newReasons[index], title: e.target.value };
                                updateSectionData('why_choose_us', { reasons: newReasons });
                              }}
                              placeholder={t("Quick Setup")}
                            />
                          </div>

                          <div className="space-y-3">
                            <Label htmlFor={`reason_${index}_icon`}>{t('Icon')}</Label>
                            <select
                              id={`reason_${index}_icon`}
                              value={reason.icon || 'clock'}
                              onChange={(e) => {
                                const newReasons = [...(getSectionData('why_choose_us').reasons || [])];
                                newReasons[index] = { ...newReasons[index], icon: e.target.value };
                                updateSectionData('why_choose_us', { reasons: newReasons });
                              }}
                              className="w-full p-2 border border-gray-300 rounded-md cursor-pointer"
                            >
                              <option value="clock">Clock</option>
                              <option value="users">Users</option>
                              <option value="zap">Zap</option>
                              <option value="check-circle">Check Circle</option>
                              <option value="star">Star</option>
                              <option value="shield">Shield</option>
                              <option value="heart">Heart</option>
                              <option value="award">Award</option>
                              <option value="layers">Layers</option>
                              <option value="bar-chart">Bar Chart</option>

                            </select>
                          </div>

                          <div className="space-y-3 md:col-span-1">
                            <Label htmlFor={`reason_${index}_description`}>{t('Description')}</Label>
                            <Textarea
                              id={`reason_${index}_description`}
                              value={reason.description || ''}
                              onChange={(e) => {
                                const newReasons = [...(getSectionData('why_choose_us').reasons || [])];
                                newReasons[index] = { ...newReasons[index], description: e.target.value };
                                updateSectionData('why_choose_us', { reasons: newReasons });
                              }}
                              placeholder={t("Manage employees, payroll, attendance, recruitment, and performance from a single platform...")}
                              rows={3}
                            />
                          </div>
                        </div>
                      </div>
                    ))}

                    <Button
                      type="button"
                      variant="outline"
                      className="w-full border-2"
                      style={{ color: brandColor, borderColor: brandColor }}
                      onClick={() => {
                        const newReasons = [...(getSectionData('why_choose_us').reasons || []), { title: '', description: '', icon: 'clock' }];
                        updateSectionData('why_choose_us', { reasons: newReasons });
                      }}
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      {t('Add Reason')}
                    </Button>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-cyan-100 rounded-lg">
                      <SettingsIcon className="h-5 w-5 text-cyan-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Statistics Section')}</h3>
                      <p className="text-sm text-gray-500">{t('Trust indicators and key metrics')}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4 mb-6">
                    <div className="space-y-3">
                      <Label htmlFor="why_choose_us_stats_title">{t('Statistics Title')}</Label>
                      <Input
                        id="why_choose_us_stats_title"
                        value={getSectionData('why_choose_us').stats_title || ''}
                        onChange={(e) => updateSectionData('why_choose_us', { stats_title: e.target.value })}
                        placeholder={t("Trusted by Industry Leaders")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="why_choose_us_stats_subtitle">{t('Statistics Subtitle')}</Label>
                      <Input
                        id="why_choose_us_stats_subtitle"
                        value={getSectionData('why_choose_us').stats_subtitle || ''}
                        onChange={(e) => updateSectionData('why_choose_us', { stats_subtitle: e.target.value })}
                        placeholder={t("Join the growing community of professionals")}
                      />
                    </div>
                  </div>

                  <div className="space-y-4">
                    {(getSectionData('why_choose_us').stats || []).map((stat, index) => (
                      <div key={index} className="grid grid-cols-3 gap-4 p-4 border rounded-lg">
                        <div className="space-y-3">
                          <Label htmlFor={`stat_${index}_value`}>{t("Value")}</Label>
                          <Input
                            id={`stat_${index}_value`}
                            value={stat.value || ''}
                            onChange={(e) => {
                              const newStats = [...(getSectionData('why_choose_us').stats || [])];
                              newStats[index] = { ...newStats[index], value: e.target.value };
                              updateSectionData('why_choose_us', { stats: newStats });
                            }}
                            placeholder="10K+"
                          />
                        </div>
                        <div className="space-y-3">
                          <Label htmlFor={`stat_${index}_label`}>{t("Label")}</Label>
                          <Input
                            id={`stat_${index}_label`}
                            value={stat.label || ''}
                            onChange={(e) => {
                              const newStats = [...(getSectionData('why_choose_us').stats || [])];
                              newStats[index] = { ...newStats[index], label: e.target.value };
                              updateSectionData('why_choose_us', { stats: newStats });
                            }}
                            placeholder={t("Active Users")}
                          />
                        </div>
                        <div className="space-y-3">
                          <Label htmlFor={`stat_${index}_color`}>{t('Color')}</Label>
                          <div className="flex gap-2">
                            <select
                              id={`stat_${index}_color`}
                              value={stat.color || 'blue'}
                              onChange={(e) => {
                                const newStats = [...(getSectionData('why_choose_us').stats || [])];
                                newStats[index] = { ...newStats[index], color: e.target.value };
                                updateSectionData('why_choose_us', { stats: newStats });
                              }}
                              className="w-full p-2 border border-gray-300 rounded-md cursor-pointer"
                            >
                              <option value="blue">Blue</option>
                              <option value="green">Green</option>
                              <option value="purple">Purple</option>
                              <option value="orange">Orange</option>
                              <option value="red">Red</option>
                              <option value="yellow">Yellow</option>
                            </select>
                            <Button
                              type="button"
                              variant="outline"
                              size="sm"
                              className="text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200"
                              onClick={() => {
                                const newStats = (getSectionData('why_choose_us').stats || []).filter((_, i) => i !== index);
                                updateSectionData('why_choose_us', { stats: newStats });
                              }}
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </div>
                      </div>
                    ))}

                    <Button
                      type="button"
                      variant="outline"
                      className="w-full border-2"
                      style={{ color: brandColor, borderColor: brandColor }}
                      onClick={() => {
                        const newStats = [...(getSectionData('why_choose_us').stats || []), { value: '', label: '', color: 'blue' }];
                        updateSectionData('why_choose_us', { stats: newStats });
                      }}
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      {t('Add Statistic')}
                    </Button>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-rose-100 rounded-lg">
                      <Type className="h-5 w-5 text-rose-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Call to Action')}</h3>
                      <p className="text-sm text-gray-500">{t('Encourage user engagement')}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="why_choose_us_cta_title">{t('CTA Title')}</Label>
                      <Input
                        id="why_choose_us_cta_title"
                        value={getSectionData('why_choose_us').cta_title || ''}
                        onChange={(e) => updateSectionData('why_choose_us', { cta_title: e.target.value })}
                        placeholder={t("Ready to get started?")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="why_choose_us_cta_subtitle">{t('CTA Subtitle')}</Label>
                      <Input
                        id="why_choose_us_cta_subtitle"
                        value={getSectionData('why_choose_us').cta_subtitle || ''}
                        onChange={(e) => updateSectionData('why_choose_us', { cta_subtitle: e.target.value })}
                        placeholder={t("Join thousands of satisfied users today")}
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* About Section */}
            {activeSection === 'about' && (
              <AboutSection
                data={data}
                setData={setData}
                errors={errors}
                handleInputChange={handleInputChange}
                getSectionData={getSectionData}
                updateSectionData={updateSectionData}
                updateSectionVisibility={updateSectionVisibility}
                t={t}
              />
            )}

            {/* Team Section */}
            {activeSection === 'team' && (
              <div className="space-y-6">
                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                      <div className="p-2 bg-blue-100 rounded-lg">
                        <Type className="h-5 w-5 text-blue-600" />
                      </div>
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900">{t('Team Content')}</h3>
                        <p className="text-sm text-gray-500">{t('Team section title and description')}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Label className="text-sm">{t('Enable Section')}</Label>
                      <Switch
                        checked={data.config_sections?.section_visibility?.team !== false}
                        onCheckedChange={(checked) => updateSectionVisibility('team', checked)}
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="team_title">{t('Team Title')}</Label>
                      <Input
                        id="team_title"
                        value={getSectionData('team').title || ''}
                        onChange={(e) => updateSectionData('team', { title: e.target.value })}
                        placeholder={t("Meet Our Team")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="team_subtitle">{t('Team Subtitle')}</Label>
                      <Textarea
                        id="team_subtitle"
                        value={getSectionData('team').subtitle || ''}
                        onChange={(e) => updateSectionData('team', { subtitle: e.target.value })}
                        placeholder={t("We're a diverse team of innovators...")}
                        rows={3}
                      />
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-purple-100 rounded-lg">
                      <Users className="h-5 w-5 text-purple-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Team Members')}</h3>
                      <p className="text-sm text-gray-500">{t('Add and manage team member profiles')}</p>
                    </div>
                  </div>

                  <div className="space-y-4">
                    {(getSectionData('team').members || []).map((member, index) => (
                      <div key={index} className="bg-gray-50 border border-gray-200 rounded-xl p-5">
                        <div className="flex items-center justify-between mb-4">
                          <h4 className="font-semibold text-gray-900 flex items-center gap-2">
                            <span className="w-6 h-6 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center text-xs font-bold">{index + 1}</span>
                            {t('Member')} {index + 1}
                          </h4>
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200"
                            onClick={() => {
                              const newMembers = (getSectionData('team').members || []).filter((_, i) => i !== index);
                              updateSectionData('team', { members: newMembers });
                            }}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <div className="space-y-3">
                            <Label htmlFor={`member_${index}_name`}>{t("Name")}</Label>
                            <Input
                              id={`member_${index}_name`}
                              value={member.name || ''}
                              onChange={(e) => {
                                const newMembers = [...(getSectionData('team').members || [])];
                                newMembers[index] = { ...newMembers[index], name: e.target.value };
                                updateSectionData('team', { members: newMembers });
                              }}
                              placeholder={t("John Doe")}
                            />
                          </div>

                          <div className="space-y-3">
                            <Label htmlFor={`member_${index}_role`}>{t('Role')}</Label>
                            <Input
                              id={`member_${index}_role`}
                              value={member.role || ''}
                              onChange={(e) => {
                                const newMembers = [...(getSectionData('team').members || [])];
                                newMembers[index] = { ...newMembers[index], role: e.target.value };
                                updateSectionData('team', { members: newMembers });
                              }}
                              placeholder={t("CEO & Founder")}
                            />
                          </div>

                          <div className="space-y-3 md:col-span-2">
                            <MediaPicker
                              label={t('Profile Image')}
                              value={getDisplayUrl(member.image || '')}
                              onChange={(value) => {
                                const newMembers = [...(getSectionData('team').members || [])];
                                newMembers[index] = { ...newMembers[index], image: convertToRelativePath(value) };
                                updateSectionData('team', { members: newMembers });
                              }}
                              placeholder={t('Select profile image...')}
                            />
                          </div>

                          <div className="space-y-3 md:col-span-2">
                            <Label htmlFor={`member_${index}_bio`}>{t('Bio')}</Label>
                            <Textarea
                              id={`member_${index}_bio`}
                              value={member.bio || ''}
                              onChange={(e) => {
                                const newMembers = [...(getSectionData('team').members || [])];
                                newMembers[index] = { ...newMembers[index], bio: e.target.value };
                                updateSectionData('team', { members: newMembers });
                              }}
                              placeholder={t("Brief description about the team member...")}
                              rows={2}
                            />
                          </div>

                          <div className="space-y-3">
                            <Label htmlFor={`member_${index}_linkedin`}>{t('LinkedIn')}</Label>
                            <Input
                              id={`member_${index}_linkedin`}
                              value={member.linkedin || ''}
                              onChange={(e) => {
                                const newMembers = [...(getSectionData('team').members || [])];
                                newMembers[index] = { ...newMembers[index], linkedin: e.target.value };
                                updateSectionData('team', { members: newMembers });
                              }}
                              placeholder="https://linkedin.com/in/..."
                            />
                          </div>

                          <div className="space-y-3">
                            <Label htmlFor={`member_${index}_email`}>{t('Email')}</Label>
                            <Input
                              id={`member_${index}_email`}
                              value={member.email || ''}
                              onChange={(e) => {
                                const newMembers = [...(getSectionData('team').members || [])];
                                newMembers[index] = { ...newMembers[index], email: e.target.value };
                                updateSectionData('team', { members: newMembers });
                              }}
                              placeholder="john@company.com"
                            />
                          </div>
                        </div>
                      </div>
                    ))}

                    <Button
                      type="button"
                      variant="outline"
                      className="w-full border-2"
                      style={{ color: brandColor, borderColor: brandColor }}
                      onClick={() => {
                        const newMembers = [...(getSectionData('team').members || []), { name: '', role: '', bio: '', image: '', linkedin: '', twitter: '', email: '' }];
                        updateSectionData('team', { members: newMembers });
                      }}
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      {t('Add Team Member')}
                    </Button>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-green-100 rounded-lg">
                      <Type className="h-5 w-5 text-green-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Join Team CTA')}</h3>
                      <p className="text-sm text-gray-500">{t('Call-to-action for team recruitment')}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="team_cta_title">{t("CTA Title")}</Label>
                      <Input
                        id="team_cta_title"
                        value={getSectionData('team').cta_title || ''}
                        onChange={(e) => updateSectionData('team', { cta_title: e.target.value })}
                        placeholder={t("Want to Join Our Team?")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="team_cta_description">{t('CTA Description')}</Label>
                      <Textarea
                        id="team_cta_description"
                        value={getSectionData('team').cta_description || ''}
                        onChange={(e) => updateSectionData('team', { cta_description: e.target.value })}
                        placeholder={t("We're always looking for talented individuals...")}
                        rows={3}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="team_cta_button_text">{t('Button Text')}</Label>
                      <Input
                        id="team_cta_button_text"
                        value={getSectionData('team').cta_button_text || ''}
                        onChange={(e) => updateSectionData('team', { cta_button_text: e.target.value })}
                        placeholder={t("View Open Positions")}
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Testimonials Section */}
            {activeSection === 'testimonials' && (
              <div className="space-y-6">
                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                      <div className="p-2 bg-green-100 rounded-lg">
                        <Type className="h-5 w-5 text-green-600" />
                      </div>
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900">{t('Testimonials Content')}</h3>
                        <p className="text-sm text-gray-500">{t('Section title and description')}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Label className="text-sm">{t('Enable Section')}</Label>
                      <Switch
                        checked={data.config_sections?.section_visibility?.testimonials !== false}
                        onCheckedChange={(checked) => updateSectionVisibility('testimonials', checked)}
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="testimonials_title">{t("Section Title")}</Label>
                      <Input
                        id="testimonials_title"
                        value={getSectionData('testimonials').title || ''}
                        onChange={(e) => updateSectionData('testimonials', { title: e.target.value })}
                        placeholder={t("What Our Clients Say")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="testimonials_subtitle">{t("Section Subtitle")}</Label>
                      <Textarea
                        id="testimonials_subtitle"
                        value={getSectionData('testimonials').subtitle || ''}
                        onChange={(e) => updateSectionData('testimonials', { subtitle: e.target.value })}
                        placeholder={t("Don't just take our word for it...")}
                        rows={3}
                      />
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-yellow-100 rounded-lg">
                      <SettingsIcon className="h-5 w-5 text-yellow-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Trust Indicators')}</h3>
                      <p className="text-sm text-gray-500">{t('Statistics that build credibility')}</p>
                    </div>
                  </div>

                  <div className="space-y-4">
                    <div className="space-y-3">
                      <Label htmlFor="testimonials_trust_title">{t('Trust Section Title')}</Label>
                      <Input
                        id="testimonials_trust_title"
                        value={getSectionData('testimonials').trust_title || ''}
                        onChange={(e) => updateSectionData('testimonials', { trust_title: e.target.value })}
                        placeholder={t("Trusted by Professionals Worldwide")}
                      />
                    </div>

                    {(getSectionData('testimonials').trust_stats || []).map((stat, index) => (
                      <div key={index} className="grid grid-cols-3 gap-4 p-4 border rounded-lg">
                        <div className="space-y-3">
                          <Label htmlFor={`trust_stat_${index}_value`}>{t("Value")}</Label>
                          <Input
                            id={`trust_stat_${index}_value`}
                            value={stat.value || ''}
                            onChange={(e) => {
                              const newStats = [...(getSectionData('testimonials').trust_stats || [])];
                              newStats[index] = { ...newStats[index], value: e.target.value };
                              updateSectionData('testimonials', { trust_stats: newStats });
                            }}
                            placeholder="4.9/5"
                          />
                        </div>
                        <div className="space-y-3">
                          <Label htmlFor={`trust_stat_${index}_label`}>{t("Label")}</Label>
                          <Input
                            id={`trust_stat_${index}_label`}
                            value={stat.label || ''}
                            onChange={(e) => {
                              const newStats = [...(getSectionData('testimonials').trust_stats || [])];
                              newStats[index] = { ...newStats[index], label: e.target.value };
                              updateSectionData('testimonials', { trust_stats: newStats });
                            }}
                            placeholder={t("Average Rating")}
                          />
                        </div>
                        <div className="space-y-3">
                          <Label htmlFor={`trust_stat_${index}_color`}>{t("Color")}</Label>
                          <div className="flex gap-2">
                            <select
                              id={`trust_stat_${index}_color`}
                              value={stat.color || 'blue'}
                              onChange={(e) => {
                                const newStats = [...(getSectionData('testimonials').trust_stats || [])];
                                newStats[index] = { ...newStats[index], color: e.target.value };
                                updateSectionData('testimonials', { trust_stats: newStats });
                              }}
                              className="w-full p-2 border border-gray-300 rounded-md cursor-pointer"
                            >
                              <option value="blue">Blue</option>
                              <option value="green">Green</option>
                              <option value="purple">Purple</option>
                              <option value="orange">Orange</option>
                              <option value="red">Red</option>
                            </select>
                            <Button
                              type="button"
                              variant="outline"
                              size="sm"
                              className="text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200"
                              onClick={() => {
                                const newStats = (getSectionData('testimonials').trust_stats || []).filter((_, i) => i !== index);
                                updateSectionData('testimonials', { trust_stats: newStats });
                              }}
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </div>
                      </div>
                    ))}

                    <Button
                      type="button"
                      variant="outline"
                      className="w-full border-2"
                      style={{ color: brandColor, borderColor: brandColor }}
                      onClick={() => {
                        const newStats = [...(getSectionData('testimonials').trust_stats || []), { value: '', label: '', color: 'blue' }];
                        updateSectionData('testimonials', { trust_stats: newStats });
                      }}
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      {t('Add Trust Statistic')}
                    </Button>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-lime-100 rounded-lg">
                      <Users className="h-5 w-5 text-lime-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Default Testimonials')}</h3>
                      <p className="text-sm text-gray-500">{t('Customer reviews and feedback')}</p>
                    </div>
                  </div>

                  <div className="space-y-4">
                    {(getSectionData('testimonials').testimonials || []).map((testimonial, index) => (
                      <div key={index} className="bg-gray-50 border border-gray-200 rounded-xl p-5">
                        <div className="flex items-center justify-between mb-4">
                          <h4 className="font-semibold text-gray-900 flex items-center gap-2">
                            <span className="w-6 h-6 bg-lime-100 text-lime-600 rounded-full flex items-center justify-center text-xs font-bold">{index + 1}</span>
                            {t('Testimonial')} {index + 1}
                          </h4>
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200"
                            onClick={() => {
                              const newTestimonials = (getSectionData('testimonials').testimonials || []).filter((_, i) => i !== index);
                              updateSectionData('testimonials', { testimonials: newTestimonials });
                            }}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <div className="space-y-3">
                            <Label htmlFor={`testimonial_${index}_name`}>{t("Name")}</Label>
                            <Input
                              id={`testimonial_${index}_name`}
                              value={testimonial.name || ''}
                              onChange={(e) => {
                                const newTestimonials = [...(getSectionData('testimonials').testimonials || [])];
                                newTestimonials[index] = { ...newTestimonials[index], name: e.target.value };
                                updateSectionData('testimonials', { testimonials: newTestimonials });
                              }}
                              placeholder={t("John Doe")}
                            />
                          </div>

                          <div className="space-y-3">
                            <Label htmlFor={`testimonial_${index}_role`}>{t("Role")}</Label>
                            <Input
                              id={`testimonial_${index}_role`}
                              value={testimonial.role || ''}
                              onChange={(e) => {
                                const newTestimonials = [...(getSectionData('testimonials').testimonials || [])];
                                newTestimonials[index] = { ...newTestimonials[index], role: e.target.value };
                                updateSectionData('testimonials', { testimonials: newTestimonials });
                              }}
                              placeholder={t("CEO")}
                            />
                          </div>

                          <div className="space-y-3">
                            <Label htmlFor={`testimonial_${index}_company`}>{t("Company")}</Label>
                            <Input
                              id={`testimonial_${index}_company`}
                              value={testimonial.company || ''}
                              onChange={(e) => {
                                const newTestimonials = [...(getSectionData('testimonials').testimonials || [])];
                                newTestimonials[index] = { ...newTestimonials[index], company: e.target.value };
                                updateSectionData('testimonials', { testimonials: newTestimonials });
                              }}
                              placeholder={t("Company Name")}
                            />
                          </div>

                          <div className="space-y-3">
                            <Label htmlFor={`testimonial_${index}_rating`}>{t('Rating')}</Label>
                            <select
                              id={`testimonial_${index}_rating`}
                              value={testimonial.rating || 5}
                              onChange={(e) => {
                                const newTestimonials = [...(getSectionData('testimonials').testimonials || [])];
                                newTestimonials[index] = { ...newTestimonials[index], rating: parseInt(e.target.value) };
                                updateSectionData('testimonials', { testimonials: newTestimonials });
                              }}
                              className="w-full p-2 border border-gray-300 rounded-md cursor-pointer"
                            >
                              <option value={1}>{t('1 Star')}</option>
                              <option value={2}>{t('2 Stars')}</option>
                              <option value={3}>{t('3 Stars')}</option>
                              <option value={4}>{t('4 Stars')}</option>
                              <option value={5}>{t('5 Stars')}</option>
                            </select>
                          </div>

                          <div className="space-y-3 md:col-span-2">
                            <Label htmlFor={`testimonial_${index}_content`}>{t('Content')}</Label>
                            <Textarea
                              id={`testimonial_${index}_content`}
                              value={testimonial.content || ''}
                              onChange={(e) => {
                                const newTestimonials = [...(getSectionData('testimonials').testimonials || [])];
                                newTestimonials[index] = { ...newTestimonials[index], content: e.target.value };
                                updateSectionData('testimonials', { testimonials: newTestimonials });
                              }}
                              placeholder={t("Testimonial content...")}
                              rows={3}
                            />
                          </div>
                        </div>
                      </div>
                    ))}

                    <Button
                      type="button"
                      variant="outline"
                      className="w-full border-2"
                      style={{ color: brandColor, borderColor: brandColor }}
                      onClick={() => {
                        const newTestimonials = [...(getSectionData('testimonials').testimonials || []), { name: '', role: '', company: '', content: '', rating: 5 }];
                        updateSectionData('testimonials', { testimonials: newTestimonials });
                      }}
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      {t('Add Default Testimonial')}
                    </Button>
                  </div>
                </div>
              </div>
            )}

            {/* Plans Section */}
            {activeSection === 'plans' && (
              <div className="space-y-6">
                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                      <div className="p-2 bg-violet-100 rounded-lg">
                        <Type className="h-5 w-5 text-violet-600" />
                      </div>
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900">{t('Plans Section Content')}</h3>
                        <p className="text-sm text-gray-500">{t('Pricing section title and description')}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Label className="text-sm">{t('Enable Section')}</Label>
                      <Switch
                        checked={data.config_sections?.section_visibility?.plans !== false}
                        onCheckedChange={(checked) => updateSectionVisibility('plans', checked)}
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="plans_title">{t("Section Title")}</Label>
                      <Input
                        id="plans_title"
                        value={getSectionData('plans').title || ''}
                        onChange={(e) => updateSectionData('plans', { title: e.target.value })}
                        placeholder={t("Choose Your Plan")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="plans_subtitle">{t("Section Subtitle")}</Label>
                      <Textarea
                        id="plans_subtitle"
                        value={getSectionData('plans').subtitle || ''}
                        onChange={(e) => updateSectionData('plans', { subtitle: e.target.value })}
                        placeholder={t("Start with our free plan and upgrade as you grow...")}
                        rows={3}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="plans_faq_text">{t('FAQ Text')}</Label>
                      <Input
                        id="plans_faq_text"
                        value={getSectionData('plans').faq_text || ''}
                        onChange={(e) => updateSectionData('plans', { faq_text: e.target.value })}
                        placeholder={t("Have questions about our plans? Reach out to our sales team for guidance.")}
                      />
                    </div>
                  </div>
                </div>

                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                  <div className="flex items-start">
                    <Info className="h-5 w-5 text-blue-600 mr-2 mt-0.5" />
                    <div>
                      <h4 className="text-sm font-medium mb-1" style={{ color: brandColor }}>{t('Plans Management')}</h4>
                      <p className="text-sm" style={{ color: brandColor + 'cc' }}>
                        {t('The actual plans displayed on the landing page are managed through the Plans module. Go to Plans section to create, edit, or manage your subscription plans.')}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* FAQ Section */}
            {activeSection === 'faq' && (
              <div className="space-y-6">
                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                      <div className="p-2 bg-sky-100 rounded-lg">
                        <HelpCircle className="h-5 w-5 text-sky-600" />
                      </div>
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900">{t('FAQ Section Content')}</h3>
                        <p className="text-sm text-gray-500">{t('Section title, subtitle and CTA')}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Label className="text-sm">{t('Enable Section')}</Label>
                      <Switch
                        checked={data.config_sections?.section_visibility?.faq !== false}
                        onCheckedChange={(checked) => updateSectionVisibility('faq', checked)}
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="faq_title">{t("Section Title")}</Label>
                      <Input
                        id="faq_title"
                        value={getSectionData('faq').title || ''}
                        onChange={(e) => updateSectionData('faq', { title: e.target.value })}
                        placeholder={t("Frequently Asked Questions")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="faq_subtitle">{t("Section Subtitle")}</Label>
                      <Textarea
                        id="faq_subtitle"
                        value={getSectionData('faq').subtitle || ''}
                        onChange={(e) => updateSectionData('faq', { subtitle: e.target.value })}
                        placeholder={t("Got questions? We've got answers...")}
                        rows={3}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="faq_cta_text">{t('CTA Text')}</Label>
                      <Input
                        id="faq_cta_text"
                        value={getSectionData('faq').cta_text || ''}
                        onChange={(e) => updateSectionData('faq', { cta_text: e.target.value })}
                        placeholder={t("Still have questions?")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="faq_button_text">{t("Button Text")}</Label>
                      <Input
                        id="faq_button_text"
                        value={getSectionData('faq').button_text || ''}
                        onChange={(e) => updateSectionData('faq', { button_text: e.target.value })}
                        placeholder={t("Contact Support")}
                      />
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-slate-100 rounded-lg">
                      <HelpCircle className="h-5 w-5 text-slate-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Default FAQs')}</h3>
                      <p className="text-sm text-gray-500">{t('Frequently asked questions and answers')}</p>
                    </div>
                  </div>

                  <div className="space-y-4">
                    {(getSectionData('faq').faqs || []).map((faq, index) => (
                      <div key={index} className="bg-gray-50 border border-gray-200 rounded-xl p-5">
                        <div className="flex items-center justify-between mb-4">
                          <h4 className="font-semibold text-gray-900 flex items-center gap-2">
                            <span className="w-6 h-6 bg-slate-100 text-slate-600 rounded-full flex items-center justify-center text-xs font-bold">{index + 1}</span>
                            {t('FAQ')} {index + 1}
                          </h4>
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200"
                            onClick={() => {
                              const newFaqs = (getSectionData('faq').faqs || []).filter((_, i) => i !== index);
                              updateSectionData('faq', { faqs: newFaqs });
                            }}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>

                        <div className="space-y-4">
                          <div className="space-y-3">
                            <Label htmlFor={`faq_${index}_question`}>{t('Question')}</Label>
                            <Input
                              id={`faq_${index}_question`}
                              value={faq.question || ''}
                              onChange={(e) => {
                                const newFaqs = [...(getSectionData('faq').faqs || [])];
                                newFaqs[index] = { ...newFaqs[index], question: e.target.value };
                                updateSectionData('faq', { faqs: newFaqs });
                              }}
                              placeholder={t("How does advocate work?")}
                            />
                          </div>

                          <div className="space-y-3">
                            <Label htmlFor={`faq_${index}_answer`}>{t('Answer')}</Label>
                            <Textarea
                              id={`faq_${index}_answer`}
                              value={faq.answer || ''}
                              onChange={(e) => {
                                const newFaqs = [...(getSectionData('faq').faqs || [])];
                                newFaqs[index] = { ...newFaqs[index], answer: e.target.value };
                                updateSectionData('faq', { faqs: newFaqs });
                              }}
                              placeholder={t("advocate allows you to manage law firm...")}
                              rows={3}
                            />
                          </div>
                        </div>
                      </div>
                    ))}

                    <Button
                      type="button"
                      variant="outline"
                      className="w-full border-2"
                      style={{ color: brandColor, borderColor: brandColor }}
                      onClick={() => {
                        const newFaqs = [...(getSectionData('faq').faqs || []), { question: '', answer: '' }];
                        updateSectionData('faq', { faqs: newFaqs });
                      }}
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      {t('Add FAQ')}
                    </Button>
                  </div>
                </div>
              </div>
            )}

            {/* Newsletter Section */}
            {activeSection === 'newsletter' && (
              <div className="space-y-6">
                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                      <div className="p-2 bg-orange-100 rounded-lg">
                        <Type className="h-5 w-5 text-orange-600" />
                      </div>
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900">{t('Newsletter Content')}</h3>
                        <p className="text-sm text-gray-500">{t('Newsletter section title and description')}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Label className="text-sm">{t('Enable Section')}</Label>
                      <Switch
                        checked={data.config_sections?.section_visibility?.newsletter !== false}
                        onCheckedChange={(checked) => updateSectionVisibility('newsletter', checked)}
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="newsletter_title">{t("Section Title")}</Label>
                      <Input
                        id="newsletter_title"
                        value={getSectionData('newsletter').title || ''}
                        onChange={(e) => updateSectionData('newsletter', { title: e.target.value })}
                        placeholder={t("Stay Updated with advocate")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="newsletter_subtitle">{t("Section Subtitle")}</Label>
                      <Textarea
                        id="newsletter_subtitle"
                        value={getSectionData('newsletter').subtitle || ''}
                        onChange={(e) => updateSectionData('newsletter', { subtitle: e.target.value })}
                        placeholder={t("Get the latest updates, networking tips...")}
                        rows={3}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="newsletter_privacy_text">{t('Privacy Text')}</Label>
                      <Input
                        id="newsletter_privacy_text"
                        value={getSectionData('newsletter').privacy_text || ''}
                        onChange={(e) => updateSectionData('newsletter', { privacy_text: e.target.value })}
                        placeholder={t("No spam, unsubscribe at any time...")}
                      />
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-red-100 rounded-lg">
                      <Award className="h-5 w-5 text-red-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Newsletter Benefits')}</h3>
                      <p className="text-sm text-gray-500">{t('Benefits of subscribing to newsletter')}</p>
                    </div>
                  </div>

                  <div className="space-y-4">
                    {(getSectionData('newsletter').benefits || []).map((benefit, index) => (
                      <div key={index} className="bg-gray-50 border border-gray-200 rounded-xl p-5">
                        <div className="flex items-center justify-between mb-4">
                          <h4 className="font-semibold text-gray-900 flex items-center gap-2">
                            <span className="w-6 h-6 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-xs font-bold">{index + 1}</span>
                            {t('Benefit')} {index + 1}
                          </h4>
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200"
                            onClick={() => {
                              const newBenefits = (getSectionData('newsletter').benefits || []).filter((_, i) => i !== index);
                              updateSectionData('newsletter', { benefits: newBenefits });
                            }}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                          <div className="space-y-3">
                            <Label htmlFor={`benefit_${index}_icon`}>{t('Icon (Emoji)')}</Label>
                            <Input
                              id={`benefit_${index}_icon`}
                              value={benefit.icon || ''}
                              onChange={(e) => {
                                const newBenefits = [...(getSectionData('newsletter').benefits || [])];
                                newBenefits[index] = { ...newBenefits[index], icon: e.target.value };
                                updateSectionData('newsletter', { benefits: newBenefits });
                              }}
                              placeholder="📧"
                              maxLength={2}
                            />
                          </div>

                          <div className="space-y-3">
                            <Label htmlFor={`benefit_${index}_title`}>Title</Label>
                            <Input
                              id={`benefit_${index}_title`}
                              value={benefit.title || ''}
                              onChange={(e) => {
                                const newBenefits = [...(getSectionData('newsletter').benefits || [])];
                                newBenefits[index] = { ...newBenefits[index], title: e.target.value };
                                updateSectionData('newsletter', { benefits: newBenefits });
                              }}
                              placeholder={t("Weekly Updates")}
                            />
                          </div>

                          <div className="space-y-3">
                            <Label htmlFor={`benefit_${index}_description`}>{t("Description")}</Label>
                            <Input
                              id={`benefit_${index}_description`}
                              value={benefit.description || ''}
                              onChange={(e) => {
                                const newBenefits = [...(getSectionData('newsletter').benefits || [])];
                                newBenefits[index] = { ...newBenefits[index], description: e.target.value };
                                updateSectionData('newsletter', { benefits: newBenefits });
                              }}
                              placeholder={t("Stay informed about the latest Advocate SaaS features and improvements.")}
                            />
                          </div>
                        </div>
                      </div>
                    ))}

                    <Button
                      type="button"
                      variant="outline"
                      className="w-full border-2"
                      style={{ color: brandColor, borderColor: brandColor }}
                      onClick={() => {
                        const newBenefits = [...(getSectionData('newsletter').benefits || []), { icon: '', title: '', description: '' }];
                        updateSectionData('newsletter', { benefits: newBenefits });
                      }}
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      {t('Add Benefit')}
                    </Button>
                  </div>
                </div>
              </div>
            )}

            {/* Contact Section */}
            {activeSection === 'contact' && (
              <div className="space-y-6">
                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                      <div className="p-2 bg-teal-100 rounded-lg">
                        <Type className="h-5 w-5 text-teal-600" />
                      </div>
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900">{t('Contact Section Content')}</h3>
                        <p className="text-sm text-gray-500">{t('Contact section titles and descriptions')}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Label className="text-sm">{t('Enable Section')}</Label>
                      <Switch
                        checked={data.config_sections?.section_visibility?.contact !== false}
                        onCheckedChange={(checked) => updateSectionVisibility('contact', checked)}
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="contact_title">{t("Section Title")}</Label>
                      <Input
                        id="contact_title"
                        value={getSectionData('contact').title || ''}
                        onChange={(e) => updateSectionData('contact', { title: e.target.value })}
                        placeholder={t("Get in Touch")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="contact_subtitle">{t("Section Subtitle")}</Label>
                      <Textarea
                        id="contact_subtitle"
                        value={getSectionData('contact').subtitle || ''}
                        onChange={(e) => updateSectionData('contact', { subtitle: e.target.value })}
                        placeholder={t("Have questions about Advocate SaaS ?...")}
                        rows={3}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="contact_form_title">{t('Form Title')}</Label>
                      <Input
                        id="contact_form_title"
                        value={getSectionData('contact').form_title || ''}
                        onChange={(e) => updateSectionData('contact', { form_title: e.target.value })}
                        placeholder={t("Send us a Message")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="contact_info_title">{t('Contact Info Title')}</Label>
                      <Input
                        id="contact_info_title"
                        value={getSectionData('contact').info_title || ''}
                        onChange={(e) => updateSectionData('contact', { info_title: e.target.value })}
                        placeholder={t("Contact Information")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="contact_info_description">{t('Contact Info Description')}</Label>
                      <Textarea
                        id="contact_info_description"
                        value={getSectionData('contact').info_description || ''}
                        onChange={(e) => updateSectionData('contact', { info_description: e.target.value })}
                        placeholder={t("We're here to help and answer any question...")}
                        rows={3}
                      />
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-emerald-100 rounded-lg">
                      <Phone className="h-5 w-5 text-emerald-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Contact Information')}</h3>
                      <p className="text-sm text-gray-500">{t('Company contact details')}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="contact_email">{t('Email Address')}</Label>
                      <Input
                        id="contact_email"
                        name="contact_email"
                        value={data.contact_email || ''}
                        onChange={handleInputChange}
                        placeholder="support@advocate.com"
                        type="email"
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="contact_phone">{t('Phone Number')}</Label>
                      <Input
                        id="contact_phone"
                        name="contact_phone"
                        value={data.contact_phone || ''}
                        onChange={handleInputChange}
                        placeholder={t("+1 (555) 123-4567")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="contact_address">{t('Address')}</Label>
                      <Textarea
                        id="contact_address"
                        name="contact_address"
                        value={data.contact_address || ''}
                        onChange={handleInputChange}
                        placeholder={t("123 Business Ave, Suite 100, San Francisco, CA 94105")}
                        rows={2}
                      />
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-indigo-100 rounded-lg">
                      <FileText className="h-5 w-5 text-indigo-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Contact FAQs')}</h3>
                      <p className="text-sm text-gray-500">{t('Contact-related frequently asked questions')}</p>
                    </div>
                  </div>

                  <div className="space-y-4">
                    {(getSectionData('contact').faqs || []).map((faq, index) => (
                      <div key={index} className="p-4 border rounded-lg space-y-4">
                        <div className="flex items-center justify-between">
                          <h4 className="font-medium">{t("FAQ")} {index + 1}</h4>
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200"
                            onClick={() => {
                              const newFaqs = (getSectionData('contact').faqs || []).filter((_, i) => i !== index);
                              updateSectionData('contact', { faqs: newFaqs });
                            }}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>

                        <div className="space-y-4">
                          <div className="space-y-3">
                            <Label htmlFor={`contact_faq_${index}_question`}>Question</Label>
                            <Input
                              id={`contact_faq_${index}_question`}
                              value={faq.question || ''}
                              onChange={(e) => {
                                const newFaqs = [...(getSectionData('contact').faqs || [])];
                                newFaqs[index] = { ...newFaqs[index], question: e.target.value };
                                updateSectionData('contact', { faqs: newFaqs });
                              }}
                              placeholder={t("How quickly do you respond?")}
                            />
                          </div>

                          <div className="space-y-3">
                            <Label htmlFor={`contact_faq_${index}_answer`}>Answer</Label>
                            <Textarea
                              id={`contact_faq_${index}_answer`}
                              value={faq.answer || ''}
                              onChange={(e) => {
                                const newFaqs = [...(getSectionData('contact').faqs || [])];
                                newFaqs[index] = { ...newFaqs[index], answer: e.target.value };
                                updateSectionData('contact', { faqs: newFaqs });
                              }}
                              placeholder={t("We typically respond within 24 hours...")}
                              rows={2}
                            />
                          </div>
                        </div>
                      </div>
                    ))}

                    <Button
                      type="button"
                      variant="outline"
                      className="w-full border-2"
                      style={{ color: brandColor, borderColor: brandColor }}
                      onClick={() => {
                        const newFaqs = [...(getSectionData('contact').faqs || []), { question: '', answer: '' }];
                        updateSectionData('contact', { faqs: newFaqs });
                      }}
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      {t('Add FAQ')}
                    </Button>
                  </div>
                </div>
              </div>
            )}

            {/* Footer Section */}
            {activeSection === 'footer' && (
              <div className="space-y-6">
                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                      <div className="p-2 bg-gray-100 rounded-lg">
                        <Type className="h-5 w-5 text-gray-600" />
                      </div>
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900">{t('Footer Content')}</h3>
                        <p className="text-sm text-gray-500">{t('Footer description and newsletter content')}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Label className="text-sm">{t('Enable Section')}</Label>
                      <Switch
                        checked={data.config_sections?.section_visibility?.footer !== false}
                        onCheckedChange={(checked) => updateSectionVisibility('footer', checked)}
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="footer_description">{t('Company Description')}</Label>
                      <Textarea
                        id="footer_description"
                        value={getSectionData('footer').description || ''}
                        onChange={(e) => updateSectionData('footer', { description: e.target.value })}
                        placeholder={t("Transforming professional networking...")}
                        rows={3}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="footer_newsletter_title">{t('Newsletter Title')}</Label>
                      <Input
                        id="footer_newsletter_title"
                        value={getSectionData('footer').newsletter_title || ''}
                        onChange={(e) => updateSectionData('footer', { newsletter_title: e.target.value })}
                        placeholder={t("Stay Updated with Our Latest Features")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="footer_newsletter_subtitle">{t('Newsletter Subtitle')}</Label>
                      <Input
                        id="footer_newsletter_subtitle"
                        value={getSectionData('footer').newsletter_subtitle || ''}
                        onChange={(e) => updateSectionData('footer', { newsletter_subtitle: e.target.value })}
                        placeholder={t("Join our newsletter for product updates...")}
                      />
                    </div>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-blue-100 rounded-lg">
                      <Globe className="h-5 w-5 text-blue-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Social Links')}</h3>
                      <p className="text-sm text-gray-500">{t('Social media links and profiles')}</p>
                    </div>
                  </div>

                  <div className="space-y-4">
                    {(getSectionData('footer').social_links || []).map((social, index) => (
                      <div key={index} className="bg-gray-50 border border-gray-200 rounded-xl p-5">
                        <div className="flex items-center justify-between mb-4">
                          <h4 className="font-semibold text-gray-900 flex items-center gap-2">
                            <span className="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-bold">{index + 1}</span>
                            {t('Social Link')} {index + 1}
                          </h4>
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200"
                            onClick={() => {
                              const newSocials = (getSectionData('footer').social_links || []).filter((_, i) => i !== index);
                              updateSectionData('footer', { social_links: newSocials });
                            }}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                          <div className="space-y-3">
                            <Label htmlFor={`social_${index}_name`}>{t("Name")}</Label>
                            <Input
                              id={`social_${index}_name`}
                              value={social.name || ''}
                              onChange={(e) => {
                                const newSocials = [...(getSectionData('footer').social_links || [])];
                                newSocials[index] = { ...newSocials[index], name: e.target.value };
                                updateSectionData('footer', { social_links: newSocials });
                              }}
                              placeholder={t("Facebook")}
                            />
                          </div>

                          <div className="space-y-3">
                            <Label htmlFor={`social_${index}_icon`}>{t("Icon")}</Label>
                            <select
                              id={`social_${index}_icon`}
                              value={social.icon || 'Facebook'}
                              onChange={(e) => {
                                const newSocials = [...(getSectionData('footer').social_links || [])];
                                newSocials[index] = { ...newSocials[index], icon: e.target.value };
                                updateSectionData('footer', { social_links: newSocials });
                              }}
                              className="w-full p-2 border border-gray-300 rounded-md cursor-pointer"
                            >
                              <option value="Facebook">Facebook</option>
                              <option value="Twitter">Twitter</option>
                              <option value="Linkedin">LinkedIn</option>
                              <option value="Instagram">Instagram</option>
                            </select>
                          </div>

                          <div className="space-y-3">
                            <Label htmlFor={`social_${index}_href`}>{t("URL")}</Label>
                            <Input
                              id={`social_${index}_href`}
                              value={social.href || ''}
                              onChange={(e) => {
                                const newSocials = [...(getSectionData('footer').social_links || [])];
                                newSocials[index] = { ...newSocials[index], href: e.target.value };
                                updateSectionData('footer', { social_links: newSocials });
                              }}
                              placeholder="https://facebook.com/..."
                            />
                          </div>
                        </div>
                      </div>
                    ))}

                    <Button
                      type="button"
                      variant="outline"
                      className="w-full border-2"
                      style={{ color: brandColor, borderColor: brandColor }}
                      onClick={() => {
                        const newSocials = [...(getSectionData('footer').social_links || []), { name: '', icon: 'Facebook', href: '' }];
                        updateSectionData('footer', { social_links: newSocials });
                      }}
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      {t('Add Social Link')}
                    </Button>
                  </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-purple-100 rounded-lg">
                      <FileText className="h-5 w-5 text-purple-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t("Footer Links")}</h3>
                      <p className="text-sm text-gray-500">{t("Footer navigation links by category")}</p>
                    </div>
                  </div>

                  <div className="space-y-6">
                    {['product', 'company', 'support', 'legal'].map((category) => (
                      <div key={category} className="space-y-4">
                        <div className="space-y-3">
                          <Label htmlFor={`${category}_title`}>{t("Section Title")}</Label>
                          <Input
                            id={`${category}_title`}
                            value={getSectionData('footer').section_titles?.[category] || ''}
                            onChange={(e) => {
                              const newTitles = { ...getSectionData('footer').section_titles };
                              newTitles[category] = e.target.value;
                              updateSectionData('footer', { section_titles: newTitles });
                            }}
                            placeholder={category.charAt(0).toUpperCase() + category.slice(1)}
                          />
                        </div>
                        <h4 className="font-medium">{getSectionData('footer').section_titles?.[category] || category.charAt(0).toUpperCase() + category.slice(1)} Links</h4>
                        {(getSectionData('footer').links?.[category] || []).map((link: any, index: number) => (
                          <div key={index} className="grid grid-cols-2 gap-4 p-4 border rounded-lg">
                            <div className="space-y-3">
                              <Label htmlFor={`${category}_${index}_name`}>{t("Name")}</Label>
                              <Input
                                id={`${category}_${index}_name`}
                                value={link.name || ''}
                                onChange={(e) => {
                                  const newLinks = { ...getSectionData('footer').links };
                                  if (!newLinks[category]) newLinks[category] = [];
                                  newLinks[category][index] = { ...newLinks[category][index], name: e.target.value };
                                  updateSectionData('footer', { links: newLinks });
                                }}
                                placeholder={t("Features")}
                              />
                            </div>
                            <div className="space-y-3">
                              <Label htmlFor={`${category}_${index}_href`}>{t("URL")}</Label>
                              <div className="flex gap-2">
                                <Input
                                  id={`${category}_${index}_href`}
                                  value={link.href || ''}
                                  onChange={(e) => {
                                    const newLinks = { ...getSectionData('footer').links };
                                    if (!newLinks[category]) newLinks[category] = [];
                                    newLinks[category][index] = { ...newLinks[category][index], href: e.target.value };
                                    updateSectionData('footer', { links: newLinks });
                                  }}
                                  placeholder="#features"
                                />
                                <Button
                                  type="button"
                                  variant="outline"
                                  size="sm"
                                  className="text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200"
                                  onClick={() => {
                                    const newLinks = { ...getSectionData('footer').links };
                                    if (newLinks[category]) {
                                      newLinks[category] = newLinks[category].filter((_: any, i: number) => i !== index);
                                      updateSectionData('footer', { links: newLinks });
                                    }
                                  }}
                                >
                                  <Trash2 className="h-4 w-4" />
                                </Button>
                              </div>
                            </div>
                          </div>
                        ))}
                        <Button
                          type="button"
                          variant="outline"
                          size="sm"
                          className="border-2"
                          style={{ color: brandColor, borderColor: brandColor }}
                          onClick={() => {
                            const newLinks = { ...getSectionData('footer').links };
                            if (!newLinks[category]) newLinks[category] = [];
                            newLinks[category].push({ name: '', href: '' });
                            updateSectionData('footer', { links: newLinks });
                          }}
                        >
                          <Plus className="h-4 w-4 mr-2" />
                          {t("Add")} {getSectionData('footer').section_titles?.[category] || category.charAt(0).toUpperCase() + category.slice(1)} {t("Link")}
                        </Button>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            )}

            {/* Design Section */}
            {activeSection === 'design' && (
              <div className="space-y-6">
                <div className="space-y-4">
                  <div className="flex items-center">
                    <Palette className="h-5 w-5 mr-2 text-muted-foreground" />
                    <h3 className="text-base font-medium">{t("Colors & Theme")}</h3>
                  </div>
                  <Separator className="my-2" />

                  <div className="grid grid-cols-3 gap-4">
                    <div className="space-y-3">
                      <Label htmlFor="primary_color">{t("Primary Color")}</Label>
                      <div className="flex gap-2">
                        <Input
                          id="primary_color"
                          type="color"
                          value={data.config_sections?.theme?.primary_color || '#3b82f6'}
                          onChange={(e) => updateThemeData({ primary_color: e.target.value })}
                          className="w-16 h-10 p-1 cursor-pointer"
                        />
                        <Input
                          value={data.config_sections?.theme?.primary_color || '#3b82f6'}
                          onChange={(e) => updateThemeData({ primary_color: e.target.value })}
                          placeholder="#3b82f6"
                        />
                      </div>
                    </div>
                    <div className="space-y-3">
                      <Label htmlFor="secondary_color">{t("Secondary Color")}</Label>
                      <div className="flex gap-2">
                        <Input
                          id="secondary_color"
                          type="color"
                          value={data.config_sections?.theme?.secondary_color || '#8b5cf6'}
                          onChange={(e) => updateThemeData({ secondary_color: e.target.value })}
                          className="w-16 h-10 p-1 cursor-pointer"
                        />
                        <Input
                          value={data.config_sections?.theme?.secondary_color || '#8b5cf6'}
                          onChange={(e) => updateThemeData({ secondary_color: e.target.value })}
                          placeholder="#8b5cf6"
                        />
                      </div>
                    </div>
                    <div className="space-y-3">
                      <Label htmlFor="accent_color">{t("Accent Color")}</Label>
                      <div className="flex gap-2">
                        <Input
                          id="accent_color"
                          type="color"
                          value={data.config_sections?.theme?.accent_color || '#10b77f'}
                          onChange={(e) => updateThemeData({ accent_color: e.target.value })}
                          className="w-16 h-10 p-1 cursor-pointer"
                        />
                        <Input
                          value={data.config_sections?.theme?.accent_color || '#10b77f'}
                          onChange={(e) => updateThemeData({ accent_color: e.target.value })}
                          placeholder="#10b77f"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <div className="space-y-4">
                  <div className="flex items-center">
                    <Image className="h-5 w-5 mr-2 text-muted-foreground" />
                    <h3 className="text-base font-medium">{t("Images & Logos")}</h3>
                  </div>
                  <Separator className="my-2" />

                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-3">
                      <MediaPicker
                        label={t("Logo (Light)")}
                        value={getDisplayUrl(data.config_sections?.theme?.logo_light || '')}
                        onChange={(value) => {
                          updateThemeData({ logo_light: convertToRelativePath(value) });
                        }}
                        placeholder={t("Select light logo...")}
                      />
                    </div>
                    <div className="space-y-3">
                      <MediaPicker
                        label={t("Logo (Dark)")}
                        value={getDisplayUrl(data.config_sections?.theme?.logo_dark || '')}
                        onChange={(value) => {
                          updateThemeData({ logo_dark: convertToRelativePath(value) });
                        }}
                        placeholder={t("Select dark logo...")}
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Change Order Section */}
            {activeSection === 'order' && (
              <div className="space-y-6">
                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-indigo-100 rounded-lg">
                      <ArrowUpDown className="h-5 w-5 text-indigo-600" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900">{t('Section Order')}</h3>
                      <p className="text-sm text-gray-500">{t('Drag and drop to reorder sections on your landing page')}</p>
                    </div>
                  </div>

                  <div className="space-y-3">
                    {(data.config_sections?.section_order || []).map((sectionKey, index) => {
                      const sectionNames = {
                        header: t('Header'),
                        hero: t('Hero'),
                        features: t('Features'),
                        screenshots: t('Screenshots'),
                        why_choose_us: t('Why Choose Us'),
                        about: t('About'),
                        team: t('Team'),
                        testimonials: t('Testimonials'),
                        plans: t('Plans'),
                        faq: t('FAQ'),
                        newsletter: t('Newsletter'),
                        contact: t('Contact'),
                        footer: t('Footer')
                      };

                      const isEnabled = data.config_sections?.section_visibility?.[sectionKey] !== false;

                      return (
                        <div
                          key={sectionKey}
                          draggable
                          onDragStart={(e) => handleDragStart(e, index)}
                          onDragOver={handleDragOver}
                          onDrop={(e) => handleDrop(e, index)}
                          className={`flex items-center gap-3 p-4 border rounded-lg cursor-move transition-all hover:shadow-md ${
                            isEnabled ? 'bg-white border-gray-200' : 'bg-gray-50 border-gray-300 opacity-60'
                          }`}
                        >
                          <GripVertical className="h-5 w-5 text-gray-400" />
                          <div className="flex-1 flex items-center justify-between">
                            <div className="flex items-center gap-3">
                              <span className="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-bold">
                                {index + 1}
                              </span>
                              <div>
                                <h4 className="font-medium text-gray-900">{sectionNames[sectionKey] || sectionKey}</h4>
                                <p className="text-sm text-gray-500">
                                  {isEnabled ? t('Enabled') : t('Disabled')}
                                </p>
                              </div>
                            </div>
                            <div className="flex items-center gap-2">
                              <Label className="text-sm">{t('Enable')}</Label>
                              <Switch
                                checked={isEnabled}
                                onCheckedChange={(checked) => updateSectionVisibility(sectionKey, checked)}
                              />
                            </div>
                          </div>
                        </div>
                      );
                    })}
                  </div>

                  <div className="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div className="flex items-start">
                      <Info className="h-5 w-5 text-blue-600 mr-2 mt-0.5" />
                      <div>
                        <h4 className="text-sm font-medium text-blue-900 mb-1">{t('How to reorder')}</h4>
                        <p className="text-sm text-blue-700">
                          {t('Click and drag any section to change its position. Disabled sections will still appear in the order but won\'t be visible on the landing page.')}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Advanced Section */}
            {activeSection === 'advanced' && (
              <div className="space-y-6">
                <div className="space-y-4">
                  <div className="flex items-center">
                    <Search className="h-5 w-5 mr-2 text-muted-foreground" />
                    <h3 className="text-base font-medium">{t("SEO Settings")}</h3>
                  </div>
                  <Separator className="my-2" />

                  <div className="space-y-4">
                    <div className="space-y-3">
                      <Label htmlFor="meta_title">{t("Meta Title")}</Label>
                      <Input
                        id="meta_title"
                        value={data.config_sections?.seo?.meta_title || ''}
                        onChange={(e) => updateSeoData({ meta_title: e.target.value })}
                        placeholder={t("Landing Page Title")}
                      />
                    </div>

                    <div className="space-y-3">
                      <Label htmlFor="meta_description">{t("Meta Description")}</Label>
                      <Textarea
                        id="meta_description"
                        value={data.config_sections?.seo?.meta_description || ''}
                        onChange={(e) => updateSeoData({ meta_description: e.target.value })}
                        placeholder={t("Landing page description for search engines")}
                        rows={3}
                      />
                    </div>
                  </div>
                </div>

                <div className="space-y-4">
                  <div className="flex items-center">
                    <Code className="h-5 w-5 mr-2 text-muted-foreground" />
                    <h3 className="text-base font-medium">{t("Custom CSS")}</h3>
                  </div>
                  <Separator className="my-2" />

                  <div className="space-y-3">
                    <Label htmlFor="custom_css">{t("Custom CSS")}</Label>
                    <Textarea
                      id="custom_css"
                      value={data.config_sections?.custom_css || ''}
                      onChange={(e) => setData('config_sections', { ...data.config_sections, custom_css: e.target.value })}
                      placeholder={t("Add your custom CSS here")}
                      rows={6}
                      className="font-mono text-sm"
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('Add custom CSS to override default styles')}
                    </p>
                  </div>
                </div>

                <div className="space-y-4">
                  <div className="flex items-center">
                    <Code className="h-5 w-5 mr-2 text-muted-foreground" />
                    <h3 className="text-base font-medium">{t('Custom JavaScript')}</h3>
                  </div>
                  <Separator className="my-2" />

                  <div className="space-y-3">
                    <Label htmlFor="custom_js">{t('Custom JavaScript')}</Label>
                    <Textarea
                      id="custom_js"
                      value={data.config_sections?.custom_js || ''}
                      onChange={(e) => setData('config_sections', { ...data.config_sections, custom_js: e.target.value })}
                      placeholder={t("Add your custom JavaScript here")}
                      rows={6}
                      className="font-mono text-sm"
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('Add custom JavaScript for advanced functionality')}
                    </p>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      </SettingsSection>
      <Toaster />
    </PageTemplate>
  );
}
