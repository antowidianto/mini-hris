<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  createEmployee,
  getBranches,
  getDepartments,
  getEmployee,
  getPositions,
  getSupervisors,
  updateEmployee,
} from '@/services/employees'

const route = useRoute()
const router = useRouter()
const isEditing = computed(() => Boolean(route.params.id))

const branches = ref([])
const departments = ref([])
const positions = ref([])
const supervisors = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const validationErrors = ref({})

const form = reactive({
  employee_id: '',
  full_name: '',
  email: '',
  nik_ktp: '',
  npwp: '',
  bpjs_kesehatan_number: '',
  bpjs_ketenagakerjaan_number: '',
  tax_marital_status: '',
  tax_dependents: 0,
  bank_name: '',
  bank_account_number: '',
  bank_account_holder_name: '',
  branch_id: '',
  department_id: '',
  position_id: '',
  supervisor_id: '',
  join_date: '',
  employment_status: 'active',
  employment_type: 'pkwtt',
  contract_start_date: '',
  contract_end_date: '',
  basic_salary: '',
  user_id: '',
})

const filteredPositions = computed(() => {
  if (!form.department_id) {
    return positions.value
  }

  return positions.value.filter((position) => Number(position.department_id) === Number(form.department_id))
})

watch(
  () => form.department_id,
  () => {
    if (!filteredPositions.value.some((position) => Number(position.id) === Number(form.position_id))) {
      form.position_id = ''
    }
  },
)

function fillForm(employee) {
  form.employee_id = employee.employee_id
  form.full_name = employee.full_name
  form.email = employee.email
  form.nik_ktp = employee.nik_ktp ?? ''
  form.npwp = employee.npwp ?? ''
  form.bpjs_kesehatan_number = employee.bpjs_kesehatan_number ?? ''
  form.bpjs_ketenagakerjaan_number = employee.bpjs_ketenagakerjaan_number ?? ''
  form.tax_marital_status = employee.tax_marital_status ?? ''
  form.tax_dependents = employee.tax_dependents ?? 0
  form.bank_name = employee.bank_name ?? ''
  form.bank_account_number = employee.bank_account_number ?? ''
  form.bank_account_holder_name = employee.bank_account_holder_name ?? ''
  form.branch_id = employee.branch?.id ?? ''
  form.department_id = employee.department?.id ?? ''
  form.position_id = employee.position?.id ?? ''
  form.supervisor_id = employee.supervisor?.id ?? ''
  form.join_date = employee.join_date
  form.employment_status = employee.employment_status
  form.employment_type = employee.employment_type ?? 'pkwtt'
  form.contract_start_date = employee.contract_start_date ?? ''
  form.contract_end_date = employee.contract_end_date ?? ''
  form.basic_salary = employee.basic_salary
  form.user_id = employee.user?.id ?? ''
}

function payload() {
  return {
    employee_id: form.employee_id,
    full_name: form.full_name,
    email: form.email,
    nik_ktp: form.nik_ktp || null,
    npwp: form.npwp || null,
    bpjs_kesehatan_number: form.bpjs_kesehatan_number || null,
    bpjs_ketenagakerjaan_number: form.bpjs_ketenagakerjaan_number || null,
    tax_marital_status: form.tax_marital_status || null,
    tax_dependents: Number(form.tax_dependents ?? 0),
    bank_name: form.bank_name || null,
    bank_account_number: form.bank_account_number || null,
    bank_account_holder_name: form.bank_account_holder_name || null,
    branch_id: form.branch_id ? Number(form.branch_id) : null,
    department_id: Number(form.department_id),
    position_id: Number(form.position_id),
    supervisor_id: form.supervisor_id ? Number(form.supervisor_id) : null,
    join_date: form.join_date,
    employment_status: form.employment_status,
    employment_type: form.employment_type,
    contract_start_date: form.contract_start_date || null,
    contract_end_date: form.contract_end_date || null,
    basic_salary: Number(form.basic_salary),
    user_id: form.user_id ? Number(form.user_id) : null,
  }
}

async function loadPage() {
  loading.value = true
  error.value = null

  try {
    const [branchData, departmentData, positionData, supervisorData] = await Promise.all([
      getBranches(),
      getDepartments(),
      getPositions(),
      getSupervisors(isEditing.value ? { exclude_id: route.params.id } : {}),
    ])
    branches.value = branchData
    departments.value = departmentData
    positions.value = positionData
    supervisors.value = supervisorData

    if (isEditing.value) {
      fillForm(await getEmployee(route.params.id))
    }
  } catch {
    error.value = 'Unable to load employee form'
  } finally {
    loading.value = false
  }
}

