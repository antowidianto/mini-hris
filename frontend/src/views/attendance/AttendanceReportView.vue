<script setup>
import { onMounted, reactive, ref } from 'vue'

import {
  getAttendanceReport,
  getMonthlyAttendanceRecap,
  importAttendancePlaceholder,
} from '@/services/attendance'
import { getDepartments, getEmployees } from '@/services/employees'

const filters = reactive({
  date_from: '',
  date_to: '',
  employee_id: '',
  department_id: '',
  status: '',
  attendance_source: '',
  per_page: 10,
  page: 1,
})

const attendances = ref([])
const recap = ref([])
const departments = ref([])
const employees = ref([])
const meta = ref(null)
const loading = ref(false)
const recapLoading = ref(false)
const importing = ref(false)
const error = ref(null)
const importSuccess = ref(null)
const importForm = reactive({
  file_name: '',
  source: 'fingerprint',
  notes: '',
})

function statusClass(status) {
  return {
    present: 'bg-emerald-50 text-emerald-700',
    late: 'bg-amber-50 text-amber-700',
    absent: 'bg-red-50 text-red-700',
    leave: 'bg-blue-50 text-blue-700',
    sick: 'bg-purple-50 text-purple-700',
    permission: 'bg-sky-50 text-sky-700',
    alpha: 'bg-red-50 text-red-700',
  }[status] ?? 'bg-slate-100 text-slate-600'
}

function currentYearMonth() {
  const dateFrom = filters.date_from ? new Date(`${filters.date_from}T00:00:00`) : new Date()

  return {
    year: dateFrom.getFullYear(),
    month: dateFrom.getMonth() + 1,
  }
}

async function loadLookups() {
  try {
    departments.value = await getDepartments()
    const employeeData = await getEmployees({ per_page: 50, employment_status: 'active' })
    employees.value = employeeData.employees
  } catch {
    error.value = 'Unable to load report filters'
  }
}

async function loadReport(page = 1) {
  loading.value = true
  error.value = null
  filters.page = page

  try {
    const data = await getAttendanceReport(filters)
    attendances.value = data.attendances
    meta.value = data.meta
  } catch {
    error.value = 'Unable to load attendance report'
  } finally {
    loading.value = false
  }
}

async function loadRecap() {
  recapLoading.value = true
  error.value = null

  try {
    const { year, month } = currentYearMonth()
    recap.value = await getMonthlyAttendanceRecap({
      year,
      month,
      employee_id: filters.employee_id || undefined,
      department_id: filters.department_id || undefined,
    })
  } catch {
    error.value = 'Unable to load monthly recap'
  } finally {
    recapLoading.value = false
  }
}

async function applyFilters() {
  await Promise.all([loadReport(1), loadRecap()])
}

async function submitImportPlaceholder() {
  importing.value = true
  error.value = null
  importSuccess.value = null

  try {
    const result = await importAttendancePlaceholder({
      file_name: importForm.file_name,
      source: importForm.source,
      notes: importForm.notes || null,
    })
    importSuccess.value = `${result.file_name} accepted as ${result.status}.`
    importForm.file_name = ''
    importForm.notes = ''
  } catch {
    error.value = 'Unable to submit import placeholder'
  } finally {
    importing.value = false
  }
}

function resetFilters() {
  filters.date_from = ''
  filters.date_to = ''
  filters.employee_id = ''
  filters.department_id = ''
  filters.status = ''
  filters.attendance_source = ''
  filters.per_page = 10
  applyFilters()
}

onMounted(async () => {
  await Promise.all([loadLookups(), loadReport(), loadRecap()])
})
</script>

