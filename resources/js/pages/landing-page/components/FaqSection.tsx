import React, { useState } from 'react';
import { ChevronDown, ChevronUp } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface Faq {
  id: number;
  question: string;
  answer: string;
}

interface FaqSectionProps {
  brandColor?: string;
  faqs: Faq[];
  settings?: any;
  sectionData?: {
    title?: string;
    subtitle?: string;
    cta_text?: string;
    button_text?: string;
    default_faqs?: Array<{
      question: string;
      answer: string;
    }>;
  };
}

export default function FaqSection({ faqs, settings, sectionData, brandColor = '#3b82f6' }: FaqSectionProps) {
  const [openFaq, setOpenFaq] = useState<number | null>(null);
  const { t } = useTranslation();

  // Default FAQs if none provided
  const defaultFaqs = [
    {
      question: t('What makes this SaaS different from others?'),
      answer: t('Unlike other tools, our platform is built to help you grow faster. With powerful automation, real-time analytics, and world-class support, you’ll get results from day one.')
    },
    {
      question: t('Do I need to pay upfront to get started?'),
      answer: t('Not at all! You can start with our free trial today. No credit card required. Upgrade anytime when you’re ready to unlock advanced features.')
    },
    {
      question: t('Can I scale my plan as my business grows?'),
      answer: t('Yes! Our pricing is flexible. Start small and scale seamlessly — whether you’re a freelancer, startup, or enterprise, we have a plan tailored for your growth.')
    },
    {
      question: t('How secure is my data on your platform?'),
      answer: t('We use enterprise-grade encryption and follow strict compliance standards, so your data is always safe. Security is our top priority.')
    },
    {
      question: t('What kind of support do I get?'),
      answer: t('Every plan includes email support, and higher tiers unlock priority support with a dedicated account manager — so you’re never left waiting.')
    },
    {
      question: t('Why should I upgrade to a paid plan?'),
      answer: t('Paid plans unlock premium features like advanced analytics, integrations, team management, and unlimited usage — giving your business the competitive edge it needs.')
    }
  ];

  const backendFaqs = sectionData?.faqs?.map((faq, index) => ({
    id: index + 1,
    ...faq
  })) || defaultFaqs;

  const displayFaqs = faqs.length > 0 ? faqs : backendFaqs;

  const toggleFaq = (id: number) => {
    setOpenFaq(openFaq === id ? null : id);
  };

  return (
    <section className="py-12 sm:py-16 lg:py-20 bg-white">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-8 sm:mb-12 lg:mb-16">
          <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            {sectionData?.title || t('Frequently Asked Questions')}
          </h2>
          <p className="text-lg text-gray-600 leading-relaxed font-medium">
            {sectionData?.subtitle || t('Everything you need to know before getting started.')}
          </p>
        </div>

        <div className="space-y-2 sm:space-y-3">
          {displayFaqs.map((faq) => (
            <div
              key={faq.id}
              className="bg-gray-50 border border-gray-200 rounded-lg"
            >
              <button
                onClick={() => toggleFaq(faq.id)}
                className="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                aria-expanded={openFaq === faq.id}
                aria-controls={`faq-answer-${faq.id}`}
                aria-describedby={`faq-question-${faq.id}`}
              >
                <h3 className="text-lg font-semibold text-gray-900 pr-4" id={`faq-question-${faq.id}`}>
                  {faq.question}
                </h3>
                {openFaq === faq.id ? (
                  <ChevronUp className="w-5 h-5 text-gray-600 flex-shrink-0" aria-hidden="true" />
                ) : (
                  <ChevronDown className="w-5 h-5 text-gray-600 flex-shrink-0" aria-hidden="true" />
                )}
              </button>

              {openFaq === faq.id && (
                <div className="px-6 pb-4 border-t border-gray-200" id={`faq-answer-${faq.id}`} role="region" aria-labelledby={`faq-question-${faq.id}`}>
                  <p className="text-gray-600 leading-relaxed pt-4">
                    {faq.answer}
                  </p>
                </div>
              )}
            </div>
          ))}
        </div>

        {(sectionData?.cta_text || sectionData?.button_text) && (
          <div className="text-center mt-8 sm:mt-12">
            <p className="text-gray-600 mb-4">
              {sectionData?.cta_text || t('Didn’t find your answer?')}
            </p>
            <a
                href={"#contact"}
                className="inline-flex items-center gap-2 text-white px-6 py-3 rounded-lg transition-colors font-semibold text-white rounded-lg transition-colors font-semibold text-base flex items-center justify-center gap-2 hover:opacity-90 border"
                style={{
                    backgroundColor: brandColor,
                    color: 'white',
                    borderColor: brandColor
                }}
                onMouseEnter={(e) => {
                    e.currentTarget.style.backgroundColor = 'white';
                    e.currentTarget.style.color = brandColor;
                }}
                onMouseLeave={(e) => {
                    e.currentTarget.style.backgroundColor = brandColor;
                    e.currentTarget.style.color = 'white';
                }}
                aria-label={t('Start free trial - Register for Sales Saas')}
              >
                {sectionData?.button_text || t('Talk to Sales')}
              </a>
            {/* <a
              href="#contact"
              className="inline-flex items-center gap-2 text-white px-6 py-3 rounded-lg transition-colors font-semibold"
              style={{ backgroundColor: brandColor }}
            >
              {sectionData?.button_text || t('Talk to Sales')}
            </a> */}
          </div>
        )}
      </div>
    </section>
  );
}
