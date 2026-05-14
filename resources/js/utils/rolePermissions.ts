// utils/rolePermissions.ts
export const getModulesFromNavigation = (userRole: string): string[] => {
  const superAdminModules = [
    'dashboard',
    'companies',
    'nfc_cards',
    'nfc_card_order_requests',
    'campaigns',
    'plans',
    'plan_requests',
    'plan_orders',
    'domain_requests',
    'currencies',
    'referral',
    'settings'
  ];

  const companyModules = [
    'dashboard',
    'users',
    'roles',
    'contacts',
    'appointments',
    'nfc_cards',
    'campaigns',
    'plans',
    'referral',
    'settings'
  ];

  return (userRole === 'superadmin' || userRole === 'super admin') 
    ? superAdminModules 
    : companyModules;
};

export const filterPermissionsByRole = (permissions: Record<string, any[]>, userRole: string): Record<string, any[]> => {
  const allowedModules = getModulesFromNavigation(userRole);
  const filteredPermissions: Record<string, any[]> = {};

  Object.keys(permissions).forEach(module => {
    if (allowedModules.includes(module)) {
      filteredPermissions[module] = permissions[module];
    }
  });

  return filteredPermissions;
};