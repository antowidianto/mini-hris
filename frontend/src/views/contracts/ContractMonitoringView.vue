<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'

import {
  getEmployeeContracts,
  getExpiringContracts,
  renewEmployeeContract,
} from '@/services/contracts'

const filters = reactive({
  days: 60,
  per_page: 10,
  page: 1,
})

const contracts = ref([])
const history = ref([])
const selectedEmployee = ref(null)
const meta = ref(null)
const loading = ref(false)
const historyLoading = ref(false)
const saving = ref(false)
const error = ref(null)
const historyError = ref(null)
const success = ref(null)
const validationErrors = ref({})

const renewalForm = reactive({
  employment_type: 'pkwt',
  contract_start_date: '',
  contract_end_date: '',
  renewal_date: '',
  document_path: '',
  notes: '',
})

const selectedEmployeeLabel = computed(() => {
  if (!selectedEmployee.value) {
    return 'Select an employee'
  }

  return `${selectedEmployee.value.employee_id} - ${selectedEmployee.value.full_name}`
})

function employmentTypeLabel(type) {
  return {
    probation: 'Probation',
    pkwt: 'PKWT',
    pkwtt: 'PKWTT',
  }[type] ?? '-'
}

function statusClass(daysRemaining) {
  if (daysRemaining <= 30) {
    return 'bg-red-50 text-red-700'
  }

  return 'bg-amber-50 text-amber-700'
}

function resetRenewalForm(employee) {
  renewalForm.employment_type = employee?.employment_type ?? 'pkwt'
  renewalForm.contract_start_date = ''
  renewalForm.contract_end_date = ''
  renewalForm.renewal_date = new Date().toISOString().slice(0, 10)
  renewalForm.document_path = ''
  renewalForm.notes = ''
}

async function loadContracts(page = 1) {
  loading.value = true
  error.value = null
  filters.page = page

  try {
    const data = await getExpiringContracts(filters)
    contracts.value = data.contracts
    meta.value = data.meta
  } catch {
    error.value = 'Unable to load expiring contracts'
  } finally {
    loading.value = false
  }
}

async function selectEmployee(employee, options = {}) {
  selectedEmployee.value = employee
  history.value = []
  historyError.value = null
  validationErrors.value = {}
  if (!options.keepSuccess) {
    success.value = null
  }
  resetRenewalForm(employee)
  historyLoading.value = true

  try {
    history.value = await getEmployeeContracts(employee.id)
  } catch {
    historyError.value = 'Unable to load contract history'
  } finally {
    historyLoading.value = false
  }
}

async function submitRenewal() {
  if (!selectedEmployee.value) {
    return
  }

  saving.value = true
  error.value = null
  success.value = null
  validationErrors.value = {}

  try {
    await renewEmployeeContract(selectedEmployee.value.id, {
      employment_type: renewalForm.employment_type,
      contract_start_date: renewalForm.contract_start_date,
      contract_end_date: renewalForm.contract_end_date || null,
      renewal_date: renewalForm.renewal_date || null,
      document_path: renewalForm.document_path || null,
      notes: renewalForm.notes || null,
    })
    success.value = 'Contract renewal recorded.'
    await Promise.all([loadContracts(filters.page), selectEmployee(selectedEmployee.value, { keepSuccess: true })])
  } catch (requestError) {
    validationErrors.value = requestError.response?.data?.errors ?? {}
    error.value = requestError.response?.data?.message ?? 'Unable to record contract renewal'
  } finally {
    saving.value = false
  }
}

function fieldError(field) {
  return validationErrors.value[field]?.[0]
}

onMounted(loadContracts)
</script>

