export const hasRole = (role: string, userRoles: string[] = []) =>
    userRoles.includes(role);

export const hasPermission = (userPermissions: string[], permission: string) =>
    userPermissions.includes(permission);