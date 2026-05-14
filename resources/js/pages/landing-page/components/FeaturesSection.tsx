import React from 'react';
import { QrCode, Smartphone, Share2, BarChart3, Globe, Shield, Star, Zap, Users, Lock, Wifi, Heart, TrendingUp } from 'lucide-react';
import { useScrollAnimation } from '../../../hooks/useScrollAnimation';
import { useTranslation } from 'react-i18next';

interface Feature {
    title: string;
    description: string;
    icon: string;
}

interface FeaturesSectionProps {
    brandColor?: string;
    settings: any;
    sectionData: {
        title?: string;
        description?: string;
        features_list?: Feature[];
        layout?: string;
        columns?: number;
        background_color?: string;
        image?: string;
        show_icons?: boolean;
    };
}

// Icon mapping for dynamic icons
const iconMap: Record<string, React.ComponentType<any>> = {
    'qr-code': QrCode,
    'smartphone': Smartphone,
    'share': Share2,
    'bar-chart': BarChart3,
    'globe': Globe,
    'shield': Shield,
    'star': Star,
    'zap': Zap,
    'users': Users,
    'lock': Lock,
    'wifi': Wifi,
    'heart': Heart,
    'trending-up': TrendingUp
};

export default function FeaturesSection({ settings, sectionData, brandColor = '#3b82f6' }: FeaturesSectionProps) {
    const { ref, isVisible } = useScrollAnimation();
    const { t } = useTranslation();

    const getImageUrl = (path: string) => {
        if (!path) return null;
        if (path.startsWith('http')) return path;
        return `${window.appSettings.imageUrl}${path}`;
    };

    const sectionImage = getImageUrl(sectionData.image);
    const backgroundColor = sectionData.background_color || '#f9fafb';
    const columns = sectionData.columns || 3;
    const layout = sectionData.layout || 'grid';

    const defaultFeatures = [
        {
            icon: 'users',
            title: t('CRM & Lead Management'),
            description: t('Capture, nurture, and convert leads with smart automation tools. Perfect for business cards, flyers, and networking events.')
        },
        {
            icon: 'zap',
            title: t('Opportunity & Pipeline'),
            description: t('Track every deal stage and source to close more sales.')
        },
        {
            icon: 'globe',
            title: t('Quotes & Orders'),
            description: t('Create professional quotes, manage sales and purchase orders with ease.')
        },
        {
            icon: 'smartphone',
            title: t('Invoices & Payments'),
            description: t('Automate invoices, track payments, and simplify your billing process.')
        },
        {
            icon: 'star',
            title: t('Projects & Tasks'),
            description: t('Collaborate on projects, assign tasks, and deliver work on time.')
        },
        {
            icon: 'bar-chart',
            title: t('Reports & Analytics'),
            description: t('Gain deep insights with customizable reports and dashboards.')
        }
    ];

    const features = sectionData.features_list && sectionData.features_list.length > 0
        ? sectionData.features_list
        : defaultFeatures;

    // Render based on layout
    const renderFeatures = () => {
        if (layout === 'list') {
            return (
                <div className="space-y-6">
                    {features.map((feature, index) => {
                        const IconComponent = iconMap[feature.icon] || QrCode;
                        return (
                            <div key={index} className="flex gap-6 bg-white p-6 rounded-xl border border-gray-200 hover:shadow-lg transition-all">
                                {sectionData.show_icons && <div className="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style={{ backgroundColor: `${brandColor}15` }}>
                                    <IconComponent className="w-6 h-6" style={{ color: brandColor }} />
                                </div>}
                                <div>
                                    <h3 className="text-xl font-bold text-gray-900 mb-2">{feature.title}</h3>
                                    <p className="text-gray-600 leading-relaxed">{feature.description}</p>
                                </div>
                            </div>
                        );
                    })}
                </div>
            );
        }

        if (layout === 'cards') {
            return (
                <div className={`grid grid-cols-1 ${columns >= 2 ? 'sm:grid-cols-2' : ''} ${columns >= 3 ? 'lg:grid-cols-3' : ''} ${columns >= 4 ? 'xl:grid-cols-4' : ''} gap-6 sm:gap-8`}>
                    {features.map((feature, index) => {
                        const IconComponent = iconMap[feature.icon] || QrCode;
                        return (
                            <div key={index} className="bg-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-200 border-t-4" style={{ borderTopColor: brandColor }}>
                                {sectionData.show_icons && <div className="w-14 h-14 rounded-full flex items-center justify-center mb-6 mx-auto" style={{ backgroundColor: `${brandColor}15` }}>
                                    <IconComponent className="w-7 h-7" style={{ color: brandColor }} />
                                </div>}
                                <h3 className="text-xl font-bold text-gray-900 mb-4 text-center">{feature.title}</h3>
                                <p className="text-gray-600 leading-relaxed text-center">{feature.description}</p>
                            </div>
                        );
                    })}
                </div>
            );
        }

        if (layout === 'alternating') {
            return (
                <div className="space-y-12">
                    {features.map((feature, index) => {
                        const IconComponent = iconMap[feature.icon] || QrCode;
                        const isEven = index % 2 === 0;
                        return (
                            <div key={index} className={`flex flex-col ${isEven ? 'lg:flex-row' : 'lg:flex-row-reverse'} gap-8 items-center bg-white p-8 rounded-xl border border-gray-200`}>
                                <div className="w-full lg:w-1/3 flex justify-center">
                                    {sectionData.show_icons && <div className="w-24 h-24 rounded-2xl flex items-center justify-center" style={{ backgroundColor: `${brandColor}15` }}>
                                        <IconComponent className="w-12 h-12" style={{ color: brandColor }} />
                                    </div>}
                                </div>
                                <div className="w-full lg:w-2/3">
                                    <h3 className="text-2xl font-bold text-gray-900 mb-4">{feature.title}</h3>
                                    <p className="text-gray-600 leading-relaxed text-lg">{feature.description}</p>
                                </div>
                            </div>
                        );
                    })}
                </div>
            );
        }

        // Default: grid layout
        return (
            <div className={`grid grid-cols-1 ${columns >= 2 ? 'sm:grid-cols-2' : ''} ${columns >= 3 ? 'lg:grid-cols-3' : ''} ${columns >= 4 ? 'xl:grid-cols-4' : ''} gap-6 sm:gap-8`}>
                {features.map((feature, index) => {
                    const IconComponent = iconMap[feature.icon] || QrCode;
                    return (
                        <div key={index} className="bg-white p-8 rounded-xl border border-gray-200 hover:border-gray-300 hover:shadow-lg transition-all duration-200" role="article" aria-labelledby={`feature-${index}-title`}>
                            {sectionData.show_icons && <div className="w-12 h-12 rounded-lg flex items-center justify-center mb-6" style={{ backgroundColor: `${brandColor}15` }} role="img" aria-label={`${feature.title} icon`}>
                                <IconComponent className="w-6 h-6" style={{ color: brandColor }} aria-hidden="true" />
                            </div>}
                            <h3 className="text-xl font-bold text-gray-900 mb-4" id={`feature-${index}-title`}>{feature.title}</h3>
                            <p className="text-gray-600 leading-relaxed">{feature.description}</p>
                        </div>
                    );
                })}
            </div>
        );
    };

    return (
        <section id="features" className="py-12 sm:py-16 lg:py-20" style={{ backgroundColor }} ref={ref}>
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className={`text-center mb-8 sm:mb-12 lg:mb-16 transition-all duration-700 ${isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'}`}>
                    <h2 className="text-3xl md:text-4xl font-bold dark:text-gray-400 mb-4">
                        {sectionData.title || t('Powerful Features to Streamline Your Sales')}
                    </h2>
                    <p className="text-lg text-gray-600 max-w-3xl mx-auto leading-relaxed font-medium">
                        {sectionData.description || t('From lead management to invoicing, get everything you need to manage and grow your sales pipeline in one platform.. Built for professionals who value efficiency and innovation.')}
                    </p>
                </div>

                {sectionImage && (
                    <div className="mb-8 sm:mb-12 text-center">
                        <img src={sectionImage} alt={t('Features')} className="max-w-full h-auto rounded-xl shadow-lg mx-auto" />
                    </div>
                )}

                <div className={`transition-all duration-700 delay-200 ${isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'}`}>
                    {renderFeatures()}
                </div>
            </div>
        </section>
    );
}