<template>
  <section class="mx-auto max-w-screen-2xl">
    <div class="flex flex-col justify-between gap-4 border-b border-hris-border pb-5 sm:flex-row">
      <div>
        <p class="text-xs font-semibold uppercase text-hris-accent">People</p>
        <h2 class="mt-1 text-2xl font-semibold">Contract Monitoring</h2>
        <p class="mt-1 text-sm text-hris-muted">Track PKWT and probation contracts that need HR attention.</p>
      </div>
    </div>

    <form class="mt-5 flex flex-wrap gap-3" @submit.prevent="loadContracts(1)">
      <select
        v-model.number="filters.days"
        class="rounded-md border border-hris-border bg-hris-panel px-3 py-2 text-sm"
      >
        <option :value="30">Expiring in 30 days</option>
        <option :value="60">Expiring in 60 days</option>
      </select>
      <button
        type="submit"
        class="rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark"
      >
        Apply
      </button>
    </form>

    <div v-if="error" class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>
    <div v-if="success" class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ success }}
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-[1.4fr_1fr]">
      <div class="overflow-hidden rounded-md border border-hris-border bg-hris-panel">
        <div v-if="loading" class="p-6 text-sm text-hris-muted">Loading contracts...</div>
        <div v-else-if="contracts.length === 0" class="p-6 text-sm text-hris-muted">No contracts need renewal in this window.</div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-hris-border text-sm">
            <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
              <tr>
                <th class="px-4 py-3 font-semibold">Employee</th>
                <th class="px-4 py-3 font-semibold">Organization</th>
                <th class="px-4 py-3 font-semibold">Type</th>
                <th class="px-4 py-3 font-semibold">End Date</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 font-semibold">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-hris-border">
              <tr v-for="contract in contracts" :key="contract.id">
                <td class="px-4 py-3">
                  <p class="font-medium">{{ contract.full_name }}</p>
                  <p class="text-xs text-hris-muted">{{ contract.employee_id }} - {{ contract.email }}</p>
                </td>
                <td class="px-4 py-3">
                  <p>{{ contract.branch?.name ?? '-' }}</p>
                  <p class="text-xs text-hris-muted">{{ contract.department?.name ?? '-' }} / {{ contract.position?.name ?? '-' }}</p>
                </td>
                <td class="px-4 py-3">{{ employmentTypeLabel(contract.employment_type) }}</td>
                <td class="px-4 py-3">{{ contract.contract_end_date }}</td>
                <td class="px-4 py-3">
                  <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="statusClass(contract.days_remaining)">
                    {{ contract.days_remaining }} days
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex gap-2">
                    <button type="button" class="text-hris-primary hover:underline" @click="selectEmployee(contract)">
                      Review
                    </button>
                    <RouterLink class="text-hris-primary hover:underline" :to="`/employees/${contract.id}`">
                      Profile
                    </RouterLink>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="meta" class="flex items-center justify-between gap-3 border-t border-hris-border px-4 py-3 text-sm">
          <p class="text-hris-muted">Page {{ meta.current_page }} of {{ meta.last_page }}</p>
          <div class="flex gap-2">
            <button
              type="button"
              class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="meta.current_page <= 1"
              @click="loadContracts(meta.current_page - 1)"
            >
              Previous
            </button>
            <button
              type="button"
              class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="meta.current_page >= meta.last_page"
              @click="loadContracts(meta.current_page + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <aside class="rounded-md border border-hris-border bg-hris-panel p-5">
        <h3 class="font-semibold">{{ selectedEmployeeLabel }}</h3>
        <p class="mt-1 text-sm text-hris-muted">Renewal history and document placeholder.</p>

        <div v-if="historyError" class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {{ historyError }}
        </div>

        <div v-if="historyLoading" class="mt-4 text-sm text-hris-muted">Loading history...</div>
        <div v-else-if="selectedEmployee" class="mt-4 space-y-3">
          <div v-if="history.length === 0" class="rounded-md border border-hris-border p-3 text-sm text-hris-muted">
            No renewal history yet.
          </div>
          <div v-for="contract in history" :key="contract.id" class="rounded-md border border-hris-border p-3 text-sm">
            <p class="font-medium">{{ employmentTypeLabel(contract.employment_type) }}</p>
            <p class="text-hris-muted">{{ contract.contract_start_date }} to {{ contract.contract_end_date ?? 'No end date' }}</p>
            <p v-if="contract.document_path" class="mt-1 text-xs text-hris-muted">{{ contract.document_path }}</p>
            <p v-if="contract.notes" class="mt-1 text-xs text-hris-muted">{{ contract.notes }}</p>
          </div>

          <form class="space-y-3 border-t border-hris-border pt-4" @submit.prevent="submitRenewal">
            <h4 class="font-semibold">Record Renewal</h4>
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">Employment type</span>
              <select v-model="renewalForm.employment_type" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm">
                <option value="probation">Probation</option>
                <option value="pkwt">PKWT</option>
                <option value="pkwtt">PKWTT</option>
              </select>
              <span v-if="fieldError('employment_type')" class="mt-1 block text-xs text-red-600">{{ fieldError('employment_type') }}</span>
            </label>
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">Contract start</span>
              <input v-model="renewalForm.contract_start_date" required type="date" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
              <span v-if="fieldError('contract_start_date')" class="mt-1 block text-xs text-red-600">{{ fieldError('contract_start_date') }}</span>
            </label>
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">Contract end</span>
              <input v-model="renewalForm.contract_end_date" type="date" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
              <span v-if="fieldError('contract_end_date')" class="mt-1 block text-xs text-red-600">{{ fieldError('contract_end_date') }}</span>
            </label>
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">Renewal date</span>
              <input v-model="renewalForm.renewal_date" type="date" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
              <span v-if="fieldError('renewal_date')" class="mt-1 block text-xs text-red-600">{{ fieldError('renewal_date') }}</span>
            </label>
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">Document path placeholder</span>
              <input v-model="renewalForm.document_path" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" placeholder="contracts/EMP-0001-2026.pdf" />
              <span v-if="fieldError('document_path')" class="mt-1 block text-xs text-red-600">{{ fieldError('document_path') }}</span>
            </label>
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">Notes</span>
              <textarea v-model="renewalForm.notes" rows="3" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"></textarea>
              <span v-if="fieldError('notes')" class="mt-1 block text-xs text-red-600">{{ fieldError('notes') }}</span>
            </label>
            <button
              type="submit"
              class="w-full rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="saving"
            >
              {{ saving ? 'Saving...' : 'Save Renewal' }}
            </button>
          </form>
        </div>
      </aside>
    </div>
  </section>
</template>