<template>
  <section class="mx-auto max-w-7xl">
    <div class="border-b border-hris-border pb-5">
      <p class="text-xs font-semibold uppercase text-hris-accent">Time</p>
      <h2 class="mt-1 text-2xl font-semibold">Attendance Report</h2>
      <p class="mt-1 text-sm text-hris-muted">Review attendance records across employees and teams.</p>
    </div>

    <div v-if="error" class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>

    <form class="mt-5 grid gap-3 rounded-md border border-hris-border bg-hris-panel p-4 lg:grid-cols-8" @submit.prevent="applyFilters">
      <input
        v-model="filters.date_from"
        type="date"
        class="rounded-md border border-hris-border px-3 py-2 text-sm"
        aria-label="Date from"
      />
      <input
        v-model="filters.date_to"
        type="date"
        class="rounded-md border border-hris-border px-3 py-2 text-sm"
        aria-label="Date to"
      />
      <select v-model="filters.department_id" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All departments</option>
        <option v-for="department in departments" :key="department.id" :value="department.id">
          {{ department.name }}
        </option>
      </select>
      <select v-model="filters.employee_id" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All employees</option>
        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
          {{ employee.full_name }}
        </option>
      </select>
      <select v-model="filters.status" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All statuses</option>
        <option value="present">Present</option>
        <option value="late">Late</option>
        <option value="absent">Absent</option>
        <option value="alpha">Alpha</option>
        <option value="sick">Sick</option>
        <option value="permission">Permission</option>
        <option value="leave">Leave</option>
      </select>
      <select v-model="filters.attendance_source" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All sources</option>
        <option value="manual">Manual</option>
        <option value="fingerprint">Fingerprint</option>
        <option value="import">Import</option>
      </select>
      <button
        type="submit"
        class="rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark"
      >
        Apply
      </button>
      <button
        type="button"
        class="rounded-md border border-hris-border px-4 py-2 text-sm font-medium hover:bg-hris-surface"
        @click="resetFilters"
      >
        Reset
      </button>
    </form>

    <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_360px]">
      <div class="rounded-md border border-hris-border bg-hris-panel p-4">
        <h3 class="font-semibold">Monthly Attendance Recap</h3>
        <div v-if="recapLoading" class="mt-4 text-sm text-hris-muted">Loading recap...</div>
        <div v-else-if="recap.length === 0" class="mt-4 text-sm text-hris-muted">No monthly recap records found.</div>
        <div v-else class="mt-4 overflow-x-auto">
          <table class="min-w-full divide-y divide-hris-border text-sm">
            <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
              <tr>
                <th class="px-3 py-2 font-semibold">Employee</th>
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
              <tr v-for="row in recap" :key="row.employee.id">
                <td class="px-3 py-2">
                  <p class="font-medium">{{ row.employee.full_name }}</p>
                  <p class="text-xs text-hris-muted">{{ row.employee.employee_id }}</p>
                </td>
                <td class="px-3 py-2">{{ row.present_days }}</td>
                <td class="px-3 py-2">{{ row.late_days }}</td>
                <td class="px-3 py-2">{{ row.sick_days }}</td>
                <td class="px-3 py-2">{{ row.permission_days }}</td>
                <td class="px-3 py-2">{{ row.leave_days }}</td>
                <td class="px-3 py-2">{{ row.alpha_days }}</td>
                <td class="px-3 py-2">{{ row.overtime_minutes }} min</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <form class="rounded-md border border-hris-border bg-hris-panel p-4" @submit.prevent="submitImportPlaceholder">
        <h3 class="font-semibold">Fingerprint Import Placeholder</h3>
        <div v-if="importSuccess" class="mt-3 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
          {{ importSuccess }}
        </div>
        <label class="mt-4 block">
          <span class="text-xs font-medium text-hris-muted">CSV / Excel filename</span>
          <input
            v-model="importForm.file_name"
            required
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
            placeholder="fingerprint-may-2026.csv"
          />
        </label>
        <label class="mt-3 block">
          <span class="text-xs font-medium text-hris-muted">Source</span>
          <select v-model="importForm.source" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm">
            <option value="fingerprint">Fingerprint</option>
            <option value="import">Import</option>
          </select>
        </label>
        <label class="mt-3 block">
          <span class="text-xs font-medium text-hris-muted">Notes</span>
          <textarea v-model="importForm.notes" rows="3" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"></textarea>
        </label>
        <button
          type="submit"
          class="mt-4 w-full rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="importing"
        >
          {{ importing ? 'Submitting...' : 'Submit Placeholder' }}
        </button>
      </form>
    </div>

    <div class="mt-5 overflow-hidden rounded-md border border-hris-border bg-hris-panel">
      <div v-if="loading" class="p-6 text-sm text-hris-muted">Loading report...</div>
      <div v-else-if="attendances.length === 0" class="p-6 text-sm text-hris-muted">
        No attendance records found.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-hris-border text-sm">
          <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
            <tr>
              <th class="px-4 py-3 font-semibold">Employee</th>
              <th class="px-4 py-3 font-semibold">Date</th>
              <th class="px-4 py-3 font-semibold">Time In</th>
              <th class="px-4 py-3 font-semibold">Time Out</th>
              <th class="px-4 py-3 font-semibold">Shift</th>
              <th class="px-4 py-3 font-semibold">Overtime</th>
              <th class="px-4 py-3 font-semibold">Status</th>
              <th class="px-4 py-3 font-semibold">Source</th>
              <th class="px-4 py-3 font-semibold">Department</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-hris-border">
            <tr v-for="attendance in attendances" :key="attendance.id">
              <td class="px-4 py-3">
                <p class="font-medium">{{ attendance.employee?.full_name }}</p>
                <p class="text-xs text-hris-muted">{{ attendance.employee?.employee_id }}</p>
              </td>
              <td class="px-4 py-3">{{ attendance.attendance_date }}</td>
              <td class="px-4 py-3">{{ attendance.time_in ?? '--' }}</td>
              <td class="px-4 py-3">{{ attendance.time_out ?? '--' }}</td>
              <td class="px-4 py-3">{{ attendance.shift_start ?? '--' }} - {{ attendance.shift_end ?? '--' }}</td>
              <td class="px-4 py-3">{{ attendance.overtime_minutes ?? 0 }} min</td>
              <td class="px-4 py-3">
                <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="statusClass(attendance.status)">
                  {{ attendance.status }}
                </span>
              </td>
              <td class="px-4 py-3">{{ attendance.attendance_source ?? '-' }}</td>
              <td class="px-4 py-3">{{ attendance.employee?.department?.name }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="meta" class="mt-4 flex items-center justify-between gap-3 text-sm">
      <p class="text-hris-muted">Page {{ meta.current_page }} of {{ meta.last_page }}</p>
      <div class="flex gap-2">
        <button
          type="button"
          class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="meta.current_page <= 1"
          @click="loadReport(meta.current_page - 1)"
        >
          Previous
        </button>
        <button
          type="button"
          class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="meta.current_page >= meta.last_page"
          @click="loadReport(meta.current_page + 1)"
        >
          Next
        </button>
      </div>
    </div>
  </section>
</template>
