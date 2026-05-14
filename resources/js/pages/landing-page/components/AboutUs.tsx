import React, { useRef, useEffect, useState } from 'react';
import { Target, Heart, Award, Lightbulb, Star, Shield, Users, Zap } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { useScrollAnimation } from '../../../hooks/useScrollAnimation';
import { getDisplayUrl } from '@/utils/helper';

interface AboutUsProps {
  brandColor?: string;
  settings: any;
  sectionData: {
    title?: string;
    description?: string;
    story_title?: string;
    story_content?: string;
    layout?: 'image-left' | 'image-right' | 'centered';
    image_position?: 'left' | 'right' | 'background';
    stats?: Array<{
      value: string;
      label: string;
      color: string;
    }>;
    values?: Array<{
      title: string;
      description: string;
      icon: string;
    }>;
    image_title?: string;
    image_subtitle?: string;
    image_icon?: string;
    parallax?: boolean;
  };
}

// Icon mapping for dynamic icons
const iconMap: Record<string, React.ComponentType<any>> = {
  'target': Target,
  'heart': Heart,
  'award': Award,
  'lightbulb': Lightbulb,
  'star': Star,
  'shield': Shield,
  'users': Users,
  'zap': Zap
};

export default function AboutUs({ settings, sectionData, brandColor = '#3b82f6' }: AboutUsProps) {

  const { t } = useTranslation();
  const { ref, isVisible } = useScrollAnimation();


  const sectionImage = getDisplayUrl(sectionData.image);
  const backgroundColor = sectionData.background_color || '#f9fafb';
  const layout = sectionData.layout || 'image-right';
  const imagePosition = sectionData.image_position || 'right';
  const parallax = sectionData.parallax === true;

  const parallaxRef = useRef<HTMLDivElement>(null);
  const [parallaxOffset, setParallaxOffset] = useState(0);

  useEffect(() => {
    if (!parallax || !sectionImage) return;
    const handleScroll = () => {
      if (!parallaxRef.current) return;
      const rect = parallaxRef.current.getBoundingClientRect();
      const center = rect.top + rect.height / 2 - window.innerHeight / 2;
      setParallaxOffset(center * 0.2);
    };
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();
    return () => window.removeEventListener('scroll', handleScroll);
  }, [parallax, sectionImage]);
  // Default data if none provided
  const defaultValues = [
    {
      icon: 'target',
      title: t('Our Mission'),
      description: t('To revolutionize sales management by providing businesses with an all-in-one SaaS platform that simplifies, automates, and accelerates growth.')
    },
    {
      icon: 'heart',
      title: t('Our Values'),
      description: t('We believe in innovation, transparency, and empowering teams with tools that drive real business success.')
    },
    {
      icon: 'award',
      title: t('Our Commitment'),
      description: t('Delivering exceptional user experience with robust technology, secure infrastructure, and world-class support.')
    },
    {
      icon: 'lightbulb',
      title: t('Our Vision'),
      description: t('A future where every business can manage leads, deals, and customers seamlessly — unlocking their full sales potential.')
    }

  ];

  const defaultStats = [
    { value: '4+ Years', label: t('Industry Experience'), color: 'blue' },
    { value: '10K+', label: t('Happy Users'), color: 'green' },
    { value: '5K+', label: t('Businesses Powered'), color: 'purple' }
  ];

  const values = sectionData.values && sectionData.values.length > 0
    ? sectionData.values
    : defaultValues;

  const stats = sectionData.stats && sectionData.stats.length > 0
    ? sectionData.stats
    : defaultStats;

  // Reusable image block
  const ImageBlock = () => (
    <div className="bg-white rounded-xl p-8 border border-gray-200 h-96 flex items-center justify-center overflow-hidden">
      {sectionImage ? (
        <img
          src={sectionImage}
          alt={t('About Us')}
          className="max-w-full max-h-full object-contain rounded-lg"
          style={parallax ? { transform: `translateY(${parallaxOffset}px)`, transition: 'transform 0.1s linear' } : undefined}
        />
      ) : (
        <div className="text-center">
          <div className="w-24 h-24 bg-gray-100 rounded-full mx-auto mb-6 flex items-center justify-center">
            <span className="text-3xl">{sectionData.image_icon || '🚀'}</span>
          </div>
          <h4 className="text-xl font-semibold dark:text-gray-400 mb-2">
            {sectionData.image_title || t('Innovation Driven')}
          </h4>
          <p className="dark:text-gray-400">
            {sectionData.image_subtitle || t('Building the future of networking')}
          </p>
        </div>
      )}
    </div>
  );

  // Reusable text/stats block
  const TextBlock = ({ centered = false }: { centered?: boolean }) => (
    <div className={centered ? 'text-center' : ''}>
      <h3 className="text-2xl font-bold dark:text-gray-400 mb-6">
        {sectionData.story_title || t('Empowering Sales Teams Since 2020')}
      </h3>
      <div className="dark:text-gray-400 mb-8 leading-relaxed" dangerouslySetInnerHTML={{
        __html: (sectionData.story_content || t('Founded by a group of sales professionals and technology experts, our Sales SaaS was created to solve the common challenges businesses face in managing leads, orders, invoices, and projects. Today, we power thousands of businesses worldwide with a reliable, scalable, and easy-to-use platform.')).replace(/\n/g, '</p><p className="mb-6">')
      }} />
      {stats.length > 0 && (
        <div className={`flex items-center gap-8 ${centered ? 'justify-center' : ''}`}>
          {stats.map((stat, index) => (
            <div key={index} className="text-center">
              <div className="text-2xl font-bold dark:text-gray-400">{stat.value}</div>
              <div className="text-sm dark:text-gray-400">{stat.label}</div>
            </div>
          ))}
        </div>
      )}
    </div>
  );

  // Render the story section based on layout + image_position
  const renderStorySection = () => {
    // centered layout: image as background or stacked below text
    if (layout === 'centered') {
      if (imagePosition === 'background' && sectionImage) {
        return (
          <div ref={parallaxRef} className="relative rounded-xl overflow-hidden mb-8 sm:mb-12 lg:mb-16">
            <img
              src={sectionImage}
              alt="About Us"
              className="w-full h-96 object-cover"
              style={parallax ? { transform: `translateY(${parallaxOffset}px)`, transition: 'transform 0.1s linear', height: '110%', top: '-5%', position: 'relative' } : undefined}
            />
            <div className="absolute inset-0 bg-black/50 flex items-center justify-center p-8">
              <div className="text-center text-white max-w-2xl">
                <h3 className="text-2xl font-bold mb-4">
                  {sectionData.story_title || t('Empowering Sales Teams Since 2020')}
                </h3>
                <div className="mb-6 leading-relaxed opacity-90" dangerouslySetInnerHTML={{
                  __html: (sectionData.story_content || t('Founded by a group of sales professionals and technology experts, our Sales SaaS was created to solve the common challenges businesses face in managing leads, orders, invoices, and projects. Today, we power thousands of businesses worldwide with a reliable, scalable, and easy-to-use platform.')).replace(/\n/g, '</p><p className="mb-6">')
                }} />
                {stats.length > 0 && (
                  <div className="flex items-center justify-center gap-8">
                    {stats.map((stat, index) => (
                      <div key={index} className="text-center">
                        <div className="text-2xl font-bold">{stat.value}</div>
                        <div className="text-sm opacity-80">{stat.label}</div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          </div>
        );
      }
      return (
        <div className="max-w-3xl mx-auto text-center mb-8 sm:mb-12 lg:mb-16">
          <TextBlock centered />
          {(imagePosition === 'left' || imagePosition === 'right') && (
            <div className="mt-8">
              <ImageBlock />
            </div>
          )}
        </div>
      );
    }

    // image-left: image on left, text on right
    if (layout === 'image-left') {
      return (
        <div className="grid lg:grid-cols-2 gap-8 sm:gap-12 lg:gap-16 items-center mb-8 sm:mb-12 lg:mb-16">
          {imagePosition === 'background' && sectionImage ? (
            <div ref={parallaxRef} className="relative rounded-xl overflow-hidden h-96">
              <img
                src={sectionImage}
                alt="About Us"
                className="w-full object-cover"
                style={parallax ? { transform: `translateY(${parallaxOffset}px)`, transition: 'transform 0.1s linear', height: '120%', top: '-10%', position: 'relative' } : { height: '100%' }}
              />
              <div className="absolute inset-0 bg-black/30 rounded-xl" />
            </div>
          ) : (
            <ImageBlock />
          )}
          <TextBlock />
        </div>
      );
    }

    // image-right (default): text on left, image on right
    return (
      <div className="grid lg:grid-cols-2 gap-8 sm:gap-12 lg:gap-16 items-center mb-8 sm:mb-12 lg:mb-16">
        <TextBlock />
        {imagePosition === 'background' && sectionImage ? (
          <div ref={parallaxRef} className="relative rounded-xl overflow-hidden h-96">
            <img
              src={sectionImage}
              alt="About Us"
              className="w-full object-cover"
              style={parallax ? { transform: `translateY(${parallaxOffset}px)`, transition: 'transform 0.1s linear', height: '120%', top: '-10%', position: 'relative' } : { height: '100%' }}
            />
            <div className="absolute inset-0 bg-black/30 rounded-xl" />
          </div>
        ) : (
          <ImageBlock />
        )}
      </div>
    );
  };

  return (
    <section id="about" className="py-12 sm:py-16 lg:py-20" style={{ backgroundColor }} ref={ref}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className={`text-center mb-8 sm:mb-12 lg:mb-16 transition-all duration-700 ${isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'}`}>
          <h2 className="text-3xl md:text-4xl font-bold dark:text-gray-400 mb-4">
            {sectionData.title || t('About Sales SaaS')}
          </h2>
          <p className="text-lg dark:text-gray-400 max-w-3xl mx-auto leading-relaxed font-medium">
            {sectionData.description || t('We are dedicated to simplifying and automating the entire sales lifecycle for businesses of all sizes.')}
          </p>
        </div>

        {renderStorySection()}

        {/* Values Grid */}
        <div className={`grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 transition-all duration-700 delay-500 ${isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'}`}>
          {values.map((value, index) => {
            const IconComponent = iconMap[value.icon] || Target;
            return (
              <div key={index} className="text-center bg-white p-6 rounded-xl border border-gray-200">
                <div className="w-12 h-12 rounded-lg flex items-center justify-center mx-auto mb-4" style={{ backgroundColor: `${brandColor}15` }}>
                  <IconComponent className="w-6 h-6" style={{ color: brandColor }} />
                </div>
                <h3 className="text-lg font-semibold dark:text-gray-400 mb-3">
                  {value.title}
                </h3>
                <p className="dark:text-gray-400 text-sm leading-relaxed">
                  {value.description}
                </p>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
