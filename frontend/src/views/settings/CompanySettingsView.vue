<script setup>
import { onMounted, reactive, ref } from 'vue'

import {
  getCompanySettingsBundle,
  replaceApprovalFlows,
  updateCompanySettings,
  updateConfigurationSettings,
  updatePayrollComponent,
} from '@/services/companySettings'

const form = reactive({
  company_name: '',
  logo_path: '',
  address: '',
  company_npwp: '',
  default_work_start: '08:00',
  default_work_end: '17:00',
  late_tolerance_minutes: 10,
  annual_leave_quota: 12,
  payroll_work_days_per_month: 22,
  late_deduction_amount: 25000,
  bpjs_kesehatan_employee_rate: 1,
  bpjs_kesehatan_employer_rate: 4,
  bpjs_jht_employee_rate: 2,
  bpjs_jht_employer_rate: 3.7,
  bpjs_jp_employee_rate: 1,
  bpjs_jp_employer_rate: 2,
  payroll_fixed_allowance_default: 0,
  payroll_non_fixed_allowance_default: 0,
  meal_allowance_default: 0,
  transport_allowance_default: 0,
  pph21_default_deduction: 0,
  employee_number_format: 'EMP-{YYYY}-{####}',
})

const loading = ref(false)
const saving = ref(false)
const registrySaving = ref(false)
const flowSaving = ref(false)
const componentSavingId = ref(null)
const error = ref(null)
const success = ref(null)
const configurationSettings = ref([])
const payrollComponents = ref([])
const approvalFlows = ref([])
const newFlow = reactive({
  module: 'leave',
  step_order: 1,
  role: 'hr',
  is_active: true,
})
const integerSettingKeys = new Set(['late_tolerance_minutes', 'annual_leave_quota', 'payroll_work_days_per_month'])
const decimalSettingKeys = new Set([
  'late_deduction_amount',
  'bpjs_kesehatan_employee_rate',
  'bpjs_kesehatan_employer_rate',
  'bpjs_jht_employee_rate',
  'bpjs_jht_employer_rate',
  'bpjs_jp_employee_rate',
  'bpjs_jp_employer_rate',
  'payroll_fixed_allowance_default',
  'payroll_non_fixed_allowance_default',
  'meal_allowance_default',
  'transport_allowance_default',
  'pph21_default_deduction',
])
const approvalRoleOptions = {
  leave: [
    { value: 'supervisor', label: 'Supervisor' },
    { value: 'hr', label: 'HR' },
    { value: 'admin', label: 'Admin' },
  ],
  payroll: [
    { value: 'hr', label: 'HR' },
    { value: 'admin', label: 'Admin' },
  ],
  request: [
    { value: 'supervisor', label: 'Supervisor' },
    { value: 'hr', label: 'HR' },
    { value: 'admin', label: 'Admin' },
    { value: 'employee', label: 'Employee' },
  ],
}

function fillForm(settings) {
  Object.entries(settings).forEach(([key, value]) => {
    if (key in form) {
      form[key] = value ?? ''
    }
  })
}

function requestError(requestError, fallback) {
  const errors = requestError.response?.data?.errors
  const firstError = errors ? Object.values(errors)[0]?.[0] : null

  return firstError ?? requestError.response?.data?.message ?? fallback
}

function normalizedSettingValue(setting) {
  if (integerSettingKeys.has(setting.key)) {
    return Number.parseInt(setting.value || 0, 10)
  }

  if (decimalSettingKeys.has(setting.key)) {
    return Number.parseFloat(setting.value || 0)
  }

  return setting.value
}

async function loadSettings() {
  loading.value = true
  error.value = null

  try {
    const data = await getCompanySettingsBundle()
    fillForm(data.company_settings)
    configurationSettings.value = data.settings ?? []
    payrollComponents.value = data.payroll_components ?? []
    approvalFlows.value = data.approval_flows ?? []
  } catch {
    error.value = 'Unable to load company settings'
  } finally {
    loading.value = false
  }
}

async function saveSettings() {
  saving.value = true
  error.value = null
  success.value = null

  try {
    fillForm(await updateCompanySettings(form))
    success.value = 'Company settings saved.'
  } catch (saveError) {
    error.value = requestError(saveError, 'Unable to save company settings')
  } finally {
    saving.value = false
  }
}

async function saveConfigurationRegistry() {
  registrySaving.value = true
  error.value = null
  success.value = null

  try {
    const payload = configurationSettings.value.reduce((settings, item) => {
      settings[item.key] = normalizedSettingValue(item)

      return settings
    }, {})

    configurationSettings.value = await updateConfigurationSettings(payload)
    success.value = 'Configuration registry saved.'
    await loadSettings()
  } catch (saveError) {
    error.value = requestError(saveError, 'Unable to save configuration registry')
  } finally {
    registrySaving.value = false
  }
}

