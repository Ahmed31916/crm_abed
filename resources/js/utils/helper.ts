import { t } from "i18next";

const getBaseUrl = (): string => {
    return window.appSettings?.baseUrl || window.location.origin;
};

const getGlobalSettings = () => {
    return (window as any).page.props.globalSettings;
};

const getDisplayUrl = (path: string, pageProps?: any): string => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    // If path already contains storage/media, just prepend domain
    if (path.includes('storage/media')) {
        const base = getBaseUrl();
        return path.startsWith('/') ? `${base}${path}` : `${base}/${path}`;
    }
    if (path.includes('media')) {
        const base = getBaseUrl();
        return path.startsWith('/') ? `${base}/storage${path}` : `${base}/storage/${path}`;
    }

    try {
        const base = getBaseUrl();
        const dynamicPath = `${base}/storage/media/`;
        let imageUrlPrefix = getGlobalSettings()?.image_url || dynamicPath;

        if (!imageUrlPrefix.includes('storage/media')) {
            imageUrlPrefix = imageUrlPrefix.endsWith('/') ? imageUrlPrefix + 'storage/media/' : imageUrlPrefix + '/storage/media/';
        }

        // Handle slash concatenation
        const prefixEndsWithSlash = imageUrlPrefix.endsWith('/');
        const pathStartsWithSlash = path.startsWith('/');

        if (prefixEndsWithSlash && pathStartsWithSlash) {
            return imageUrlPrefix + path.substring(1);
        } else if (!prefixEndsWithSlash && !pathStartsWithSlash) {
            return imageUrlPrefix + '/' + path;
        } else {
            return imageUrlPrefix + path;
        }
    }
    catch {
        const fallbackPrefix = `${window.location.origin}/storage/media/`;
        return path.startsWith('/') ? fallbackPrefix + path.substring(1) : fallbackPrefix + path;
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
        .toLowerCase() // Convert everything to lowercase first
        .replace(/_/g, ' ') // Replace underscores with spaces
        .split(' ') // Split into words
        .map(word => word.charAt(0).toUpperCase() + word.slice(1)) // Capitalize first letter of each word
        .join(' '); // Join back with spaces
};

export { getDisplayUrl, isRegistrationEnabled, getTermsAndConditionsUrl, isDisabledEditRole, formatRelativeTime, capitalize };
