import { t } from "i18next";

/**
 * تحديد ما إذا كنا في بيئة localhost محلية.
 * نستخدمها لاتخاذ قرار: هل نسمح بروابط localhost أم نُصفّيها؟
 */
const isLocalEnvironment = (): boolean => {
    const host = window.location.hostname;
    return host === 'localhost'
        || host === '127.0.0.1'
        || host === '::1'
        || host.startsWith('192.168.')
        || host.startsWith('10.')
        || host.endsWith('.local');
};

/**
 * إزالة أي إشارة إلى localhost/127.0.0.1 من URL النهائي عندما نحن في الإنتاج.
 * هذا يمنع أخطاء CORS و loopback عندما يكون appSettings.baseUrl أو
 * globalSettings.image_url مضبوطين بالخطأ على localhost بعد النشر.
 *
 * مثال:
 *   - في الإنتاج: "http://localhost/storage/media/x.png"
 *        → "https://crtest.vitalsinfo.com/storage/media/x.png"
 *   - في localhost: يُرجَّع كما هو دون تغيير.
 */
const sanitizeUrl = (url: string): string => {
    if (!url) return '';

    // في البيئة المحلية لا حاجة لأي تعديل
    if (isLocalEnvironment()) return url;

    // إذا كان URL نسبي (يبدأ بـ / أو لا يحتوي على protocol) فلا داعي للفحص
    if (!/^https?:\/\//i.test(url)) return url;

    try {
        const urlObj = new URL(url);
        const host = urlObj.hostname;

        // إذا كان الـ host يشير إلى loopback/local ولكننا في إنتاج → استبدله بـ origin الحالي
        if (host === 'localhost' || host === '127.0.0.1' || host === '::1'
            || host.startsWith('192.168.') || host.startsWith('10.')) {
            return window.location.origin + urlObj.pathname + urlObj.search + urlObj.hash;
        }

        // كذلك إذا كان البروتوكول http ولكن الموقع يعمل على https (mixed content)
        if (urlObj.protocol === 'http:' && window.location.protocol === 'https:') {
            urlObj.protocol = 'https:';
            return urlObj.toString();
        }

        return url;
    } catch {
        // URL غير صالح، أرجعه كما هو
        return url;
    }
};

const getBaseUrl = (): string => {
    // الأولوية: appSettings.baseUrl، ثم window.location.origin
    // لكن نتأكد من تطهيره من إشارات localhost عند الإنتاج
    const raw = window.appSettings?.baseUrl || window.location.origin;
    return sanitizeUrl(raw);
};

const getGlobalSettings = () => {
    return (window as any).page?.props?.globalSettings
        || (window as any).globalSettings;
};

const getDisplayUrl = (path: string, pageProps?: any): string => {
    if (!path) return '';

    // إذا كان path كامل URL أصلاً (مثل https://...) نطبّق عليه sanitizeUrl فقط
    if (path.startsWith('http')) {
        return sanitizeUrl(path);
    }

    // إذا كان path يحتوي على storage/media، فقط نضيف domain
    if (path.includes('storage/media')) {
        const base = getBaseUrl();
        const assembled = path.startsWith('/') ? `${base}${path}` : `${base}/${path}`;
        return sanitizeUrl(assembled);
    }

    // إذا كان path يحتوي على media (بدون storage)
    if (path.includes('media')) {
        const base = getBaseUrl();
        const assembled = path.startsWith('/')
            ? `${base}/storage${path}`
            : `${base}/storage/${path}`;
        return sanitizeUrl(assembled);
    }

    // الحالة العامة: نستخدم image_url من globalSettings أو نُسقط على dynamicPath
    try {
        const base = getBaseUrl();
        const dynamicPath = `${base}/storage/media/`;
        let imageUrlPrefix = getGlobalSettings()?.image_url || dynamicPath;

        // نتأكد دائماً من أن prefix ينتهي بـ storage/media/
        if (!imageUrlPrefix.includes('storage/media')) {
            imageUrlPrefix = imageUrlPrefix.endsWith('/')
                ? imageUrlPrefix + 'storage/media/'
                : imageUrlPrefix + '/storage/media/';
        }

        // معالجة concat الشرطة المائلة
        const prefixEndsWithSlash = imageUrlPrefix.endsWith('/');
        const pathStartsWithSlash = path.startsWith('/');

        let assembled: string;
        if (prefixEndsWithSlash && pathStartsWithSlash) {
            assembled = imageUrlPrefix + path.substring(1);
        } else if (!prefixEndsWithSlash && !pathStartsWithSlash) {
            assembled = imageUrlPrefix + '/' + path;
        } else {
            assembled = imageUrlPrefix + path;
        }

        return sanitizeUrl(assembled);
    } catch {
        // fallback نهائي: نستخدم origin الصفحة الحالي مباشرة (وليس appSettings)
        const fallbackPrefix = `${window.location.origin}/storage/media/`;
        const assembled = path.startsWith('/')
            ? fallbackPrefix + path.substring(1)
            : fallbackPrefix + path;
        return sanitizeUrl(assembled);
    }
};

const isRegistrationEnabled = () => {
    const globalSettings = getGlobalSettings();
    return globalSettings?.registrationEnabled;
}

const getTermsAndConditionsUrl = () => {
    const globalSettings = getGlobalSettings();
    return globalSettings?.termsConditionsPage;
}

const isDisabledEditRole = (role: string) => {
    const roles = ['sales-manager'];
    return roles.includes(role);
}

const formatRelativeTime = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

    if (diffInMinutes < 1) return t('Just now');
    if (diffInMinutes < 60) return t('{{count}} {{unit}} ago', { count: diffInMinutes, unit: diffInMinutes === 1 ? t('minute') : t('minutes') });

    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return t('{{count}} {{unit}} ago', { count: diffInHours, unit: diffInHours === 1 ? t('hour') : t('hours') });

    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 7) return t('{{count}} {{unit}} ago', { count: diffInDays, unit: diffInDays === 1 ? t('day') : t('days') });

    return window?.appSettings?.formatDateTime(date, false);
};

const capitalize = (str: string) => {
    if (!str) return '';

    return str
        .toLowerCase()
        .replace(/_/g, ' ')
        .split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};

export {
    getDisplayUrl,
    isRegistrationEnabled,
    getTermsAndConditionsUrl,
    isDisabledEditRole,
    formatRelativeTime,
    capitalize,
    // نصدّر هذه أيضاً حتى يمكن استخدامها في مكونات أخرى إن لزم
    sanitizeUrl,
    isLocalEnvironment
};
