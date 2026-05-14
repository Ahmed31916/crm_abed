import { useCallback, useEffect, useState } from 'react';
import { setCookie, getCookie, isDemoMode } from '@/utils/cookie-utils';

export type Appearance = 'light' | 'dark' | 'system';
export type ThemeColor = 'blue' | 'green' | 'purple' | 'orange' | 'red' | 'custom';

export interface ThemeSettings {
    appearance: Appearance;
    themeColor: ThemeColor;
    customColor: string;
}

const DEFAULT_THEME: ThemeSettings = {
    appearance: 'system',
    themeColor: 'green',
    customColor: '#3b82f6', // Default blue color
};

// Preset theme colors
export const THEME_COLORS = {
    blue: '#3b82f6',
    green: '#10b77f',
    purple: '#8b5cf6',
    orange: '#f97316',
    red: '#ef4444',
};

const prefersDark = () => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};



const applyTheme = (settings: ThemeSettings) => {
    const { appearance, themeColor, customColor } = settings;
    const isDark = appearance === 'dark' || (appearance === 'system' && prefersDark());

    // Apply dark mode class
    document.documentElement.classList.toggle('dark', isDark);
    document.body.classList.toggle('dark', isDark);

    // Apply theme color
    const color = themeColor === 'custom' ? customColor : THEME_COLORS[themeColor];
    document.documentElement.style.setProperty('--theme-color', color);

    // Also update CSS variables that depend on theme color
    document.documentElement.style.setProperty('--primary', color);
    document.documentElement.style.setProperty('--chart-1', color);

    // Generate a lighter/darker variant for hover states
    const adjustedColor = isDark ? lightenColor(color, 10) : darkenColor(color, 10);
    document.documentElement.style.setProperty('--theme-color-hover', adjustedColor);

    // Force a small repaint to ensure colors are applied
    const tempClass = 'theme-color-updating';
    document.documentElement.classList.add(tempClass);
    setTimeout(() => {
        document.documentElement.classList.remove(tempClass);
    }, 10);
};

// Helper function to lighten a color
const lightenColor = (hex: string, percent: number): string => {
    // Convert hex to RGB
    let r = parseInt(hex.substring(1, 3), 16);
    let g = parseInt(hex.substring(3, 5), 16);
    let b = parseInt(hex.substring(5, 7), 16);

    // Lighten
    r = Math.min(255, Math.floor(r * (1 + percent / 100)));
    g = Math.min(255, Math.floor(g * (1 + percent / 100)));
    b = Math.min(255, Math.floor(b * (1 + percent / 100)));

    // Convert back to hex
    return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
};

// Helper function to darken a color
const darkenColor = (hex: string, percent: number): string => {
    // Convert hex to RGB
    let r = parseInt(hex.substring(1, 3), 16);
    let g = parseInt(hex.substring(3, 5), 16);
    let b = parseInt(hex.substring(5, 7), 16);

    // Darken
    r = Math.max(0, Math.floor(r * (1 - percent / 100)));
    g = Math.max(0, Math.floor(g * (1 - percent / 100)));
    b = Math.max(0, Math.floor(b * (1 - percent / 100)));

    // Convert back to hex
    return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const handleSystemThemeChange = () => {
    const themeSettings = getThemeSettings();
    applyTheme(themeSettings);
};

const getThemeSettings = (): ThemeSettings => {
    if (isDemoMode()) {
        // In demo mode, use cookies
        try {
            const savedTheme = getCookie('themeSettings');
            if (savedTheme) {
                return JSON.parse(savedTheme);
            }
        } catch (error) {
            // Fall through to default
        }
    } else {
        // In live mode, get from globalSettings
        const globalSettings = (window as any).page?.props?.globalSettings;
        if (globalSettings) {
            return {
                appearance: globalSettings.themeMode || DEFAULT_THEME.appearance,
                themeColor: globalSettings.themeColor || DEFAULT_THEME.themeColor,
                customColor: globalSettings.customColor || DEFAULT_THEME.customColor
            };
        }
    }

    return DEFAULT_THEME;
};

export function initializeTheme() {
    const themeSettings = getThemeSettings();
    applyTheme(themeSettings);

    // Add the event listener for system theme changes...
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
    const [themeSettings, setThemeSettings] = useState<ThemeSettings>(DEFAULT_THEME);

    const updateAppearance = useCallback((mode: Appearance) => {
        setThemeSettings(prev => {
            const newSettings = { ...prev, appearance: mode };

            // Only apply theme for preview, don't store yet
            applyTheme(newSettings);
            return newSettings;
        });
    }, []);

    const updateThemeColor = useCallback((color: ThemeColor) => {
        setThemeSettings(prev => {
            const newSettings = { ...prev, themeColor: color };

            // Only apply theme for preview, don't store yet
            applyTheme(newSettings);
            return newSettings;
        });
    }, []);

    const updateCustomColor = useCallback((hexColor: string, setAsActive = false) => {
        setThemeSettings(prev => {
            const newSettings = {
                ...prev,
                customColor: hexColor,
                ...(setAsActive && { themeColor: 'custom' })
            };

            // Only apply theme for preview, don't store yet
            applyTheme(newSettings);
            return newSettings;
        });
    }, []);

    const saveThemeSettings = useCallback(() => {
        if (isDemoMode()) {
            // Save to cookies only when explicitly called
            setCookie('themeSettings', JSON.stringify(themeSettings));
            setCookie('appearance', themeSettings.appearance);
        }
        // In non-demo mode, saving is handled by the parent component
    }, [themeSettings]);

    useEffect(() => {
        let savedSettings = getThemeSettings();

        // In non-demo mode, get theme settings from database
        if (!isDemoMode()) {
            const globalSettings = (window as any).page?.props?.globalSettings;
            if (globalSettings) {
                savedSettings = {
                    appearance: globalSettings.themeMode || DEFAULT_THEME.appearance,
                    themeColor: globalSettings.themeColor || DEFAULT_THEME.themeColor,
                    customColor: globalSettings.customColor || DEFAULT_THEME.customColor
                };
            }
        }

        setThemeSettings(savedSettings);
        applyTheme(savedSettings);

        return () => mediaQuery()?.removeEventListener('change', handleSystemThemeChange);
    }, [(window as any).page?.props?.globalSettings]);

    return {
        appearance: themeSettings.appearance,
        themeColor: themeSettings.themeColor,
        customColor: themeSettings.customColor,
        updateAppearance,
        updateThemeColor,
        updateCustomColor,
        saveThemeSettings
    } as const;
}
