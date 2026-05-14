 // Simple route helper for contact messages
if (typeof window.route === 'undefined') {
     window.route = function(name, params = {}) {
         const routes = {
             'contact-messages.index': '/contact-messages',
             'contact-messages.destroy': (id) => `/contact-messages/${id}`,
             'newsletters.index': '/newsletters',
             'newsletters.destroy': (id) => `/newsletters/${id}`,
             'cookie.consent.store': '/cookie-consent/store',
             'cookie.consent.download': '/cookie-consent/download',
             'settings.cookie.update': '/settings/cookie',
         };

         if (typeof routes[name] === 'function') {
             return routes[name](params);
         }

         return routes[name] || '#';
     };
 }
