<script setup>
import { onMounted, reactive, ref } from 'vue'

import { getEmployees } from '@/services/employees'
import {
  downloadExport,
  getImportJobs,
  importAttendanceCsv,
  importEmployeesCsv,
} from '@/services/importExport'

const employeeFile = ref(null)
const attendanceFile = ref(null)
const jobs = ref([])
const employees = ref([])
const meta = ref(null)
const loading = ref(false)
const importingEmployees = ref(false)
const importingAttendance = ref(false)
const exporting = ref(false)
const error = ref(null)
const success = ref(null)
const selectedJob = ref(null)

const payrollExport = reactive({
  period_year: new Date().getFullYear(),
  period_month: new Date().getMonth() + 1,
  employee_id: '',
})

const attendanceExport = reactive({
  year: new Date().getFullYear(),
  month: new Date().getMonth() + 1,
  employee_id: '',
})

function setFile(event, target) {
  target.value = event.target.files?.[0] ?? null
}

function statusClass(status) {
  return status === 'completed'
    ? 'bg-emerald-50 text-emerald-700'
    : 'bg-amber-50 text-amber-700'
}

async function loadJobs(page = 1) {
  loading.value = true
  error.value = null

  try {
    const data = await getImportJobs({ per_page: 10, page })
    jobs.value = data.import_jobs
    meta.value = data.meta
  } catch {
    error.value = 'Unable to load import jobs'
  } finally {
    loading.value = false
  }
}

async function loadEmployees() {
  try {
    const data = await getEmployees({ per_page: 50, employment_status: 'active' })
    employees.value = data.employees
  } catch {
    error.value = 'Unable to load employee filters'
  }
}

async function submitEmployeeImport() {
  if (!employeeFile.value) {
    error.value = 'Choose an employee CSV file first.'
    return
  }

  importingEmployees.value = true
  error.value = null
  success.value = null

  try {
    const job = await importEmployeesCsv(employeeFile.value)
    selectedJob.value = job
    success.value = `Employee import completed: ${job.success_rows} succeeded, ${job.failed_rows} failed.`
    await loadJobs()
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Unable to import employees'
  } finally {
    importingEmployees.value = false
  }
}

async function submitAttendanceImport() {
  if (!attendanceFile.value) {
    error.value = 'Choose an attendance CSV file first.'
    return
  }

  importingAttendance.value = true
  error.value = null
  success.value = null

  try {
    const job = await importAttendanceCsv(attendanceFile.value)
    selectedJob.value = job
    success.value = `Attendance import completed: ${job.success_rows} succeeded, ${job.failed_rows} failed.`
    await loadJobs()
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Unable to import attendance'
  } finally {
    importingAttendance.value = false
  }
}

async function exportFile(path, params = {}) {
  exporting.value = true
  error.value = null

  try {
    await downloadExport(path, params)
  } catch {
    error.value = 'Unable to download export'
  } finally {
    exporting.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadEmployees(), loadJobs()])
})
</script>