async function savePayrollComponent(component) {
  componentSavingId.value = component.id
  error.value = null
  success.value = null

  try {
    const updatedComponent = await updatePayrollComponent(component.id, {
      name: component.name,
      type: component.type,
      is_active: component.is_active,
      sort_order: component.sort_order,
    })
    const index = payrollComponents.value.findIndex((item) => item.id === updatedComponent.id)

    if (index >= 0) {
      payrollComponents.value.splice(index, 1, updatedComponent)
    }

    success.value = 'Payroll component saved.'
  } catch (saveError) {
    error.value = requestError(saveError, 'Unable to save payroll component')
  } finally {
    componentSavingId.value = null
  }
}

function addApprovalFlow() {
  approvalFlows.value.push({ ...newFlow, id: `new-${Date.now()}` })
  newFlow.step_order = 1
  newFlow.role = 'hr'
  newFlow.is_active = true
}

function removeApprovalFlow(index) {
  approvalFlows.value.splice(index, 1)
}

function roleOptions(module) {
  return approvalRoleOptions[module] ?? approvalRoleOptions.request
}

function ensureFlowRole(flow) {
  const options = roleOptions(flow.module)

  if (!options.some((option) => option.value === flow.role)) {
    flow.role = options[0].value
  }
}

async function saveApprovalFlows() {
  flowSaving.value = true
  error.value = null
  success.value = null

  try {
    const payload = approvalFlows.value.map(({ module, step_order, role, is_active }) => ({
      module,
      step_order,
      role,
      is_active,
    }))

    approvalFlows.value = await replaceApprovalFlows(payload)
    success.value = 'Approval flows saved.'
  } catch (saveError) {
    error.value = requestError(saveError, 'Unable to save approval flows')
  } finally {
    flowSaving.value = false
  }
}

onMounted(loadSettings)
</script>

