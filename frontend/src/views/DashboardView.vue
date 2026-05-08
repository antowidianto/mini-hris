<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import MetricTile from '@/components/MetricTile.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { ADMIN_HR_ROLES, ROLES } from '@/config/roles'
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
const expiringContracts = computed(() => contractExpiry.value.preview ?? [])
const payrollCompletion = computed(() => Number(payrollReadiness.value.completion_percent ?? 0))
const payrollCompletionWidth = computed(() => `${Math.min(100, Math.max(0, payrollCompletion.value))}%`)
const canUseReports = computed(() => ADMIN_HR_ROLES.includes(auth.role))

const adminMetricTiles = computed(() => [
  {
    label: 'Total Employees',
    value: metrics.value.total_employees ?? 0,
    detail: `${metrics.value.active_employees ?? 0} active`,
    tone: 'blue',
    to: '/employees',
  },
  {
    label: 'Attendance Today',
    value: metrics.value.attendance_today ?? 0,
    detail: `${attendanceToday.value.not_recorded ?? 0} not recorded`,
    tone: 'green',
    to: '/attendance/report',
  },
  {
    label: 'Approval Queue',
    value: metrics.value.pending_leave_requests ?? 0,
    detail: `${metrics.value.pending_supervisor_approvals ?? 0} supervisor / ${metrics.value.pending_hr_approvals ?? 0} HR`,
    tone: 'amber',
    to: '/leaves/approvals',
  },
  {
    label: 'Contracts 60 Days',
    value: metrics.value.contracts_expiring_60_days ?? 0,
    detail: `${metrics.value.contracts_expiring_30_days ?? 0} due in 30 days`,
    tone: 'red',
    to: '/contracts',
  },
])

const attendanceTiles = computed(() => [
  { label: 'Recorded', value: attendanceToday.value.recorded ?? 0, detail: `${attendanceToday.value.active_employees ?? 0} active`, tone: 'blue' },
  { label: 'Present', value: attendanceToday.value.present ?? 0, tone: 'green' },
  { label: 'Late', value: attendanceToday.value.late ?? 0, tone: 'amber' },
  { label: 'Leave', value: attendanceToday.value.leave ?? 0, tone: 'blue' },
  { label: 'Sick', value: attendanceToday.value.sick ?? 0, tone: 'violet' },
  { label: 'Permission', value: attendanceToday.value.permission ?? 0, tone: 'blue' },
  { label: 'Alpha', value: attendanceToday.value.alpha ?? 0, tone: 'red' },
  { label: 'Not Recorded', value: attendanceToday.value.not_recorded ?? 0, tone: 'slate' },
])

const employeeMetricTiles = computed(() => [
  {
    label: 'Attendance Today',
    value: attendanceLabel(metrics.value.attendance_today),
    detail: `${metrics.value.attendance_today?.time_in ?? '--'} to ${metrics.value.attendance_today?.time_out ?? '--'}`,
    tone: metrics.value.attendance_today ? 'green' : 'amber',
    to: '/attendance',
  },
  {
    label: 'Remaining Leave',
    value: `${metrics.value.remaining_leave_balance ?? 0} days`,
    detail: 'Current year balance',
    tone: 'blue',
    to: '/leaves',
  },
  {
    label: 'Latest Leave',
    value: metrics.value.latest_leave_request?.status ?? '--',
    detail: metrics.value.latest_leave_request?.leave_type?.name ?? 'No request yet',
    tone: metrics.value.latest_leave_request?.status === 'rejected' ? 'red' : 'amber',
    to: '/leaves',
  },
  {
    label: 'Latest Payslip',
    value: metrics.value.latest_payslip ? currency(metrics.value.latest_payslip.take_home_pay ?? metrics.value.latest_payslip.net_salary) : '--',
    detail: metrics.value.latest_payslip?.period_label ?? 'No payslip yet',
    tone: 'green',
    to: '/payslips',
  },
])

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

