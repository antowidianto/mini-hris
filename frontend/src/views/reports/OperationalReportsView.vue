<script setup>
import { onMounted, reactive, ref } from 'vue'

import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { getBranches, getDepartments } from '@/services/employees'
import { getOperationalReport } from '@/services/reports'

const today = new Date()
const monthStart = new Date(today.getFullYear(), today.getMonth(), 1)

function localDate(value) {
  const year = value.getFullYear()
  const month = String(value.getMonth() + 1).padStart(2, '0')
  const day = String(value.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

const filters = reactive({
  date_from: localDate(monthStart),
  date_to: localDate(today),
  branch_id: '',
  department_id: '',
  employment_status: '',
})

const branches = ref([])
const departments = ref([])
const report = ref(null)
const loading = ref(false)
const error = ref(null)

function minutes(value) {
  const total = Number(value ?? 0)
  const hours = Math.floor(total / 60)
  const mins = total % 60

  return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`
}

async function loadLookups() {
  try {
    const [branchData, departmentData] = await Promise.all([getBranches(), getDepartments()])
    branches.value = branchData
    departments.value = departmentData
  } catch {
    error.value = 'Unable to load report filters'
  }
}

async function loadReport() {
  loading.value = true
  error.value = null

  try {
    report.value = await getOperationalReport({
      ...filters,
      branch_id: filters.branch_id || undefined,
      department_id: filters.department_id || undefined,
      employment_status: filters.employment_status || undefined,
    })
  } catch {
    error.value = 'Unable to load reports'
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.date_from = localDate(monthStart)
  filters.date_to = localDate(today)
  filters.branch_id = ''
  filters.department_id = ''
  filters.employment_status = ''
  loadReport()
}

onMounted(async () => {
  await loadLookups()
  await loadReport()
})
</script>

<template>
  <section class="mx-auto max-w-screen-2xl">
    <PageHeader eyebrow="Reports" title="Operational Reports" description="Review attendance, leave, overtime, and headcount across the company." />

    <div v-if="error" class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>

    <form class="ui-filter-bar mt-5 grid gap-3 rounded-md border border-hris-border bg-hris-panel p-4 lg:grid-cols-7" @submit.prevent="loadReport">
      <input v-model="filters.date_from" type="date" class="rounded-md border border-hris-border px-3 py-2 text-sm" aria-label="Date from" />
      <input v-model="filters.date_to" type="date" class="rounded-md border border-hris-border px-3 py-2 text-sm" aria-label="Date to" />
      <select v-model="filters.branch_id" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All branches</option>
        <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
      </select>
      <select v-model="filters.department_id" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All departments</option>
        <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option>
      </select>
      <select v-model="filters.employment_status" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All statuses</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
      <button type="submit" class="rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark">
        Apply
      </button>
      <button type="button" class="rounded-md border border-hris-border px-4 py-2 text-sm font-medium hover:bg-hris-surface" @click="resetFilters">
        Reset
      </button>
    </form>

    <div v-if="loading" class="ui-table-card mt-5 rounded-md border border-hris-border bg-hris-panel">
      <LoadingState label="Loading reports..." />
    </div>

    <template v-else-if="report">
      <div class="mt-5 grid gap-4 md:grid-cols-4">
        <div class="rounded-md border border-hris-border bg-hris-panel p-4">
          <p class="text-sm text-hris-muted">Headcount</p>
          <p class="mt-2 text-2xl font-semibold">{{ report.summary.headcount }}</p>
          <p class="mt-1 text-xs text-hris-muted">{{ report.summary.active_headcount }} active / {{ report.summary.inactive_headcount }} inactive</p>
        </div>
        <div class="rounded-md border border-hris-border bg-hris-panel p-4">
          <p class="text-sm text-hris-muted">Attendance Records</p>
          <p class="mt-2 text-2xl font-semibold">{{ report.summary.attendance_records }}</p>
          <p class="mt-1 text-xs text-hris-muted">{{ report.summary.late_days }} late day(s)</p>
        </div>
        <div class="rounded-md border border-hris-border bg-hris-panel p-4">
          <p class="text-sm text-hris-muted">Overtime</p>
          <p class="mt-2 text-2xl font-semibold">{{ minutes(report.summary.overtime_minutes) }}</p>
          <p class="mt-1 text-xs text-hris-muted">Total approved attendance overtime</p>
        </div>
        <div class="rounded-md border border-hris-border bg-hris-panel p-4">
          <p class="text-sm text-hris-muted">Leave</p>
          <p class="mt-2 text-2xl font-semibold">{{ report.summary.approved_leave_days }}</p>
          <p class="mt-1 text-xs text-hris-muted">{{ report.summary.pending_leave_requests }} pending request(s)</p>
        </div>
      </div>

      <section class="mt-5 rounded-md border border-hris-border bg-hris-panel p-5">
        <h3 class="font-semibold">Attendance Recap</h3>
        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full divide-y divide-hris-border text-sm">
            <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
              <tr>
                <th class="px-3 py-2 font-semibold">Employee</th>
                <th class="px-3 py-2 font-semibold">Branch</th>
                <th class="px-3 py-2 font-semibold">Present</th>
                <th class="px-3 py-2 font-semibold">Late</th>
                <th class="px-3 py-2 font-semibold">Sick</th>
                <th class="px-3 py-2 font-semibold">Permission</th>
                <th class="px-3 py-2 font-semibold">Leave</th>
                <th class="px-3 py-2 font-semibold">Alpha</th>
                <th class="px-3 py-2 font-semibold">Overtime</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-hris-border">
              <tr v-if="report.attendance_recap.length === 0">
                <td colspan="9">
                  <EmptyState title="No attendance recap data found" />
                </td>
              </tr>
              <tr v-for="row in report.attendance_recap" :key="row.employee.id">
                <td class="px-3 py-2">
                  <p class="font-medium">{{ row.employee.full_name }}</p>
                  <p class="text-xs text-hris-muted">{{ row.employee.employee_id }} - {{ row.employee.department ?? '--' }}</p>
                </td>
                <td class="px-3 py-2">{{ row.employee.branch ?? '--' }}</td>
                <td class="px-3 py-2">{{ row.present_days }}</td>
                <td class="px-3 py-2">{{ row.late_days }}</td>
                <td class="px-3 py-2">{{ row.sick_days }}</td>
                <td class="px-3 py-2">{{ row.permission_days }}</td>
                <td class="px-3 py-2">{{ row.leave_days }}</td>
                <td class="px-3 py-2">{{ row.alpha_days }}</td>
                <td class="px-3 py-2">{{ minutes(row.overtime_minutes) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <section class="rounded-md border border-hris-border bg-hris-panel p-5">
          <h3 class="font-semibold">Late Report</h3>
          <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-hris-border text-sm">
              <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
                <tr>
                  <th class="px-3 py-2 font-semibold">Employee</th>
                  <th class="px-3 py-2 font-semibold">Date</th>
                  <th class="px-3 py-2 font-semibold">Time In</th>
                  <th class="px-3 py-2 font-semibold">Late</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-hris-border">
                <tr v-if="report.late_report.length === 0">
                  <td colspan="4">
                    <EmptyState title="No late records found" />
                  </td>
                </tr>
                <tr v-for="row in report.late_report" :key="row.id">
                  <td class="px-3 py-2">{{ row.employee.full_name }}</td>
                  <td class="px-3 py-2">{{ row.attendance_date }}</td>
                  <td class="px-3 py-2">{{ row.time_in ?? '--' }}</td>
                  <td class="px-3 py-2">{{ minutes(row.late_minutes) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="rounded-md border border-hris-border bg-hris-panel p-5">
          <h3 class="font-semibold">Overtime Report</h3>
          <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-hris-border text-sm">
              <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
                <tr>
                  <th class="px-3 py-2 font-semibold">Employee</th>
                  <th class="px-3 py-2 font-semibold">Date</th>
                  <th class="px-3 py-2 font-semibold">Time Out</th>
                  <th class="px-3 py-2 font-semibold">Overtime</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-hris-border">
                <tr v-if="report.overtime_report.length === 0">
                  <td colspan="4">
                    <EmptyState title="No overtime records found" />
                  </td>
                </tr>
                <tr v-for="row in report.overtime_report" :key="row.id">
                  <td class="px-3 py-2">{{ row.employee.full_name }}</td>
                  <td class="px-3 py-2">{{ row.attendance_date }}</td>
                  <td class="px-3 py-2">{{ row.time_out ?? '--' }}</td>
                  <td class="px-3 py-2">{{ minutes(row.overtime_minutes) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <section class="rounded-md border border-hris-border bg-hris-panel p-5">
          <h3 class="font-semibold">Leave Report</h3>
          <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-hris-border text-sm">
              <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
                <tr>
                  <th class="px-3 py-2 font-semibold">Employee</th>
                  <th class="px-3 py-2 font-semibold">Type</th>
                  <th class="px-3 py-2 font-semibold">Dates</th>
                  <th class="px-3 py-2 font-semibold">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-hris-border">
                <tr v-if="report.leave_report.requests.length === 0">
                  <td colspan="4">
                    <EmptyState title="No leave requests found" />
                  </td>
                </tr>
                <tr v-for="row in report.leave_report.requests" :key="row.id">
                  <td class="px-3 py-2">{{ row.employee.full_name }}</td>
                  <td class="px-3 py-2">{{ row.leave_type }}</td>
                  <td class="px-3 py-2">
                    <p>{{ row.start_date }} to {{ row.end_date }}</p>
                    <p class="text-xs text-hris-muted">{{ row.report_days }} day(s) in range</p>
                  </td>
                  <td class="px-3 py-2">
                    <StatusBadge :status="row.status" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="rounded-md border border-hris-border bg-hris-panel p-5">
          <h3 class="font-semibold">Headcount By Branch</h3>
          <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-hris-border text-sm">
              <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
                <tr>
                  <th class="px-3 py-2 font-semibold">Branch</th>
                  <th class="px-3 py-2 font-semibold">Active</th>
                  <th class="px-3 py-2 font-semibold">Inactive</th>
                  <th class="px-3 py-2 font-semibold">Total</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-hris-border">
                <tr v-if="report.headcount_by_branch.length === 0">
                  <td colspan="4">
                    <EmptyState title="No headcount data found" />
                  </td>
                </tr>
                <tr v-for="row in report.headcount_by_branch" :key="row.branch.id ?? 'unassigned'">
                  <td class="px-3 py-2">
                    <p class="font-medium">{{ row.branch.name }}</p>
                    <p class="text-xs text-hris-muted">{{ row.branch.code ?? '--' }}</p>
                  </td>
                  <td class="px-3 py-2">{{ row.active }}</td>
                  <td class="px-3 py-2">{{ row.inactive }}</td>
                  <td class="px-3 py-2 font-semibold">{{ row.total }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </template>
  </section>
</template>
