import { useEffect, useRef } from 'react';
import { usePage } from '@inertiajs/react';

interface RecaptchaProps {
  onVerify: (token: string) => void;
  onExpired?: () => void;
  onError?: () => void;
}

declare global {
  interface Window {
    grecaptcha: any;
    onRecaptchaLoad: () => void;
  }
}

export function useRecaptchaSettings() {
  const { settings = {} } = usePage().props as any;
  const recaptchaEnabled = settings.recaptchaEnabled === 'true' || settings.recaptchaEnabled === true || settings.recaptchaEnabled === 1 || settings.recaptchaEnabled === '1';
  const recaptchaVersion = settings.recaptchaVersion || 'v2';
  const recaptchaSiteKey = settings.recaptchaSiteKey || '';

  return { recaptchaEnabled, recaptchaVersion, recaptchaSiteKey };
}

export default function Recaptcha({ onVerify, onExpired, onError }: RecaptchaProps) {
  const recaptchaRef = useRef<HTMLDivElement>(null);
  const widgetId = useRef<number | null>(null);
  const { recaptchaEnabled, recaptchaVersion, recaptchaSiteKey } = useRecaptchaSettings();

  useEffect(() => {
    if (!recaptchaEnabled || !recaptchaSiteKey) return;

    const loadRecaptcha = () => {
      try {
        if (recaptchaVersion === 'v2') {
          loadRecaptchaV2();
        } else {
          loadRecaptchaV3();
        }
      } catch (error) {
        if (onError) onError();
      }
    };

    const loadRecaptchaV2 = () => {
      if (window.grecaptcha && window.grecaptcha.render && recaptchaRef.current) {
        try {
          // Check if already rendered
          if (widgetId.current !== null) {
            window.grecaptcha.reset(widgetId.current);
            return;
          }

          // Check if the container already has a widget
          if (recaptchaRef.current.hasChildNodes()) {
            return;
          }

          widgetId.current = window.grecaptcha.render(recaptchaRef.current, {
            sitekey: recaptchaSiteKey,
            callback: onVerify,
            'expired-callback': onExpired || (() => {}),
            'error-callback': onError || (() => {}),
          });
        } catch (error) {
        }
      }
    };

    const loadRecaptchaV3 = () => {
      if (window.grecaptcha && window.grecaptcha.ready) {
        try {
          window.grecaptcha.ready(() => {
            window.grecaptcha.execute(recaptchaSiteKey, { action: 'submit' })
              .then((token: string) => {
                onVerify(token);
              })
              .catch((error: any) => {
                if (onError) onError();
              });
          });
        } catch (error) {
        }
      }
    };

    if (window.grecaptcha && window.grecaptcha.render) {
      loadRecaptcha();
    } else {
      // Check if script already exists
      const existingScript = document.querySelector('script[src*="google.com/recaptcha"]');
      if (existingScript) {
        window.onRecaptchaLoad = loadRecaptcha;
        return;
      }

      window.onRecaptchaLoad = loadRecaptcha;
      const script = document.createElement('script');
      script.src = `https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoad&render=${recaptchaVersion === 'v3' ? recaptchaSiteKey : 'explicit'}`;
      script.async = true;
      script.defer = true;
      script.onerror = () => {
        if (onError) onError();
      };
      document.head.appendChild(script);
    }

    return () => {
      try {
        if (widgetId.current !== null && window.grecaptcha && window.grecaptcha.reset) {
          window.grecaptcha.reset(widgetId.current);
        }
      } catch (error) {
      }
    };
  }, [recaptchaEnabled, recaptchaVersion, recaptchaSiteKey]);

  if (!recaptchaEnabled || !recaptchaSiteKey) {
    return null;
  }

  return recaptchaVersion === 'v2' ? <div ref={recaptchaRef}></div> : null;
}