function percent(value) {
  return `${Number(value ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 1 })}%`
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
    <PageHeader
      eyebrow="Workspace"
      title="Dashboard"
      :description="`Welcome back, ${auth.user?.name ?? 'team'}. Workforce operations overview.`"
    >
      <template #actions>
        <div class="flex gap-2">
          <RouterLink v-if="canUseReports" to="/reports" class="rounded-md border border-hris-border px-3 py-1.5 text-sm font-medium text-hris-ink hover:bg-hris-surface">
            Reports
          </RouterLink>
          <RouterLink to="/notifications" class="rounded-md bg-hris-primary px-3 py-1.5 text-sm font-semibold text-white hover:bg-hris-primary-dark">
            Notifications
          </RouterLink>
        </div>
      </template>
    </PageHeader>

    <div v-if="loading" class="mt-4 rounded-md border border-hris-border bg-hris-panel">
      <LoadingState label="Loading dashboard..." />
    </div>

    <div v-else-if="error" class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>

    <div v-else-if="dashboard && !isEmployeeDashboard" class="mt-4 space-y-4">
      <section>
        <div class="mb-2 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-hris-ink">General</h3>
          <span class="text-xs text-hris-muted">Live company snapshot</span>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <MetricTile
            v-for="tile in adminMetricTiles"
            :key="tile.label"
            :label="tile.label"
            :value="tile.value"
            :detail="tile.detail"
            :tone="tile.tone"
            :to="tile.to"
          />
        </div>
      </section>

      <section>
        <div class="mb-2 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-hris-ink">Attendance Today</h3>
          <RouterLink to="/attendance/report" class="text-xs font-semibold text-hris-primary hover:underline">Open report</RouterLink>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <MetricTile
            v-for="tile in attendanceTiles"
            :key="tile.label"
            :label="tile.label"
            :value="tile.value"
            :detail="tile.detail"
            :tone="tile.tone"
          />
        </div>
      </section>

      <div class="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
        <section class="rounded-md border border-hris-border bg-hris-panel p-4">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="text-sm font-semibold">Payroll Readiness</h3>
              <p class="mt-0.5 text-xs text-hris-muted">{{ payrollReadiness.period_label ?? '--' }}</p>
            </div>
            <RouterLink to="/payroll" class="rounded-md border border-hris-border px-3 py-1.5 text-sm font-medium hover:bg-hris-surface">
              Payroll
            </RouterLink>
          </div>

          <dl class="mt-4 grid grid-cols-3 gap-3 text-sm">
            <div class="rounded-md bg-blue-50 px-3 py-2">
              <dt class="text-xs text-hris-muted">Generated</dt>
              <dd class="mt-1 text-xl font-semibold">{{ payrollReadiness.generated_count ?? 0 }}</dd>
            </div>
            <div class="rounded-md bg-amber-50 px-3 py-2">
              <dt class="text-xs text-hris-muted">Missing</dt>
              <dd class="mt-1 text-xl font-semibold">{{ payrollReadiness.missing_count ?? 0 }}</dd>
            </div>
            <div class="rounded-md bg-emerald-50 px-3 py-2">
              <dt class="text-xs text-hris-muted">Complete</dt>
              <dd class="mt-1 text-xl font-semibold">{{ percent(payrollCompletion) }}</dd>
            </div>
          </dl>

          <div class="mt-4 h-2 overflow-hidden rounded-full bg-hris-surface">
            <div class="h-full rounded-full bg-hris-primary" :style="{ width: payrollCompletionWidth }"></div>
          </div>
        </section>

        <section class="ui-table-card overflow-hidden rounded-md border border-hris-border bg-hris-panel">
          <div class="flex items-center justify-between border-b border-hris-border px-4 py-3">
            <div>
              <h3 class="text-sm font-semibold">Expiring Contracts</h3>
              <p class="mt-0.5 text-xs text-hris-muted">{{ metrics.contracts_expiring_60_days ?? 0 }} contracts within 60 days</p>
            </div>
            <RouterLink to="/contracts" class="text-xs font-semibold text-hris-primary hover:underline">View all</RouterLink>
          </div>
          <EmptyState v-if="expiringContracts.length === 0" title="No contracts expiring soon" />
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-hris-border text-sm">
              <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
                <tr>
                  <th class="font-semibold">Employee</th>
                  <th class="font-semibold">Type</th>
                  <th class="font-semibold">End Date</th>
                  <th class="font-semibold">Days</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-hris-border">
                <tr v-for="contract in expiringContracts" :key="contract.id">
                  <td>
                    <p class="font-medium">{{ contract.full_name }}</p>
                    <p class="text-xs text-hris-muted">{{ contract.employee_id }}</p>
                  </td>
                  <td>{{ contract.employment_type }}</td>
                  <td>{{ contract.contract_end_date }}</td>
                  <td>
                    <StatusBadge :status="contract.days_remaining <= 30 ? 'warning' : 'info'" :label="`${contract.days_remaining} days`" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <section>
        <div class="mb-2 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-hris-ink">Quick Actions</h3>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <MetricTile label="Add Employee" value="New" detail="Create employee profile" tone="blue" to="/employees/new" />
          <MetricTile label="Review Leave" :value="metrics.pending_leave_requests ?? 0" detail="Pending decisions" tone="amber" to="/leaves/approvals" />
          <MetricTile label="Generate Payroll" :value="payrollReadiness.missing_count ?? 0" detail="Missing payroll rows" tone="green" to="/payroll" />
          <MetricTile label="Operational Reports" value="Open" detail="Attendance, leave, payroll" tone="violet" to="/reports" />
        </div>
      </section>
    </div>

    <div v-else-if="dashboard && isEmployeeDashboard" class="mt-4 space-y-4">
      <div
        v-if="!metrics.has_employee_profile"
        class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
      >
        Your user account is not linked to an employee profile yet.
      </div>

      <section>
        <div class="mb-2 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-hris-ink">My Snapshot</h3>
          <span class="text-xs text-hris-muted">Today</span>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <MetricTile
            v-for="tile in employeeMetricTiles"
            :key="tile.label"
            :label="tile.label"
            :value="tile.value"
            :detail="tile.detail"
            :tone="tile.tone"
            :to="tile.to"
          />
          <MetricTile
            v-if="metrics.pending_supervisor_approvals > 0"
            label="Team Leave"
            :value="metrics.pending_supervisor_approvals"
            detail="Waiting for your approval"
            tone="amber"
            to="/leaves/approvals"
          />
        </div>
      </section>

      <section>
        <div class="mb-2 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-hris-ink">Quick Actions</h3>
        </div>
        <div class="grid gap-3 lg:grid-cols-3">
          <MetricTile label="Record Attendance" value="Clock" detail="Clock in or clock out" tone="green" to="/attendance" />
          <MetricTile label="Request Leave" value="Submit" detail="Create and track requests" tone="blue" to="/leaves" />
          <MetricTile label="View Payslips" value="Open" detail="Latest salary records" tone="violet" to="/payslips" />
        </div>
      </section>
    </div>
  </section>
</template>
