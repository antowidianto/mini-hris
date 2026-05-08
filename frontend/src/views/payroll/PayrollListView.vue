<script setup>
import { onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationControls from '@/components/PaginationControls.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { ROLES } from '@/config/roles'
import { getEmployees } from '@/services/employees'
import { approvePayroll, generatePayroll, getPayrolls, rejectPayroll } from '@/services/payroll'
import { useAuthStore } from '@/stores/authStore'

const currentDate = new Date()
const auth = useAuthStore()

const filters = reactive({
  period_year: currentDate.getFullYear(),
  period_month: currentDate.getMonth() + 1,
  employee_id: '',
  approval_status: '',
  per_page: 10,
  page: 1,
})

const form = reactive({
  period_year: currentDate.getFullYear(),
  period_month: currentDate.getMonth() + 1,
  employee_id: '',
  fixed_allowance: '',
  non_fixed_allowance: '',
  meal_allowance: '',
  transport_allowance: '',
  pph21_deduction: '',
  other_deduction: '',
})

const payrolls = ref([])
const employees = ref([])
const meta = ref(null)
const loading = ref(false)
const generating = ref(false)
const deciding = ref(false)
const decision = ref(null)
const notes = reactive({})
const error = ref(null)
const success = ref(null)

function currency(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(value ?? 0))
}

function requestError(requestError, fallback) {
  const errors = requestError.response?.data?.errors
  const firstError = errors ? Object.values(errors)[0]?.[0] : null

  return firstError ?? requestError.response?.data?.message ?? fallback
}

function workflowLabel(payroll) {
  if (payroll.approval_status !== 'pending') {
    return payroll.approval_status
  }

  return payroll.current_approval_step
    ? `Waiting ${payroll.current_approval_step.role}`
    : 'No approval step'
}

function canDecidePayroll(payroll) {
  const role = payroll.current_approval_step?.role

  if (!role) {
    return false
  }

  return auth.role === role || (auth.role === ROLES.ADMIN && role === ROLES.HR)
}

async function loadLookups() {
  try {
    const employeeData = await getEmployees({ per_page: 50, employment_status: 'active' })
    employees.value = employeeData.employees
  } catch {
    error.value = 'Unable to load employee options'
  }
}

async function loadPayrolls(page = 1) {
  loading.value = true
  error.value = null
  filters.page = page

  try {
    const data = await getPayrolls(filters)
    payrolls.value = data.payrolls
    meta.value = data.meta
  } catch {
    error.value = 'Unable to load payroll records'
  } finally {
    loading.value = false
  }
}

async function handleGenerate() {
  generating.value = true
  error.value = null
  success.value = null

  try {
    const createdPayrolls = await generatePayroll({
      ...form,
      employee_id: form.employee_id || null,
      fixed_allowance: form.fixed_allowance === '' ? null : form.fixed_allowance,
      non_fixed_allowance: form.non_fixed_allowance === '' ? null : form.non_fixed_allowance,
      meal_allowance: form.meal_allowance === '' ? null : form.meal_allowance,
      transport_allowance: form.transport_allowance === '' ? null : form.transport_allowance,
      pph21_deduction: form.pph21_deduction === '' ? null : form.pph21_deduction,
      other_deduction: form.other_deduction === '' ? null : form.other_deduction,
    })
    success.value = `Generated ${createdPayrolls.length} payroll record(s).`
    filters.period_year = form.period_year
    filters.period_month = form.period_month
    filters.employee_id = form.employee_id
    await loadPayrolls(1)
  } catch (generateError) {
    error.value = requestError(generateError, 'Unable to generate payroll')
  } finally {
    generating.value = false
  }
}

function resetFilters() {
  filters.period_year = currentDate.getFullYear()
  filters.period_month = currentDate.getMonth() + 1
  filters.employee_id = ''
  filters.approval_status = ''
  filters.per_page = 10
  loadPayrolls(1)
}

function requestDecision(payroll, action) {
  decision.value = { payroll, action }
}

async function confirmDecision() {
  if (!decision.value) {
    return
  }

  deciding.value = true
  error.value = null
  success.value = null

  const payroll = decision.value.payroll
  const payload = {
    approval_notes: notes[payroll.id] ?? '',
  }

  try {
    if (decision.value.action === 'approve') {
      await approvePayroll(payroll.id, payload)
      success.value = 'Payroll approved.'
    } else {
      await rejectPayroll(payroll.id, payload)
      success.value = 'Payroll rejected.'
    }

    notes[payroll.id] = ''
    decision.value = null
    await loadPayrolls(meta.value?.current_page ?? 1)
  } catch (decisionError) {
    error.value = requestError(decisionError, 'Unable to update payroll approval')
  } finally {
    deciding.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadLookups(), loadPayrolls()])
})
</script>

