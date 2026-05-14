import { useEffect } from 'react';
import { useBrand } from '@/contexts/BrandContext';
import { THEME_COLORS } from '@/hooks/use-appearance';

export function useBrandTheme() {
  const { themeColor, customColor, themeMode } = useBrand();

  useEffect(() => {
    // Apply theme color
    const color = themeColor === 'custom' ? customColor : THEME_COLORS[themeColor as keyof typeof THEME_COLORS];
    if (color) {
      document.documentElement.style.setProperty('--theme-color', color);
      document.documentElement.style.setProperty('--primary', color);
      document.documentElement.style.setProperty('--chart-1', color);
    }
  }, [themeColor, customColor]);

  useEffect(() => {
    // Apply theme mode
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = themeMode === 'dark' || (themeMode === 'system' && prefersDark);
    document.documentElement.classList.toggle('dark', isDark);
  }, [themeMode]);
}