async function submitForm() {
  saving.value = true
  error.value = null
  validationErrors.value = {}

  try {
    const employee = isEditing.value
      ? await updateEmployee(route.params.id, payload())
      : await createEmployee(payload())

    router.push(`/employees/${employee.id}`)
  } catch (requestError) {
    validationErrors.value = requestError.response?.data?.errors ?? {}
    error.value = requestError.response?.data?.message ?? 'Unable to save employee'
  } finally {
    saving.value = false
  }
}

function fieldError(field) {
  return validationErrors.value[field]?.[0]
}

onMounted(loadPage)
</script>

<template>
  <section class="mx-auto max-w-4xl">
    <div class="border-b border-hris-border pb-5">
      <p class="text-xs font-semibold uppercase text-hris-accent">People</p>
      <h2 class="mt-1 text-2xl font-semibold">{{ isEditing ? 'Edit Employee' : 'Add Employee' }}</h2>
    </div>

    <div v-if="loading" class="mt-6 rounded-md border border-hris-border bg-hris-panel p-5 text-sm text-hris-muted">
      Loading form...
    </div>

    <form v-else class="mt-6 space-y-5 rounded-md border border-hris-border bg-hris-panel p-5" @submit.prevent="submitForm">
      <div v-if="error" class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        {{ error }}
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <label class="block">
          <span class="text-sm font-medium">Employee ID</span>
          <input
            v-model="form.employee_id"
            required
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
            placeholder="EMP-0001"
          />
          <span v-if="fieldError('employee_id')" class="mt-1 block text-xs text-red-600">{{ fieldError('employee_id') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Full Name</span>
          <input
            v-model="form.full_name"
            required
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
            placeholder="Employee full name"
          />
          <span v-if="fieldError('full_name')" class="mt-1 block text-xs text-red-600">{{ fieldError('full_name') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Email</span>
          <input
            v-model="form.email"
            type="email"
            required
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
            placeholder="employee@example.com"
          />
          <span v-if="fieldError('email')" class="mt-1 block text-xs text-red-600">{{ fieldError('email') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Join Date</span>
          <input
            v-model="form.join_date"
            type="date"
            required
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          />
          <span v-if="fieldError('join_date')" class="mt-1 block text-xs text-red-600">{{ fieldError('join_date') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Branch / Outlet / Area</span>
          <select
            v-model="form.branch_id"
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          >
            <option value="">Select branch</option>
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
              {{ branch.name }}
            </option>
          </select>
          <span v-if="fieldError('branch_id')" class="mt-1 block text-xs text-red-600">{{ fieldError('branch_id') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Department</span>
          <select
            v-model="form.department_id"
            required
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          >
            <option value="">Select department</option>
            <option v-for="department in departments" :key="department.id" :value="department.id">
              {{ department.name }}
            </option>
          </select>
          <span v-if="fieldError('department_id')" class="mt-1 block text-xs text-red-600">{{ fieldError('department_id') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Position</span>
          <select
            v-model="form.position_id"
            required
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          >
            <option value="">Select position</option>
            <option v-for="position in filteredPositions" :key="position.id" :value="position.id">
              {{ position.name }}
            </option>
          </select>
          <span v-if="fieldError('position_id')" class="mt-1 block text-xs text-red-600">{{ fieldError('position_id') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Direct Supervisor</span>
          <select
            v-model="form.supervisor_id"
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          >
            <option value="">No supervisor</option>
            <option v-for="supervisor in supervisors" :key="supervisor.id" :value="supervisor.id">
              {{ supervisor.full_name }} - {{ supervisor.position?.name ?? supervisor.employee_id }}
            </option>
          </select>
          <span v-if="fieldError('supervisor_id')" class="mt-1 block text-xs text-red-600">{{ fieldError('supervisor_id') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Employment Status</span>
          <select
            v-model="form.employment_status"
            required
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          >
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <span v-if="fieldError('employment_status')" class="mt-1 block text-xs text-red-600">{{ fieldError('employment_status') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Employment Type</span>
          <select
            v-model="form.employment_type"
            required
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          >
            <option value="probation">Probation</option>
            <option value="pkwt">PKWT</option>
            <option value="pkwtt">PKWTT</option>
          </select>
          <span v-if="fieldError('employment_type')" class="mt-1 block text-xs text-red-600">{{ fieldError('employment_type') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Contract Start Date</span>
          <input
            v-model="form.contract_start_date"
            type="date"
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          />
          <span v-if="fieldError('contract_start_date')" class="mt-1 block text-xs text-red-600">{{ fieldError('contract_start_date') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Contract End Date</span>
          <input
            v-model="form.contract_end_date"
            type="date"
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          />
          <span v-if="fieldError('contract_end_date')" class="mt-1 block text-xs text-red-600">{{ fieldError('contract_end_date') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Basic Salary</span>
          <input
            v-model="form.basic_salary"
            type="number"
            min="0"
            step="1000"
            required
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
            placeholder="12000000"
          />
          <span v-if="fieldError('basic_salary')" class="mt-1 block text-xs text-red-600">{{ fieldError('basic_salary') }}</span>
        </label>

        <div class="border-t border-hris-border pt-4 md:col-span-2">
          <h3 class="font-semibold">Indonesian Administration</h3>
        </div>

        <label class="block">
          <span class="text-sm font-medium">NIK KTP</span>
          <input
            v-model="form.nik_ktp"
            inputmode="numeric"
            maxlength="16"
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
            placeholder="16 digit NIK"
          />
          <span v-if="fieldError('nik_ktp')" class="mt-1 block text-xs text-red-600">{{ fieldError('nik_ktp') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">NPWP</span>
          <input
            v-model="form.npwp"
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
            placeholder="01.234.567.8-999.000"
          />
          <span v-if="fieldError('npwp')" class="mt-1 block text-xs text-red-600">{{ fieldError('npwp') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">BPJS Kesehatan Number</span>
          <input
            v-model="form.bpjs_kesehatan_number"
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          />
          <span v-if="fieldError('bpjs_kesehatan_number')" class="mt-1 block text-xs text-red-600">{{ fieldError('bpjs_kesehatan_number') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">BPJS Ketenagakerjaan Number</span>
          <input
            v-model="form.bpjs_ketenagakerjaan_number"
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          />
          <span v-if="fieldError('bpjs_ketenagakerjaan_number')" class="mt-1 block text-xs text-red-600">{{ fieldError('bpjs_ketenagakerjaan_number') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Marital Tax Status</span>
          <select
            v-model="form.tax_marital_status"
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          >
            <option value="">Not set</option>
            <option value="TK">TK</option>
            <option value="K">K</option>
          </select>
          <span v-if="fieldError('tax_marital_status')" class="mt-1 block text-xs text-red-600">{{ fieldError('tax_marital_status') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Number of Dependents</span>
          <input
            v-model.number="form.tax_dependents"
            type="number"
            min="0"
            max="3"
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          />
          <span v-if="fieldError('tax_dependents')" class="mt-1 block text-xs text-red-600">{{ fieldError('tax_dependents') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Bank Name</span>
          <input
            v-model="form.bank_name"
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
            placeholder="BCA, Mandiri, BRI"
          />
          <span v-if="fieldError('bank_name')" class="mt-1 block text-xs text-red-600">{{ fieldError('bank_name') }}</span>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Bank Account Number</span>
          <input
            v-model="form.bank_account_number"
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          />
          <span v-if="fieldError('bank_account_number')" class="mt-1 block text-xs text-red-600">{{ fieldError('bank_account_number') }}</span>
        </label>

        <label class="block md:col-span-2">
          <span class="text-sm font-medium">Account Holder Name</span>
          <input
            v-model="form.bank_account_holder_name"
            class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          />
          <span v-if="fieldError('bank_account_holder_name')" class="mt-1 block text-xs text-red-600">{{ fieldError('bank_account_holder_name') }}</span>
        </label>
      </div>

      <label class="block">
        <span class="text-sm font-medium">Linked User ID</span>
        <input
          v-model="form.user_id"
          type="number"
          min="1"
          class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
          placeholder="Optional"
        />
        <span v-if="fieldError('user_id')" class="mt-1 block text-xs text-red-600">{{ fieldError('user_id') }}</span>
      </label>

      <div class="flex justify-end gap-3 border-t border-hris-border pt-5">
        <RouterLink
          to="/employees"
          class="rounded-md border border-hris-border px-4 py-2 text-sm font-medium hover:bg-hris-surface"
        >
          Cancel
        </RouterLink>
        <button
          type="submit"
          class="rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="saving"
        >
          {{ saving ? 'Saving...' : 'Save Employee' }}
        </button>
      </div>
    </form>
  </section>
</template>
