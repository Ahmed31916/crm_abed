/**
 * Cookie utility functions for managing theme settings in demo mode
 */

export const setCookie = (name: string, value: string, days = 365) => {
  if (typeof document === 'undefined') {
    return;
  }

  const maxAge = days * 24 * 60 * 60;
  document.cookie = `${name}=${encodeURIComponent(value)};path=/;max-age=${maxAge};SameSite=Lax`;
};

export const getCookie = (name: string): string | null => {
  if (typeof document === 'undefined') return null;
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) {
    const cookieValue = parts.pop()?.split(';').shift();
    return cookieValue ? decodeURIComponent(cookieValue) : null;
  }
  return null;
};

export const removeCookie = (name: string) => {
  if (typeof document === 'undefined') {
    return;
  }

  document.cookie = `${name}=;path=/;max-age=0;SameSite=Lax`;
};

/**
 * Check if the application is running in demo mode
 */
export const isDemoMode = (): boolean => {
  // Check globalSettings from middleware (primary source)
  const globalSettings = (window as any).page?.props?.globalSettings;
  if (globalSettings?.is_demo !== undefined) {
    return globalSettings.is_demo === true || globalSettings.is_demo === '1';
  }

  // Fallback to other sources
  const pageProps = (window as any).page?.props?.is_demo;
  const appSettings = (window as any).appSettings?.isDemo;

  return pageProps || appSettings || false;
};

/**
 * Get theme settings from cookies (demo mode) or return default
 */
export const getThemeSettingsFromCookies = (defaultSettings: any) => {
  if (!isDemoMode()) {
    return defaultSettings;
  }

  try {
    const savedTheme = getCookie('themeSettings');
    return savedTheme ? JSON.parse(savedTheme) : defaultSettings;
  } catch (error) {
    return defaultSettings;
  }
};

/**
 * Save theme settings to cookies (only in demo mode)
 */
export const saveThemeSettingsToCookies = (settings: any) => {
  if (!isDemoMode()) {
    return;
  }

  try {
    setCookie('themeSettings', JSON.stringify(settings));
  } catch (error) {
  }
};
