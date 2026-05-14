import { createContext, ReactNode, useContext, useEffect, useState } from 'react';
import { getSidebarSettings, SidebarSettings } from '@/components/sidebar-style-settings';
import { setCookie, getCookie, isDemoMode } from '@/utils/cookie-utils';

type SidebarContextType = {
  variant: SidebarSettings['variant'];
  collapsible: SidebarSettings['collapsible'];
  style: string;
  updateVariant: (variant: SidebarSettings['variant']) => void;
  updateCollapsible: (collapsible: SidebarSettings['collapsible']) => void;
  updateStyle: (style: string) => void;
  saveSidebarSettings: () => void;
};

const SidebarContext = createContext<SidebarContextType | undefined>(undefined);

// Extended sidebar settings with style
interface ExtendedSidebarSettings extends SidebarSettings {
  style: string;
}

// Default sidebar settings with style
const DEFAULT_EXTENDED_SETTINGS: ExtendedSidebarSettings = {
  variant: 'inset',
  collapsible: 'icon',
  style: 'plain'
};

// Get extended sidebar settings from cookies (demo mode) or database (non-demo mode)
const getExtendedSidebarSettings = (): ExtendedSidebarSettings => {
  if (isDemoMode()) {
    // In demo mode, use cookies
    try {
      const savedSettings = getCookie('sidebarSettings');
      return savedSettings ? JSON.parse(savedSettings) : DEFAULT_EXTENDED_SETTINGS;
    } catch (error) {
      return DEFAULT_EXTENDED_SETTINGS;
    }
  }

  // In non-demo mode, get from database via global settings
  const globalSettings = (window as any).page?.props?.globalSettings;
  if (globalSettings) {
    return {
      variant: globalSettings.sidebarVariant || DEFAULT_EXTENDED_SETTINGS.variant,
      collapsible: DEFAULT_EXTENDED_SETTINGS.collapsible,
      style: globalSettings.sidebarStyle || DEFAULT_EXTENDED_SETTINGS.style
    };
  }

  return DEFAULT_EXTENDED_SETTINGS;
};

export const SidebarProvider = ({ children }: { children: ReactNode }) => {
  const [settings, setSettings] = useState<ExtendedSidebarSettings>(getExtendedSidebarSettings());

  // Update variant
  const updateVariant = (variant: SidebarSettings['variant']) => {
    setSettings(prev => {
      const newSettings = { ...prev, variant };

      if (isDemoMode()) {
        setCookie('sidebarSettings', JSON.stringify(newSettings));
      }
      // In non-demo mode, don't store locally - will be handled by parent component

      return newSettings;
    });
  };

  // Update collapsible
  const updateCollapsible = (collapsible: SidebarSettings['collapsible']) => {
    setSettings(prev => {
      const newSettings = { ...prev, collapsible };

      if (isDemoMode()) {
        setCookie('sidebarSettings', JSON.stringify(newSettings));
      }
      // In non-demo mode, don't store locally - will be handled by parent component

      return newSettings;
    });
  };

  // Update style
    const updateStyle = (style: string) => {
        setSettings(prev => ({ ...prev, style }));
    };
    // Save sidebar settings to cookies (demo mode only)
  const saveSidebarSettings = () => {
    const isDemo = (window as any).page?.props?.globalSettings?.is_demo || false;

    if (isDemo) {
      setCookie('sidebarSettings', JSON.stringify(settings));
    }
  };

  useEffect(() => {
    // Reload settings when global settings change
    const newSettings = getExtendedSidebarSettings();
    setSettings(newSettings);
  }, [(window as any).page?.props?.globalSettings]);

  useEffect(() => {
    if (isDemoMode()) {
      // In demo mode, listen for storage events to update settings when changed from another tab
      const handleStorageChange = (event: StorageEvent) => {
        if (event.key === 'sidebarSettings') {
          try {
            const newSettings = JSON.parse(event.newValue || '');
            setSettings(newSettings);
          } catch (error) {
          }
        }
      };

      window.addEventListener('storage', handleStorageChange);
      return () => window.removeEventListener('storage', handleStorageChange);
    }
  }, []);

  return (
    <SidebarContext.Provider value={{
      variant: settings.variant,
      collapsible: settings.collapsible,
      style: settings.style,
      updateVariant,
      updateCollapsible,
      updateStyle,
      saveSidebarSettings
    }}>
      {children}
    </SidebarContext.Provider>
  );
};

export const useSidebarSettings = () => {
  const context = useContext(SidebarContext);
  if (!context) throw new Error('useSidebarSettings must be used within SidebarProvider');
  return context;
};
