<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import { ROLES } from '@/config/roles'
import { getDashboard } from '@/services/dashboard'
import { useAuthStore } from '@/stores/authStore'

const auth = useAuthStore()
const dashboard = ref(null)
const loading = ref(false)
const error = ref(null)

const metrics = computed(() => dashboard.value?.metrics ?? {})
const isEmployeeDashboard = computed(() => dashboard.value?.role === ROLES.EMPLOYEE)
const attendanceToday = computed(() => metrics.value.attendance_today_breakdown ?? {})
const payrollReadiness = computed(() => metrics.value.payroll_readiness ?? {})
const contractExpiry = computed(() => metrics.value.contract_expiry ?? {})

function currency(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(value ?? 0))
}

function attendanceLabel(attendance) {
  if (!attendance) {
    return 'Not clocked in'
  }

  return attendance.status
}

async function loadDashboard() {
  loading.value = true
  error.value = null

  try {
    dashboard.value = await getDashboard()
  } catch {
    error.value = 'Unable to load dashboard metrics'
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>

<template>
  <section class="mx-auto max-w-7xl">
    <div class="border-b border-hris-border pb-5">
      <h2 class="text-2xl font-semibold">Dashboard</h2>
      <p class="mt-1 text-sm text-hris-muted">
        Welcome back, {{ auth.user?.name }}. Workforce operations overview.
      </p>
    </div>

    <div v-if="loading" class="mt-6 rounded-md border border-hris-border bg-hris-panel p-5 text-sm text-hris-muted">
      Loading dashboard...
    </div>

    <div v-else-if="error" class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>

    <div v-else-if="dashboard && !isEmployeeDashboard">
      <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <RouterLink to="/employees" class="rounded-md border border-hris-border bg-hris-panel p-4 hover:border-hris-primary">
          <p class="text-sm text-hris-muted">Total Employees</p>
          <p class="mt-2 text-2xl font-semibold">{{ metrics.total_employees }}</p>
        </RouterLink>
        <RouterLink to="/employees" class="rounded-md border border-hris-border bg-hris-panel p-4 hover:border-hris-primary">
          <p class="text-sm text-hris-muted">Active Employees</p>
          <p class="mt-2 text-2xl font-semibold">{{ metrics.active_employees }}</p>
        </RouterLink>
        <RouterLink to="/attendance/report" class="rounded-md border border-hris-border bg-hris-panel p-4 hover:border-hris-primary">
          <p class="text-sm text-hris-muted">Attendance Today</p>
          <p class="mt-2 text-2xl font-semibold">{{ metrics.attendance_today }}</p>
          <p class="mt-1 text-xs text-hris-muted">{{ attendanceToday.not_recorded ?? 0 }} not recorded</p>
        </RouterLink>
        <RouterLink to="/leaves/approvals" class="rounded-md border border-hris-border bg-hris-panel p-4 hover:border-hris-primary">
          <p class="text-sm text-hris-muted">Approval Queue</p>
          <p class="mt-2 text-2xl font-semibold">{{ metrics.pending_leave_requests }}</p>
          <p class="mt-1 text-xs text-hris-muted">
            {{ metrics.pending_supervisor_approvals }} supervisor / {{ metrics.pending_hr_approvals }} HR
          </p>
        </RouterLink>
      </div>

      <div class="mt-5 grid gap-5 lg:grid-cols-[1.2fr_1fr]">
        <section class="rounded-md border border-hris-border bg-hris-panel p-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="font-semibold">Payroll Readiness</h3>
              <p class="mt-1 text-sm text-hris-muted">{{ payrollReadiness.period_label ?? '--' }}</p>
            </div>
            <RouterLink to="/payroll" class="rounded-md border border-hris-border px-3 py-2 text-sm font-medium hover:bg-hris-surface">
              Payroll
            </RouterLink>
          </div>
          <div class="mt-5 grid gap-4 sm:grid-cols-3">
            <div>
              <p class="text-xs uppercase text-hris-muted">Generated</p>
              <p class="mt-1 text-xl font-semibold">{{ payrollReadiness.generated_count ?? 0 }}</p>
            </div>
            <div>
              <p class="text-xs uppercase text-hris-muted">Missing</p>
              <p class="mt-1 text-xl font-semibold">{{ payrollReadiness.missing_count ?? 0 }}</p>
            </div>
            <div>
              <p class="text-xs uppercase text-hris-muted">Completion</p>
              <p class="mt-1 text-xl font-semibold">{{ payrollReadiness.completion_percent ?? 0 }}%</p>
            </div>
          </div>
          <div class="mt-5 h-2 overflow-hidden rounded-full bg-hris-surface">
            <div class="h-full rounded-full bg-hris-primary" :style="{ width: `${payrollReadiness.completion_percent ?? 0}%` }"></div>
          </div>
        </section>

        <section class="rounded-md border border-hris-border bg-hris-panel p-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="font-semibold">Attendance Today</h3>
              <p class="mt-1 text-sm text-hris-muted">{{ attendanceToday.recorded ?? 0 }} of {{ attendanceToday.active_employees ?? 0 }} recorded</p>
            </div>
            <RouterLink to="/attendance/report" class="rounded-md border border-hris-border px-3 py-2 text-sm font-medium hover:bg-hris-surface">
              Report
            </RouterLink>
          </div>
          <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
            <div class="rounded-md bg-hris-surface px-3 py-2">Present: <span class="font-semibold">{{ attendanceToday.present ?? 0 }}</span></div>
            <div class="rounded-md bg-hris-surface px-3 py-2">Late: <span class="font-semibold">{{ attendanceToday.late ?? 0 }}</span></div>
            <div class="rounded-md bg-hris-surface px-3 py-2">Leave: <span class="font-semibold">{{ attendanceToday.leave ?? 0 }}</span></div>
            <div class="rounded-md bg-hris-surface px-3 py-2">Sick: <span class="font-semibold">{{ attendanceToday.sick ?? 0 }}</span></div>
            <div class="rounded-md bg-hris-surface px-3 py-2">Permission: <span class="font-semibold">{{ attendanceToday.permission ?? 0 }}</span></div>
            <div class="rounded-md bg-hris-surface px-3 py-2">Alpha: <span class="font-semibold">{{ attendanceToday.alpha ?? 0 }}</span></div>
          </div>
        </section>
      </div>

      <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_1.4fr]">
        <section class="rounded-md border border-hris-border bg-hris-panel p-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="font-semibold">Contract Risk</h3>
              <p class="mt-1 text-sm text-hris-muted">{{ metrics.contracts_expiring_60_days }} contracts in 60 days</p>
            </div>
            <RouterLink to="/contracts" class="rounded-md border border-hris-border px-3 py-2 text-sm font-medium hover:bg-hris-surface">
              Contracts
            </RouterLink>
          </div>
          <div class="mt-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-md bg-amber-50 px-3 py-3 text-amber-800">
              <p class="text-xs uppercase">30 Days</p>
              <p class="mt-1 text-2xl font-semibold">{{ metrics.contracts_expiring_30_days }}</p>
            </div>
            <div class="rounded-md bg-blue-50 px-3 py-3 text-blue-800">
              <p class="text-xs uppercase">60 Days</p>
              <p class="mt-1 text-2xl font-semibold">{{ metrics.contracts_expiring_60_days }}</p>
            </div>
          </div>
        </section>

        <section class="overflow-hidden rounded-md border border-hris-border bg-hris-panel">
          <div class="border-b border-hris-border px-5 py-4">
            <h3 class="font-semibold">Expiring Contracts</h3>
          </div>
          <div v-if="(contractExpiry.preview ?? []).length === 0" class="p-5 text-sm text-hris-muted">
            No contracts expiring soon.
          </div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-hris-border text-sm">
              <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
                <tr>
                  <th class="px-4 py-3 font-semibold">Employee</th>
                  <th class="px-4 py-3 font-semibold">Type</th>
                  <th class="px-4 py-3 font-semibold">End Date</th>
                  <th class="px-4 py-3 font-semibold">Days</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-hris-border">
                <tr v-for="contract in contractExpiry.preview" :key="contract.id">
                  <td class="px-4 py-3">
                    <p class="font-medium">{{ contract.full_name }}</p>
                    <p class="text-xs text-hris-muted">{{ contract.employee_id }}</p>
                  </td>
                  <td class="px-4 py-3">{{ contract.employment_type }}</td>
                  <td class="px-4 py-3">{{ contract.contract_end_date }}</td>
                  <td class="px-4 py-3">{{ contract.days_remaining }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <div class="mt-6 grid gap-5 lg:grid-cols-4">
        <RouterLink to="/employees/new" class="rounded-md border border-hris-border bg-hris-panel p-5 hover:border-hris-primary">
          <p class="font-semibold">Add Employee</p>
          <p class="mt-1 text-sm text-hris-muted">Create a new employee profile and account link.</p>
        </RouterLink>
        <RouterLink to="/leaves/approvals" class="rounded-md border border-hris-border bg-hris-panel p-5 hover:border-hris-primary">
          <p class="font-semibold">Review Leave</p>
          <p class="mt-1 text-sm text-hris-muted">Process pending leave decisions.</p>
        </RouterLink>
        <RouterLink to="/payroll" class="rounded-md border border-hris-border bg-hris-panel p-4 hover:border-hris-primary">
          <p class="font-semibold">Generate Payroll</p>
          <p class="mt-1 text-sm text-hris-muted">Run payroll for the selected monthly period.</p>
        </RouterLink>
        <RouterLink to="/contracts" class="rounded-md border border-hris-border bg-hris-panel p-4 hover:border-hris-primary">
          <p class="font-semibold">Monitor Contracts</p>
          <p class="mt-1 text-sm text-hris-muted">Review contracts approaching renewal dates.</p>
        </RouterLink>
      </div>
    </div>

    <div v-else-if="dashboard && isEmployeeDashboard">
      <div
        v-if="!metrics.has_employee_profile"
        class="mt-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
      >
        Your user account is not linked to an employee profile yet.
      </div>

      <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <RouterLink to="/attendance" class="rounded-md border border-hris-border bg-hris-panel p-4 hover:border-hris-primary">
          <p class="text-sm text-hris-muted">Attendance Today</p>
          <p class="mt-2 text-xl font-semibold">{{ attendanceLabel(metrics.attendance_today) }}</p>
          <p class="mt-1 text-xs text-hris-muted">
            {{ metrics.attendance_today?.time_in ?? '--' }} to {{ metrics.attendance_today?.time_out ?? '--' }}
          </p>
        </RouterLink>
        <RouterLink to="/leaves" class="rounded-md border border-hris-border bg-hris-panel p-4 hover:border-hris-primary">
          <p class="text-sm text-hris-muted">Remaining Leave</p>
          <p class="mt-2 text-xl font-semibold">{{ metrics.remaining_leave_balance }} days</p>
        </RouterLink>
        <RouterLink to="/leaves" class="rounded-md border border-hris-border bg-hris-panel p-4 hover:border-hris-primary">
          <p class="text-sm text-hris-muted">Latest Leave</p>
          <p class="mt-2 text-xl font-semibold">{{ metrics.latest_leave_request?.status ?? '--' }}</p>
          <p class="mt-1 text-xs text-hris-muted">{{ metrics.latest_leave_request?.leave_type?.name ?? 'No request yet' }}</p>
        </RouterLink>
        <RouterLink to="/payslips" class="rounded-md border border-hris-border bg-hris-panel p-4 hover:border-hris-primary">
          <p class="text-sm text-hris-muted">Latest Payslip</p>
          <p class="mt-2 text-xl font-semibold">{{ metrics.latest_payslip ? currency(metrics.latest_payslip.take_home_pay ?? metrics.latest_payslip.net_salary) : '--' }}</p>
          <p class="mt-1 text-xs text-hris-muted">{{ metrics.latest_payslip?.period_label ?? 'No payslip yet' }}</p>
        </RouterLink>
        <RouterLink
          v-if="metrics.pending_supervisor_approvals > 0"
          to="/leaves/approvals"
          class="rounded-md border border-hris-border bg-hris-panel p-4 hover:border-hris-primary"
        >
          <p class="text-sm text-hris-muted">Team Leave</p>
          <p class="mt-2 text-xl font-semibold">{{ metrics.pending_supervisor_approvals }}</p>
          <p class="mt-1 text-xs text-hris-muted">Waiting for your approval</p>
        </RouterLink>
      </div>

      <div class="mt-6 grid gap-5 lg:grid-cols-3">
        <RouterLink to="/attendance" class="rounded-md border border-hris-border bg-hris-panel p-5 hover:border-hris-primary">
          <p class="font-semibold">Record Attendance</p>
          <p class="mt-1 text-sm text-hris-muted">Clock in or clock out for today.</p>
        </RouterLink>
        <RouterLink to="/leaves" class="rounded-md border border-hris-border bg-hris-panel p-5 hover:border-hris-primary">
          <p class="font-semibold">Request Leave</p>
          <p class="mt-1 text-sm text-hris-muted">Submit a leave request and monitor status.</p>
        </RouterLink>
        <RouterLink to="/payslips" class="rounded-md border border-hris-border bg-hris-panel p-5 hover:border-hris-primary">
          <p class="font-semibold">View Payslips</p>
          <p class="mt-1 text-sm text-hris-muted">Check your latest salary records.</p>
        </RouterLink>
      </div>
    </div>
  </section>
</template>