<template>
  <section class="mx-auto max-w-7xl">
    <div class="border-b border-hris-border pb-5">
      <p class="text-xs font-semibold uppercase text-hris-accent">Indonesia SME</p>
      <h2 class="mt-1 text-2xl font-semibold">Company Settings</h2>
      <p class="mt-1 text-sm text-hris-muted">Configure company defaults used by attendance, leave, payroll, and employee numbering.</p>
    </div>

    <div v-if="error" class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>
    <div v-if="success" class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ success }}
    </div>

    <div v-if="loading" class="mt-6 rounded-md border border-hris-border bg-hris-panel p-5 text-sm text-hris-muted">
      Loading company settings...
    </div>

    <form v-else class="mt-6 space-y-5" @submit.prevent="saveSettings">
      <section class="rounded-md border border-hris-border bg-hris-panel p-5">
        <h3 class="font-semibold">Company Identity</h3>
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">Company name</span>
            <input v-model="form.company_name" type="text" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">Company NPWP</span>
            <input v-model="form.company_npwp" type="text" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block lg:col-span-2">
            <span class="text-xs font-medium text-hris-muted">Logo path placeholder</span>
            <input v-model="form.logo_path" type="text" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" placeholder="/storage/company/logo.png" />
          </label>
          <label class="block lg:col-span-2">
            <span class="text-xs font-medium text-hris-muted">Address</span>
            <textarea v-model="form.address" rows="3" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"></textarea>
          </label>
        </div>
      </section>

      <section class="rounded-md border border-hris-border bg-hris-panel p-5">
        <h3 class="font-semibold">Attendance And Leave Defaults</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-4">
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">Work start</span>
            <input v-model="form.default_work_start" type="time" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">Work end</span>
            <input v-model="form.default_work_end" type="time" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">Late tolerance minutes</span>
            <input v-model.number="form.late_tolerance_minutes" type="number" min="0" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">Annual leave quota</span>
            <input v-model.number="form.annual_leave_quota" type="number" min="0" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">Payroll work days</span>
            <input v-model.number="form.payroll_work_days_per_month" type="number" min="1" max="31" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">Late deduction amount</span>
            <input v-model.number="form.late_deduction_amount" type="number" min="0" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
        </div>
      </section>

      <section class="rounded-md border border-hris-border bg-hris-panel p-5">
        <h3 class="font-semibold">BPJS Rate Settings</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">Kesehatan employee %</span>
            <input v-model.number="form.bpjs_kesehatan_employee_rate" type="number" min="0" step="0.01" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">Kesehatan employer %</span>
            <input v-model.number="form.bpjs_kesehatan_employer_rate" type="number" min="0" step="0.01" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">JHT employee %</span>
            <input v-model.number="form.bpjs_jht_employee_rate" type="number" min="0" step="0.01" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">JHT employer %</span>
            <input v-model.number="form.bpjs_jht_employer_rate" type="number" min="0" step="0.01" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">JP employee %</span>
            <input v-model.number="form.bpjs_jp_employee_rate" type="number" min="0" step="0.01" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">JP employer %</span>
            <input v-model.number="form.bpjs_jp_employer_rate" type="number" min="0" step="0.01" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
        </div>
      </section>

      <section class="rounded-md border border-hris-border bg-hris-panel p-5">
        <h3 class="font-semibold">Payroll Component Defaults</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">Fixed allowance</span>
            <input v-model.number="form.payroll_fixed_allowance_default" type="number" min="0" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">Non-fixed allowance</span>
            <input v-model.number="form.payroll_non_fixed_allowance_default" type="number" min="0" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">Meal allowance</span>
            <input v-model.number="form.meal_allowance_default" type="number" min="0" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">Transport allowance</span>
            <input v-model.number="form.transport_allowance_default" type="number" min="0" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">PPh 21 default deduction</span>
            <input v-model.number="form.pph21_default_deduction" type="number" min="0" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">Employee number format</span>
            <input v-model="form.employee_number_format" type="text" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
          </label>
        </div>
      </section>

      <section class="rounded-md border border-hris-border bg-hris-panel p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h3 class="font-semibold">Configuration Registry</h3>
            <p class="mt-1 text-sm text-hris-muted">Global setting keys used by attendance, leave, and payroll.</p>
          </div>
          <button
            type="button"
            class="rounded-md border border-hris-border px-4 py-2 text-sm font-semibold hover:bg-hris-soft disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="registrySaving"
            @click="saveConfigurationRegistry"
          >
            {{ registrySaving ? 'Saving...' : 'Save Registry' }}
          </button>
        </div>

        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full divide-y divide-hris-border text-sm">
            <thead class="bg-hris-soft text-left text-xs uppercase text-hris-muted">
              <tr>
                <th class="px-3 py-2 font-semibold">Key</th>
                <th class="px-3 py-2 font-semibold">Value</th>
                <th class="px-3 py-2 font-semibold">Scope</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-hris-border">
              <tr v-if="configurationSettings.length === 0">
                <td colspan="3" class="px-3 py-6 text-center text-hris-muted">No configuration keys found.</td>
              </tr>
              <tr v-for="setting in configurationSettings" :key="setting.id">
                <td class="px-3 py-2 font-medium">{{ setting.key }}</td>
                <td class="px-3 py-2">
                  <input v-model="setting.value" type="text" class="w-full min-w-48 rounded-md border border-hris-border px-3 py-2 text-sm" />
                </td>
                <td class="px-3 py-2">
                  <span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold uppercase text-blue-700">{{ setting.scope }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="rounded-md border border-hris-border bg-hris-panel p-5">
        <h3 class="font-semibold">Payroll Components</h3>
        <p class="mt-1 text-sm text-hris-muted">Turn earning and deduction components on or off without changing code.</p>

        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full divide-y divide-hris-border text-sm">
            <thead class="bg-hris-soft text-left text-xs uppercase text-hris-muted">
              <tr>
                <th class="px-3 py-2 font-semibold">Code</th>
                <th class="px-3 py-2 font-semibold">Name</th>
                <th class="px-3 py-2 font-semibold">Type</th>
                <th class="px-3 py-2 font-semibold">Sort</th>
                <th class="px-3 py-2 font-semibold">Active</th>
                <th class="px-3 py-2 font-semibold">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-hris-border">
              <tr v-if="payrollComponents.length === 0">
                <td colspan="6" class="px-3 py-6 text-center text-hris-muted">No payroll components found.</td>
              </tr>
              <tr v-for="component in payrollComponents" :key="component.id">
                <td class="px-3 py-2 font-medium">{{ component.code }}</td>
                <td class="px-3 py-2">
                  <input v-model="component.name" type="text" class="w-full min-w-40 rounded-md border border-hris-border px-3 py-2 text-sm" />
                </td>
                <td class="px-3 py-2">
                  <select v-model="component.type" class="w-full min-w-32 rounded-md border border-hris-border px-3 py-2 text-sm">
                    <option value="earning">Earning</option>
                    <option value="deduction">Deduction</option>
                  </select>
                </td>
                <td class="px-3 py-2">
                  <input v-model.number="component.sort_order" type="number" min="0" class="w-24 rounded-md border border-hris-border px-3 py-2 text-sm" />
                </td>
                <td class="px-3 py-2">
                  <label class="inline-flex items-center gap-2 text-sm">
                    <input v-model="component.is_active" type="checkbox" class="h-4 w-4 rounded border-hris-border text-hris-primary" />
                    <span>{{ component.is_active ? 'Active' : 'Inactive' }}</span>
                  </label>
                </td>
                <td class="px-3 py-2">
                  <button
                    type="button"
                    class="rounded-md bg-hris-primary px-3 py-2 text-xs font-semibold text-white hover:bg-hris-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="componentSavingId === component.id"
                    @click="savePayrollComponent(component)"
                  >
                    {{ componentSavingId === component.id ? 'Saving...' : 'Save' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="rounded-md border border-hris-border bg-hris-panel p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h3 class="font-semibold">Approval Flows</h3>
            <p class="mt-1 text-sm text-hris-muted">Configure approval steps for leave, payroll, and request modules.</p>
          </div>
          <button
            type="button"
            class="rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="flowSaving"
            @click="saveApprovalFlows"
          >
            {{ flowSaving ? 'Saving...' : 'Save Flows' }}
          </button>
        </div>

        <div class="mt-4 grid gap-3 rounded-md border border-hris-border bg-hris-soft p-3 md:grid-cols-5">
          <select v-model="newFlow.module" class="rounded-md border border-hris-border px-3 py-2 text-sm" @change="ensureFlowRole(newFlow)">
            <option value="leave">Leave</option>
            <option value="payroll">Payroll</option>
            <option value="request">Request</option>
          </select>
          <input v-model.number="newFlow.step_order" type="number" min="1" max="20" class="rounded-md border border-hris-border px-3 py-2 text-sm" />
          <select v-model="newFlow.role" class="rounded-md border border-hris-border px-3 py-2 text-sm">
            <option v-for="option in roleOptions(newFlow.module)" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <label class="inline-flex items-center gap-2 text-sm">
            <input v-model="newFlow.is_active" type="checkbox" class="h-4 w-4 rounded border-hris-border text-hris-primary" />
            <span>Active</span>
          </label>
          <button type="button" class="rounded-md border border-hris-border px-3 py-2 text-sm font-semibold hover:bg-white" @click="addApprovalFlow">Add Step</button>
        </div>

        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full divide-y divide-hris-border text-sm">
            <thead class="bg-hris-soft text-left text-xs uppercase text-hris-muted">
              <tr>
                <th class="px-3 py-2 font-semibold">Module</th>
                <th class="px-3 py-2 font-semibold">Step</th>
                <th class="px-3 py-2 font-semibold">Role</th>
                <th class="px-3 py-2 font-semibold">Active</th>
                <th class="px-3 py-2 font-semibold">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-hris-border">
              <tr v-if="approvalFlows.length === 0">
                <td colspan="5" class="px-3 py-6 text-center text-hris-muted">No approval flows found.</td>
              </tr>
              <tr v-for="(flow, index) in approvalFlows" :key="flow.id">
                <td class="px-3 py-2">
                  <select v-model="flow.module" class="w-full min-w-32 rounded-md border border-hris-border px-3 py-2 text-sm" @change="ensureFlowRole(flow)">
                    <option value="leave">Leave</option>
                    <option value="payroll">Payroll</option>
                    <option value="request">Request</option>
                  </select>
                </td>
                <td class="px-3 py-2">
                  <input v-model.number="flow.step_order" type="number" min="1" max="20" class="w-24 rounded-md border border-hris-border px-3 py-2 text-sm" />
                </td>
                <td class="px-3 py-2">
                  <select v-model="flow.role" class="w-full min-w-32 rounded-md border border-hris-border px-3 py-2 text-sm">
                    <option v-for="option in roleOptions(flow.module)" :key="option.value" :value="option.value">{{ option.label }}</option>
                  </select>
                </td>
                <td class="px-3 py-2">
                  <label class="inline-flex items-center gap-2 text-sm">
                    <input v-model="flow.is_active" type="checkbox" class="h-4 w-4 rounded border-hris-border text-hris-primary" />
                    <span>{{ flow.is_active ? 'Active' : 'Inactive' }}</span>
                  </label>
                </td>
                <td class="px-3 py-2">
                  <button type="button" class="rounded-md border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50" @click="removeApprovalFlow(index)">
                    Remove
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div class="flex justify-end">
        <button
          type="submit"
          class="rounded-md bg-hris-primary px-5 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="saving"
        >
          {{ saving ? 'Saving...' : 'Save Settings' }}
        </button>
      </div>
    </form>
  </section>
</template>
