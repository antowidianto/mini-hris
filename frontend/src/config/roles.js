export const ROLES = {
  ADMIN: 'admin',
  HR: 'hr',
  EMPLOYEE: 'employee',
}

export const ADMIN_HR_ROLES = [ROLES.ADMIN, ROLES.HR]

export function canAccessRole(userRole, allowedRoles = []) {
  return allowedRoles.length === 0 || allowedRoles.includes(userRole)
}
