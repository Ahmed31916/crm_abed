import React from 'react';
import { usePage, Head } from '@inertiajs/react';
import Header from './components/Header';
import Footer from './components/Footer';
import { useFavicon } from '@/hooks/use-favicon';

interface CustomPage {
  id: number;
  title: string;
  slug: string;
  content: string;
  meta_title?: string;
  meta_description?: string;
  is_active: boolean;
}

interface CustomPageData {
  id: number;
  title: string;
  slug: string;
}

interface PageProps {
  page: CustomPage;
  customPages: CustomPageData[];
  settings: {
    company_name: string;
    contact_email?: string;
    contact_phone?: string;
    contact_address?: string;
    config_sections?: {
      sections?: Array<{
        key: string;
        [key: string]: any;
      }>;
    };
    [key: string]: any;
  };
}

export default function CustomPage() {
  // Custom CSS for content styling
  const customCSS = `
    .prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
      color: #1f2937;
      font-weight: 600;
      margin-top: 2rem;
      margin-bottom: 1rem;
    }

    .prose h1 { font-size: 2.25rem; }
    .prose h2 { font-size: 1.875rem; }
    .prose h3 { font-size: 1.5rem; }

    .prose p {
      margin-bottom: 1.5rem;
      line-height: 1.75;
    }

    .prose ul, .prose ol {
      margin: 1.5rem 0;
      padding-left: 1.5rem;
    }

    .prose li {
      margin-bottom: 0.5rem;
    }

    .prose a {
      color: var(--primary-color);
      text-decoration: underline;
    }

    .prose blockquote {
      border-left: 4px solid var(--primary-color);
      padding-left: 1rem;
      margin: 1.5rem 0;
      font-style: italic;
      background-color: #f9fafb;
      padding: 1rem;
    }

    .prose img {
      max-width: 100%;
      height: auto;
      border-radius: 0.5rem;
      margin: 1.5rem 0;
    }
  `;
  const { page, customPages = [], settings } = usePage<PageProps>().props;
  const primaryColor = settings?.config_sections?.theme?.primary_color || '#3b82f6';
  const secondaryColor = settings?.config_sections?.theme?.secondary_color || '#8b5cf6';
  const accentColor = settings?.config_sections?.theme?.accent_color || '#10b77f';
  const globalSettings = (usePage().props as any).globalSettings;
  const userLanguage = (usePage().props as any).userLanguage;
  useFavicon();
//   React.useEffect(() => {
//           const isDemo = globalSettings?.is_demo || false;
//           let storedPosition = 'left';

//           if (isDemo) {
//               // In demo mode, use cookies
//               const getCookie = (name: string): string | null => {
//                   if (typeof document === 'undefined') return null;
//                   const value = `; ${document.cookie}`;
//                   const parts = value.split(`; ${name}=`);
//                   if (parts.length === 2) {
//                       const cookieValue = parts.pop()?.split(';').shift();
//                       return cookieValue ? decodeURIComponent(cookieValue) : null;
//                   }
//                   return null;
//               };
//               const stored = getCookie('layoutDirection');
//               if (stored === 'left' || stored === 'right') {
//                   storedPosition = stored;
//               }
//           } else {
//               // In normal mode, get from database via globalSettings
//               const stored = globalSettings?.layoutDirection;
//               if (stored === 'left' || stored === 'right') {
//                   storedPosition = stored;
//               }
//           }

//           const dir = storedPosition === 'right' ? 'rtl' : 'ltr';
//           document.documentElement.dir = dir;
//           document.documentElement.setAttribute('dir', dir);

//           // Check if it was actually set
//           setTimeout(() => {
//               const actualDir = document.documentElement.getAttribute('dir');
//               if (actualDir !== dir) {
//                   document.documentElement.dir = dir;
//                   document.documentElement.setAttribute('dir', dir);
//               }
//           }, 100);

//       }, []);

// before
// React.useEffect(() => {
//         const isDemo = globalSettings?.is_demo || false;
//         const currentLang = userLanguage || globalSettings?.defaultLanguage || 'en';
//         const isRTL = ['ar', 'he'].includes(currentLang);
//         let dir = isRTL ? 'rtl' : 'ltr';

//         if (isDemo) {
//             const getCookie = (name: string): string | null => {
//                 if (typeof document === 'undefined') return null;
//                 const value = `; ${document.cookie}`;
//                 const parts = value.split(`; ${name}=`);
//                 if (parts.length === 2) {
//                     const cookieValue = parts.pop()?.split(';').shift();
//                     return cookieValue ? decodeURIComponent(cookieValue) : null;
//                 }
//                 return null;
//             };
//             const stored = getCookie('layoutDirection');
//             if (stored === 'left' || stored === 'right') {
//                 dir = stored === 'right' ? 'rtl' : 'ltr';
//             }
//         }

//         document.documentElement.dir = dir;
//         document.documentElement.setAttribute('dir', dir);

//         setTimeout(() => {
//             const actualDir = document.documentElement.getAttribute('dir');
//             if (actualDir !== dir) {
//                 document.documentElement.dir = dir;
//                 document.documentElement.setAttribute('dir', dir);
//             }
//         }, 100);

//     }, [userLanguage, globalSettings?.defaultLanguage, globalSettings?.is_demo]);


    // RTL Support for custom pages - Apply immediately and persist
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

  return (
    <>
      <Head>
        <title>{page.meta_title || page.title}</title>
        {page.meta_description && (
          <meta name="description" content={page.meta_description} />
        )}
        <style>{customCSS}</style>
      </Head>

      <div
        className="min-h-screen bg-white"
        style={{
          '--primary-color': primaryColor,
          '--secondary-color': secondaryColor,
          '--accent-color': accentColor,
          '--primary-color-rgb': primaryColor.replace('#', '').match(/.{2}/g)?.map(x => parseInt(x, 16)).join(', ') || '59, 130, 246',
          '--secondary-color-rgb': secondaryColor.replace('#', '').match(/.{2}/g)?.map(x => parseInt(x, 16)).join(', ') || '139, 92, 246',
          '--accent-color-rgb': accentColor.replace('#', '').match(/.{2}/g)?.map(x => parseInt(x, 16)).join(', ') || '16, 185, 129'
        } as React.CSSProperties}
      >
        <Header max-w-7xl mx-auto p
          settings={settings}
          customPages={customPages}
          sectionData={settings?.config_sections?.sections?.find(s => s.key === 'header') || {}}
          brandColor={primaryColor}
        />

        <main className="pt-16">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div className="max-w-4xl mx-auto">
              <header className="text-center mb-12">
                <h1 className="text-4xl font-bold text-gray-900 mb-4">{page.title}</h1>
                <div className="w-24 h-1 bg-gradient-to-r from-blue-500 to-purple-600 mx-auto rounded-full"></div>
              </header>

              <article className="prose prose-lg max-w-none">
                <div
                  className="text-gray-700 leading-relaxed"
                  dangerouslySetInnerHTML={{ __html: page.content }}
                />
              </article>
            </div>
          </div>
        </main>

        <Footer
          settings={settings}
          sectionData={settings?.config_sections?.sections?.find(s => s.key === 'footer') || {}}
          brandColor={primaryColor}
        />
      </div>
    </>
  );
}
