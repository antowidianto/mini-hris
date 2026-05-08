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
  companySettings: [ROLES.ADMIN],
  notifications: [],
  auditLogs: [ROLES.ADMIN],
}

export const navigationGroups = [
  {
    label: 'Overview',
    items: [{ label: 'Dashboard', to: '/', access: routeAccess.dashboard }],
  },
  {
    label: 'People',
    items: [
      { label: 'Employees', to: '/employees', access: routeAccess.employees },
      { label: 'Contracts', to: '/contracts', access: routeAccess.contracts },
      { label: 'Documents', to: '/documents', access: routeAccess.documents },
    ],
  },
  {
    label: 'Time',
    items: [
      { label: 'My Attendance', to: '/attendance', access: routeAccess.attendance },
      {
        label: 'Attendance Report',
        to: '/attendance/report',
        access: routeAccess.attendanceReport,
      },
    ],
  },
  {
    label: 'Leave',
    items: [
      { label: 'My Leave', to: '/leaves', access: routeAccess.leaveRequests },
      { label: 'Leave Approvals', to: '/leaves/approvals', access: routeAccess.leaveApprovals },
    ],
  },
  {
    label: 'Payroll',
    items: [
      { label: 'Payroll', to: '/payroll', access: routeAccess.payroll },
      { label: 'Payslips', to: '/payslips', access: routeAccess.payslips },
    ],
  },
  {
    label: 'System',
    items: [
      { label: 'Notifications', to: '/notifications', access: routeAccess.notifications },
      { label: 'Company Settings', to: '/company-settings', access: routeAccess.companySettings },
      { label: 'Audit Logs', to: '/audit-logs', access: routeAccess.auditLogs },
    ],
  },
]
