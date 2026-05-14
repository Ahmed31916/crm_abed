// utils/permissions.ts
import { usePage } from '@inertiajs/react';

/**
 * Check if the current user has a specific permission
 * @param permission The permission name to check
 * @returns boolean indicating if the user has the permission
 */
export const hasPermission = (permission: string): boolean => {
  const { auth } = usePage().props as any;
  
  if (!auth || !auth.user || !auth.permissions) {
    return false;
  }
  
  // Check if user has the specific permission
  return auth.permissions.includes(permission);
};

/**
 * Check if the current user has any of the specified permissions
 * @param permissions Array of permission names to check
 * @returns boolean indicating if the user has any of the permissions
 */
export const hasAnyPermission = (permissions: string[]): boolean => {
  const { auth } = usePage().props as any;
  
  if (!auth || !auth.user || !auth.permissions) {
    return false;
  }
  
  // Check if user has any of the permissions
  return permissions.some(permission => auth.permissions.includes(permission));
};