<template>
  <section class="mx-auto max-w-7xl">
    <div class="border-b border-hris-border pb-5">
      <p class="text-xs font-semibold uppercase text-hris-accent">Operations</p>
      <h2 class="mt-1 text-2xl font-semibold">Import & Export</h2>
      <p class="mt-1 text-sm text-hris-muted">Move employee, attendance, and payroll data with CSV files.</p>
    </div>

    <div v-if="error" class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>
    <div v-if="success" class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ success }}
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
      <form class="rounded-md border border-hris-border bg-hris-panel p-5" @submit.prevent="submitEmployeeImport">
        <h3 class="font-semibold">Employee Import</h3>
        <p class="mt-1 text-sm text-hris-muted">CSV headers: employee_id, full_name, email, department, position, join_date, basic_salary.</p>
        <input
          class="mt-4 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          type="file"
          accept=".csv,text/csv"
          @change="setFile($event, employeeFile)"
        />
        <button
          type="submit"
          class="mt-4 rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="importingEmployees"
        >
          {{ importingEmployees ? 'Importing...' : 'Import Employees' }}
        </button>
      </form>

      <form class="rounded-md border border-hris-border bg-hris-panel p-5" @submit.prevent="submitAttendanceImport">
        <h3 class="font-semibold">Attendance Import</h3>
        <p class="mt-1 text-sm text-hris-muted">CSV headers: employee_id, attendance_date, time_in, time_out, status.</p>
        <input
          class="mt-4 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          type="file"
          accept=".csv,text/csv"
          @change="setFile($event, attendanceFile)"
        />
        <button
          type="submit"
          class="mt-4 rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="importingAttendance"
        >
          {{ importingAttendance ? 'Importing...' : 'Import Attendance' }}
        </button>
      </form>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
      <section class="rounded-md border border-hris-border bg-hris-panel p-5">
        <h3 class="font-semibold">Employee Export</h3>
        <p class="mt-1 text-sm text-hris-muted">Download the employee list with organization and payroll master fields.</p>
        <button
          type="button"
          class="mt-4 rounded-md border border-hris-border px-4 py-2 text-sm font-semibold hover:bg-hris-surface disabled:opacity-60"
          :disabled="exporting"
          @click="exportFile('/exports/employees')"
        >
          Download CSV
        </button>
      </section>

      <section class="rounded-md border border-hris-border bg-hris-panel p-5">
        <h3 class="font-semibold">Payroll Export</h3>
        <div class="mt-4 grid gap-3">
          <input v-model.number="payrollExport.period_year" type="number" class="rounded-md border border-hris-border px-3 py-2 text-sm" />
          <select v-model.number="payrollExport.period_month" class="rounded-md border border-hris-border px-3 py-2 text-sm">
            <option v-for="month in 12" :key="month" :value="month">{{ month }}</option>
          </select>
          <select v-model="payrollExport.employee_id" class="rounded-md border border-hris-border px-3 py-2 text-sm">
            <option value="">All employees</option>
            <option v-for="employee in employees" :key="employee.id" :value="employee.id">
              {{ employee.employee_id }} - {{ employee.full_name }}
            </option>
          </select>
          <button
            type="button"
            class="rounded-md border border-hris-border px-4 py-2 text-sm font-semibold hover:bg-hris-surface disabled:opacity-60"
            :disabled="exporting"
            @click="exportFile('/exports/payroll', payrollExport)"
          >
            Download CSV
          </button>
        </div>
      </section>

      <section class="rounded-md border border-hris-border bg-hris-panel p-5">
        <h3 class="font-semibold">Attendance Recap Export</h3>
        <div class="mt-4 grid gap-3">
          <input v-model.number="attendanceExport.year" type="number" class="rounded-md border border-hris-border px-3 py-2 text-sm" />
          <select v-model.number="attendanceExport.month" class="rounded-md border border-hris-border px-3 py-2 text-sm">
            <option v-for="month in 12" :key="month" :value="month">{{ month }}</option>
          </select>
          <select v-model="attendanceExport.employee_id" class="rounded-md border border-hris-border px-3 py-2 text-sm">
            <option value="">All employees</option>
            <option v-for="employee in employees" :key="employee.id" :value="employee.id">
              {{ employee.employee_id }} - {{ employee.full_name }}
            </option>
          </select>
          <button
            type="button"
            class="rounded-md border border-hris-border px-4 py-2 text-sm font-semibold hover:bg-hris-surface disabled:opacity-60"
            :disabled="exporting"
            @click="exportFile('/exports/attendance-recap', attendanceExport)"
          >
            Download CSV
          </button>
        </div>
      </section>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
      <section class="overflow-hidden rounded-md border border-hris-border bg-hris-panel">
        <div class="border-b border-hris-border px-5 py-4">
          <h3 class="font-semibold">Recent Imports</h3>
        </div>
        <div v-if="loading" class="p-5 text-sm text-hris-muted">Loading import jobs...</div>
        <div v-else-if="jobs.length === 0" class="p-5 text-sm text-hris-muted">No imports yet.</div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-hris-border text-sm">
            <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
              <tr>
                <th class="px-4 py-3 font-semibold">File</th>
                <th class="px-4 py-3 font-semibold">Type</th>
                <th class="px-4 py-3 font-semibold">Rows</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 font-semibold">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-hris-border">
              <tr v-for="job in jobs" :key="job.id">
                <td class="px-4 py-3">{{ job.file_name }}</td>
                <td class="px-4 py-3">{{ job.type }}</td>
                <td class="px-4 py-3">{{ job.success_rows }} success / {{ job.failed_rows }} failed</td>
                <td class="px-4 py-3">
                  <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="statusClass(job.status)">
                    {{ job.status }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <button type="button" class="text-hris-primary hover:underline" @click="selectedJob = job">
                    Details
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="meta" class="flex items-center justify-between border-t border-hris-border px-4 py-3 text-sm">
          <p class="text-hris-muted">Page {{ meta.current_page }} of {{ meta.last_page }}</p>
          <div class="flex gap-2">
            <button class="rounded-md border border-hris-border px-3 py-2 disabled:opacity-50" :disabled="meta.current_page <= 1" @click="loadJobs(meta.current_page - 1)">Previous</button>
            <button class="rounded-md border border-hris-border px-3 py-2 disabled:opacity-50" :disabled="meta.current_page >= meta.last_page" @click="loadJobs(meta.current_page + 1)">Next</button>
          </div>
        </div>
      </section>

      <aside class="rounded-md border border-hris-border bg-hris-panel p-5">
        <h3 class="font-semibold">Import Result</h3>
        <div v-if="!selectedJob" class="mt-4 text-sm text-hris-muted">Select an import job to inspect row failures.</div>
        <div v-else class="mt-4 space-y-3 text-sm">
          <p><span class="text-hris-muted">File:</span> {{ selectedJob.file_name }}</p>
          <p><span class="text-hris-muted">Rows:</span> {{ selectedJob.total_rows }}</p>
          <p><span class="text-hris-muted">Success:</span> {{ selectedJob.success_rows }}</p>
          <p><span class="text-hris-muted">Failed:</span> {{ selectedJob.failed_rows }}</p>
          <div v-if="(selectedJob.failures ?? []).length > 0" class="space-y-3">
            <div v-for="failure in selectedJob.failures" :key="failure.row" class="rounded-md border border-hris-border p-3">
              <p class="font-medium">Row {{ failure.row }}</p>
              <ul class="mt-1 list-inside list-disc text-xs text-red-700">
                <li v-for="message in failure.errors" :key="message">{{ message }}</li>
              </ul>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </section>
</template>
