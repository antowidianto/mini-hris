import { createRouter, createWebHistory } from 'vue-router'

import { routeAccess } from '@/config/navigation'
import AppShell from '@/layouts/AppShell.vue'
import { useAuthStore } from '@/stores/authStore'
import AuditLogView from '@/views/audit/AuditLogView.vue'
import AttendanceReportView from '@/views/attendance/AttendanceReportView.vue'
import AttendanceView from '@/views/attendance/AttendanceView.vue'
import ContractMonitoringView from '@/views/contracts/ContractMonitoringView.vue'
import DashboardView from '@/views/DashboardView.vue'
import DocumentManagementView from '@/views/documents/DocumentManagementView.vue'
import EmployeeDetailView from '@/views/employees/EmployeeDetailView.vue'
import EmployeeFormView from '@/views/employees/EmployeeFormView.vue'
import EmployeeListView from '@/views/employees/EmployeeListView.vue'
import ForbiddenView from '@/views/ForbiddenView.vue'
import ImportExportView from '@/views/import-export/ImportExportView.vue'
import CompanySettingsView from '@/views/settings/CompanySettingsView.vue'
import LeaveApprovalView from '@/views/leaves/LeaveApprovalView.vue'
import LeaveRequestView from '@/views/leaves/LeaveRequestView.vue'
import LoginView from '@/views/LoginView.vue'
import NotFoundView from '@/views/NotFoundView.vue'
import NotificationListView from '@/views/notifications/NotificationListView.vue'
import PayrollDetailView from '@/views/payroll/PayrollDetailView.vue'
import PayrollListView from '@/views/payroll/PayrollListView.vue'
import PayslipListView from '@/views/payroll/PayslipListView.vue'
import OperationalReportsView from '@/views/reports/OperationalReportsView.vue'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: {
      public: true,
    },
  },
  {
    path: '/',
    component: AppShell,
    children: [
      {
        path: '',
        name: 'dashboard',
        component: DashboardView,
        meta: {
          requiresAuth: true,
          label: 'Dashboard',
          roles: routeAccess.dashboard,
        },
      },
      {
        path: 'employees',
        name: 'employees',
        component: EmployeeListView,
        meta: {
          requiresAuth: true,
          label: 'Employees',
          roles: routeAccess.employees,
        },
      },
      {
        path: 'employees/new',
        name: 'employees-create',
        component: EmployeeFormView,
        meta: {
          requiresAuth: true,
          label: 'Add Employee',
          roles: routeAccess.employees,
        },
      },
      {
        path: 'employees/:id',
        name: 'employees-detail',
        component: EmployeeDetailView,
        meta: {
          requiresAuth: true,
          label: 'Employee Detail',
          roles: routeAccess.employees,
        },
      },
      {
        path: 'employees/:id/edit',
        name: 'employees-edit',
        component: EmployeeFormView,
        meta: {
          requiresAuth: true,
          label: 'Edit Employee',
          roles: routeAccess.employees,
        },
      },
      {
        path: 'contracts',
        name: 'contracts',
        component: ContractMonitoringView,
        meta: {
          requiresAuth: true,
          label: 'Contract Monitoring',
          roles: routeAccess.contracts,
        },
      },
      {
        path: 'documents',
        name: 'documents',
        component: DocumentManagementView,
        meta: {
          requiresAuth: true,
          label: 'Documents',
          roles: routeAccess.documents,
        },
      },
      {
        path: 'attendance',
        name: 'attendance',
        component: AttendanceView,
        meta: {
          requiresAuth: true,
          label: 'My Attendance',
          roles: routeAccess.attendance,
        },
      },
      {
        path: 'attendance/report',
        name: 'attendance-report',
        component: AttendanceReportView,
        meta: {
          requiresAuth: true,
          label: 'Attendance Report',
          roles: routeAccess.attendanceReport,
        },
      },
      {
        path: 'leaves',
        name: 'leave-requests',
        component: LeaveRequestView,
        meta: {
          requiresAuth: true,
          label: 'My Leave',
          roles: routeAccess.leaveRequests,
        },
      },
      {
        path: 'leaves/approvals',
        name: 'leave-approvals',
        component: LeaveApprovalView,
        meta: {
          requiresAuth: true,
          label: 'Leave Approvals',
          roles: routeAccess.leaveApprovals,
        },
      },
      {
        path: 'payroll',
        name: 'payroll',
        component: PayrollListView,
        meta: {
          requiresAuth: true,
          label: 'Payroll',
          roles: routeAccess.payroll,
        },
      },
      {
        path: 'payroll/:id',
        name: 'payroll-detail',
        component: PayrollDetailView,
        props: {
          mode: 'payroll',
        },
        meta: {
          requiresAuth: true,
          label: 'Payroll Detail',
          roles: routeAccess.payroll,
        },
      },
      {
        path: 'payslips',
        name: 'payslips',
        component: PayslipListView,
        meta: {
          requiresAuth: true,
          label: 'Payslips',
          roles: routeAccess.payslips,
        },
      },
      {
        path: 'payslips/:id',
        name: 'payslip-detail',
        component: PayrollDetailView,
        props: {
          mode: 'payslip',
        },
        meta: {
          requiresAuth: true,
          label: 'Payslip Detail',
          roles: routeAccess.payslips,
        },
      },
      {
        path: 'import-export',
        name: 'import-export',
        component: ImportExportView,
        meta: {
          requiresAuth: true,
          label: 'Import & Export',
          roles: routeAccess.importExport,
        },
      },
      {
        path: 'reports',
        name: 'reports',
        component: OperationalReportsView,
        meta: {
          requiresAuth: true,
          label: 'Reports',
          roles: routeAccess.reports,
        },
      },
      {
        path: 'company-settings',
        name: 'company-settings',
        component: CompanySettingsView,
        meta: {
          requiresAuth: true,
          label: 'Company Settings',
          roles: routeAccess.companySettings,
        },
      },
      {
        path: 'notifications',
        name: 'notifications',
        component: NotificationListView,
        meta: {
          requiresAuth: true,
          label: 'Notifications',
          roles: routeAccess.notifications,
        },
      },
      {
        path: 'audit-logs',
        name: 'audit-logs',
        component: AuditLogView,
        meta: {
          requiresAuth: true,
          label: 'Audit Logs',
          roles: routeAccess.auditLogs,
        },
      },
      {
        path: 'forbidden',
        name: 'forbidden',
        component: ForbiddenView,
        meta: {
          requiresAuth: true,
          label: 'Access Restricted',
        },
      },
      {
        path: ':pathMatch(.*)*',
        name: 'app-not-found',
        component: NotFoundView,
        meta: {
          requiresAuth: true,
          label: 'Page Not Found',
        },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: NotFoundView,
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.public && auth.isAuthenticated) {
    if (!auth.user) {
      await auth.fetchUser().catch(() => auth.clearSession())
    }

    if (auth.isAuthenticated) {
      return { name: 'dashboard' }
    }
  }

  if (!to.meta.requiresAuth) {
    return true
  }

  if (!auth.isAuthenticated) {
    return {
      name: 'login',
      query: { redirect: to.fullPath },
    }
  }

  if (!auth.user) {
    try {
      await auth.fetchUser()
    } catch {
      auth.clearSession()

      return {
        name: 'login',
        query: { redirect: to.fullPath },
      }
    }
  }

  if (!auth.canAccess(to.meta.roles)) {
    return { name: 'forbidden' }
  }

  return true
})

export default router
