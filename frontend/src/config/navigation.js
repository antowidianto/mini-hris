import { ADMIN_HR_ROLES, ROLES } from '@/config/roles'

export const routeAccess = {
  dashboard: [],
  employees: ADMIN_HR_ROLES,
  contracts: ADMIN_HR_ROLES,
  documents: ADMIN_HR_ROLES,
  attendance: [ROLES.EMPLOYEE],
  attendanceReport: ADMIN_HR_ROLES,
  leaveRequests: [ROLES.EMPLOYEE],
  leaveApprovals: [ROLES.ADMIN, ROLES.HR, ROLES.EMPLOYEE],
  payroll: ADMIN_HR_ROLES,
  payslips: [ROLES.EMPLOYEE],
  importExport: ADMIN_HR_ROLES,
  reports: ADMIN_HR_ROLES,
  companySettings: [ROLES.ADMIN],
  notifications: [],
  auditLogs: [ROLES.ADMIN],
}

export const navigationGroups = [
  {
    label: 'Overview',
    items: [{ label: 'Dashboard', to: '/', access: routeAccess.dashboard, icon: 'dashboard' }],
  },
  {
    label: 'People',
    items: [
      { label: 'Employees', to: '/employees', access: routeAccess.employees, icon: 'people' },
      { label: 'Contracts', to: '/contracts', access: routeAccess.contracts, icon: 'briefcase' },
      { label: 'Documents', to: '/documents', access: routeAccess.documents, icon: 'document' },
    ],
  },
  {
    label: 'Time',
    items: [
      { label: 'My Attendance', to: '/attendance', access: routeAccess.attendance, icon: 'clock' },
      {
        label: 'Attendance Report',
        to: '/attendance/report',
        access: routeAccess.attendanceReport,
        icon: 'chart',
      },
    ],
  },
  {
    label: 'Leave',
    items: [
      { label: 'My Leave', to: '/leaves', access: routeAccess.leaveRequests, icon: 'calendar' },
      { label: 'Leave Approvals', to: '/leaves/approvals', access: routeAccess.leaveApprovals, icon: 'check-circle' },
    ],
  },
  {
    label: 'Payroll',
    items: [
      { label: 'Payroll', to: '/payroll', access: routeAccess.payroll, icon: 'wallet' },
      { label: 'Payslips', to: '/payslips', access: routeAccess.payslips, icon: 'document' },
    ],
  },
  {
    label: 'System',
    items: [
      { label: 'Reports', to: '/reports', access: routeAccess.reports, icon: 'chart' },
      { label: 'Import & Export', to: '/import-export', access: routeAccess.importExport, icon: 'briefcase' },
      { label: 'Notifications', to: '/notifications', access: routeAccess.notifications, icon: 'bell' },
      { label: 'Company Settings', to: '/company-settings', access: routeAccess.companySettings, icon: 'settings' },
      { label: 'Audit Logs', to: '/audit-logs', access: routeAccess.auditLogs, icon: 'document' },
    ],
  },
]
