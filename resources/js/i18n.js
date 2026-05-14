// resources/js/i18n.js
import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import Backend from 'i18next-http-backend';
import LanguageDetector from 'i18next-browser-languagedetector';

// Make i18n instance available for direct imports
export { default as i18next } from 'i18next';

// Custom backend to handle the modified response format
const customBackend = {
  type: 'backend',
  init: function(services, backendOptions) {
    this.services = services;
    this.options = backendOptions;
  },
  read: function(language, namespace, callback) {
    const loadPath = window.route ? window.route('translations', language) : `/translations/${language}`;

    fetch(loadPath)
      .then(response => response.json())
      .then(data => {
        // Extract translations from the structured response
        const translations = data.translations;

        // Set document direction - always keep LTR for sidebar compatibility
        document.documentElement.dir = 'ltr';
        document.documentElement.setAttribute('dir', 'ltr');

        callback(null, translations);
      })
      .catch(error => {
        callback(error, null);
      });
  }
};

// Function to get initial language
const getInitialLanguage = () => {
  // Always return null to let the app.tsx handle language initialization
  // This prevents race conditions with async locale fetching
  return null;
};

// Function to reset language cache when switching languages
const resetLanguageCache = (language) => {
  // Clear any cached translations for better switching
  if (window.localStorage) {
    Object.keys(localStorage).forEach(key => {
      if (key.startsWith('i18next_res_')) {
        localStorage.removeItem(key);
      }
    });
  }
};

// Override the changeLanguage method to reset cache
const originalChangeLanguage = i18n.changeLanguage;
i18n.changeLanguage = function(language) {
  resetLanguageCache(language);
  return originalChangeLanguage.apply(this, arguments);
};

// Initialize i18n
i18n
    .use(customBackend)
    .use(initReactI18next)
    .init({
        lng: undefined,
        fallbackLng: false,
        load: 'currentOnly',
        debug: process.env.NODE_ENV === 'development',

        interpolation: {
            escapeValue: false,
        },

        ns: ['translation'],
        defaultNS: 'translation',

        partialBundledLanguages: true,
        loadOnInitialization: false
    });

// Export the initialized instance
export default i18n;

// Make sure the i18n instance is available for direct imports
window.i18next = i18n;