<template>
  <section class="mx-auto max-w-7xl">
    <PageHeader eyebrow="Payroll" title="Payroll" description="Generate monthly payroll and review salary results." />

    <div v-if="error" class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>
    <div v-if="success" class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ success }}
    </div>

    <form class="ui-filter-bar mt-5 grid gap-3 rounded-md border border-hris-border bg-hris-panel p-4 lg:grid-cols-6" @submit.prevent="handleGenerate">
      <input v-model.number="form.period_year" type="number" min="2020" max="2100" class="rounded-md border border-hris-border px-3 py-2 text-sm" aria-label="Payroll year" />
      <input v-model.number="form.period_month" type="number" min="1" max="12" class="rounded-md border border-hris-border px-3 py-2 text-sm" aria-label="Payroll month" />
      <select v-model="form.employee_id" class="rounded-md border border-hris-border px-3 py-2 text-sm lg:col-span-2">
        <option value="">All active employees</option>
        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
          {{ employee.full_name }}
        </option>
      </select>
      <input v-model.number="form.fixed_allowance" type="number" min="0" class="rounded-md border border-hris-border px-3 py-2 text-sm" placeholder="Fixed allowance" />
      <input v-model.number="form.non_fixed_allowance" type="number" min="0" class="rounded-md border border-hris-border px-3 py-2 text-sm" placeholder="Non-fixed allowance" />
      <input v-model.number="form.meal_allowance" type="number" min="0" class="rounded-md border border-hris-border px-3 py-2 text-sm" placeholder="Meal allowance" />
      <input v-model.number="form.transport_allowance" type="number" min="0" class="rounded-md border border-hris-border px-3 py-2 text-sm" placeholder="Transport allowance" />
      <input v-model.number="form.pph21_deduction" type="number" min="0" class="rounded-md border border-hris-border px-3 py-2 text-sm" placeholder="PPh 21 deduction" />
      <input v-model.number="form.other_deduction" type="number" min="0" class="rounded-md border border-hris-border px-3 py-2 text-sm" placeholder="Other deduction" />
      <button
        type="submit"
        class="rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark disabled:cursor-not-allowed disabled:opacity-60 lg:col-span-6"
        :disabled="generating"
      >
        {{ generating ? 'Generating...' : 'Generate Payroll' }}
      </button>
    </form>

    <form class="ui-filter-bar mt-5 grid gap-3 rounded-md border border-hris-border bg-hris-panel p-4 sm:grid-cols-6" @submit.prevent="loadPayrolls(1)">
      <input v-model.number="filters.period_year" type="number" min="2020" max="2100" class="rounded-md border border-hris-border px-3 py-2 text-sm" aria-label="Filter year" />
      <input v-model.number="filters.period_month" type="number" min="1" max="12" class="rounded-md border border-hris-border px-3 py-2 text-sm" aria-label="Filter month" />
      <select v-model="filters.employee_id" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All employees</option>
        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
          {{ employee.full_name }}
        </option>
      </select>
      <select v-model="filters.approval_status" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All statuses</option>
        <option value="pending">Pending approval</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
      </select>
      <button type="submit" class="rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark">
        Apply
      </button>
      <button type="button" class="rounded-md border border-hris-border px-4 py-2 text-sm font-medium hover:bg-hris-surface" @click="resetFilters">
        Reset
      </button>
    </form>

    <div class="ui-table-card mt-5 overflow-hidden rounded-md border border-hris-border bg-hris-panel">
      <LoadingState v-if="loading" label="Loading payroll..." />
      <EmptyState v-else-if="payrolls.length === 0" title="No payroll records found" message="Adjust filters or generate payroll for the selected period." />
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-hris-border text-sm">
          <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
            <tr>
              <th class="px-4 py-3 font-semibold">Employee</th>
              <th class="px-4 py-3 font-semibold">Period</th>
              <th class="px-4 py-3 font-semibold">Gross</th>
              <th class="px-4 py-3 font-semibold">Deductions</th>
              <th class="px-4 py-3 font-semibold">THP</th>
              <th class="px-4 py-3 font-semibold">Approval</th>
              <th class="px-4 py-3 font-semibold">Notes</th>
              <th class="px-4 py-3 font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-hris-border">
            <tr v-for="payroll in payrolls" :key="payroll.id">
              <td class="px-4 py-3">
                <p class="font-medium">{{ payroll.employee?.full_name }}</p>
                <p class="text-xs text-hris-muted">{{ payroll.employee?.employee_id }}</p>
              </td>
              <td class="px-4 py-3">{{ payroll.period_label }}</td>
              <td class="px-4 py-3">{{ currency(payroll.gross_salary) }}</td>
              <td class="px-4 py-3">{{ currency(payroll.total_deductions) }}</td>
              <td class="px-4 py-3 font-semibold">{{ currency(payroll.take_home_pay ?? payroll.net_salary) }}</td>
              <td class="px-4 py-3">
                <StatusBadge :status="payroll.approval_status" :label="workflowLabel(payroll)" />
              </td>
              <td class="px-4 py-3">
                <textarea
                  v-if="payroll.approval_status === 'pending'"
                  v-model="notes[payroll.id]"
                  rows="2"
                  class="w-48 rounded-md border border-hris-border px-3 py-2 text-sm"
                  placeholder="Approval notes"
                ></textarea>
                <p v-else class="max-w-48 text-xs text-hris-muted">{{ payroll.approval_notes ?? '--' }}</p>
              </td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-2">
                  <RouterLink class="text-hris-primary hover:underline" :to="`/payroll/${payroll.id}`">
                    View
                  </RouterLink>
                  <template v-if="payroll.approval_status === 'pending' && canDecidePayroll(payroll)">
                    <button type="button" class="text-emerald-700 hover:underline" @click="requestDecision(payroll, 'approve')">
                      Approve
                    </button>
                    <button type="button" class="text-red-600 hover:underline" @click="requestDecision(payroll, 'reject')">
                      Reject
                    </button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <PaginationControls :meta="meta" @change="loadPayrolls" />

    <ConfirmationModal
      :open="Boolean(decision)"
      :title="decision?.action === 'approve' ? 'Approve Payroll' : 'Reject Payroll'"
      :message="`${decision?.action === 'approve' ? 'Approve' : 'Reject'} payroll for ${decision?.payroll?.employee?.full_name ?? 'this employee'}?`"
      :confirm-label="decision?.action === 'approve' ? 'Approve' : 'Reject'"
      :variant="decision?.action === 'approve' ? 'success' : 'danger'"
      :loading="deciding"
      @cancel="decision = null"
      @confirm="confirmDecision"
    />
  </section>
</template>
