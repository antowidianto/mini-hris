<script setup>
import { onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationControls from '@/components/PaginationControls.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import {
  deactivateEmployee,
  getBranches,
  getDepartments,
  getEmployees,
  getPositions,
} from '@/services/employees'

const filters = reactive({
  search: '',
  branch_id: '',
  department_id: '',
  position_id: '',
  employment_status: '',
  employment_type: '',
  per_page: 10,
  page: 1,
})

const employees = ref([])
const branches = ref([])
const departments = ref([])
const positions = ref([])
const meta = ref(null)
const loading = ref(false)
const error = ref(null)
const deactivating = ref(false)
const selectedEmployee = ref(null)

function currency(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(value ?? 0))
}

function employmentTypeLabel(type) {
  return {
    probation: 'Probation',
    pkwt: 'PKWT',
    pkwtt: 'PKWTT',
  }[type] ?? '-'
}

async function loadEmployees(page = 1) {
  loading.value = true
  error.value = null
  filters.page = page

  try {
    const data = await getEmployees(filters)
    employees.value = data.employees
    meta.value = data.meta
  } catch {
    error.value = 'Unable to load employees'
  } finally {
    loading.value = false
  }
}

async function loadLookups() {
  try {
    const [branchData, departmentData, positionData] = await Promise.all([
      getBranches(),
      getDepartments(),
      getPositions(),
    ])
    branches.value = branchData
    departments.value = departmentData
    positions.value = positionData
  } catch {
    error.value = 'Unable to load employee filters'
  }
}

function resetFilters() {
  filters.search = ''
  filters.branch_id = ''
  filters.department_id = ''
  filters.position_id = ''
  filters.employment_status = ''
  filters.employment_type = ''
  filters.per_page = 10
  loadEmployees(1)
}

function requestDeactivate(employee) {
  selectedEmployee.value = employee
}

async function confirmDeactivate() {
  if (!selectedEmployee.value) {
    return
  }

  deactivating.value = true

  try {
    await deactivateEmployee(selectedEmployee.value.id)
    selectedEmployee.value = null
    await loadEmployees(meta.value?.current_page ?? 1)
  } catch {
    error.value = 'Unable to deactivate employee'
  } finally {
    deactivating.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadLookups(), loadEmployees()])
})
</script>

<template>
  <section class="mx-auto max-w-7xl">
    <PageHeader title="Employees" description="Manage employee profiles and account links.">
      <template #actions>
        <RouterLink
          to="/employees/new"
          class="self-start rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark"
        >
          Add Employee
        </RouterLink>
      </template>
    </PageHeader>

    <form class="ui-filter-bar mt-5 grid gap-3 rounded-md border border-hris-border bg-hris-panel p-4 md:grid-cols-8" @submit.prevent="loadEmployees(1)">
      <input
        v-model="filters.search"
        type="search"
        class="rounded-md border border-hris-border bg-hris-panel px-3 py-2 text-sm md:col-span-2"
        placeholder="Search ID, name, email, NIK, or NPWP"
      />

      <select
        v-model="filters.branch_id"
        class="rounded-md border border-hris-border bg-hris-panel px-3 py-2 text-sm"
      >
        <option value="">All branches</option>
        <option v-for="branch in branches" :key="branch.id" :value="branch.id">
          {{ branch.name }}
        </option>
      </select>

      <select
        v-model="filters.department_id"
        class="rounded-md border border-hris-border bg-hris-panel px-3 py-2 text-sm"
      >
        <option value="">All departments</option>
        <option v-for="department in departments" :key="department.id" :value="department.id">
          {{ department.name }}
        </option>
      </select>

      <select
        v-model="filters.position_id"
        class="rounded-md border border-hris-border bg-hris-panel px-3 py-2 text-sm"
      >
        <option value="">All positions</option>
        <option v-for="position in positions" :key="position.id" :value="position.id">
          {{ position.name }}
        </option>
      </select>

      <select
        v-model="filters.employment_status"
        class="rounded-md border border-hris-border bg-hris-panel px-3 py-2 text-sm"
      >
        <option value="">All statuses</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>

      <select
        v-model="filters.employment_type"
        class="rounded-md border border-hris-border bg-hris-panel px-3 py-2 text-sm"
      >
        <option value="">All types</option>
        <option value="probation">Probation</option>
        <option value="pkwt">PKWT</option>
        <option value="pkwtt">PKWTT</option>
      </select>

      <div class="flex gap-2">
        <button
          type="submit"
          class="flex-1 rounded-md bg-hris-primary px-3 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark"
        >
          Apply
        </button>
        <button
          type="button"
          class="rounded-md border border-hris-border px-3 py-2 text-sm font-medium hover:bg-hris-panel"
          @click="resetFilters"
        >
          Reset
        </button>
      </div>
    </form>

    <div v-if="error" class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>

    <div class="ui-table-card mt-5 overflow-hidden rounded-md border border-hris-border bg-hris-panel">
      <LoadingState v-if="loading" label="Loading employees..." />
      <EmptyState v-else-if="employees.length === 0" title="No employees found" message="Adjust filters or add a new employee record." />

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-hris-border text-sm">
          <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
            <tr>
              <th class="px-4 py-3 font-semibold">Employee</th>
              <th class="px-4 py-3 font-semibold">Branch / Outlet</th>
              <th class="px-4 py-3 font-semibold">Department</th>
              <th class="px-4 py-3 font-semibold">Position</th>
              <th class="px-4 py-3 font-semibold">Type</th>
              <th class="px-4 py-3 font-semibold">Status</th>
              <th class="px-4 py-3 font-semibold">Salary</th>
              <th class="px-4 py-3 font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-hris-border">
            <tr v-for="employee in employees" :key="employee.id">
              <td class="px-4 py-3">
                <p class="font-medium">{{ employee.full_name }}</p>
                <p class="text-xs text-hris-muted">{{ employee.employee_id }} - {{ employee.email }}</p>
              </td>
              <td class="px-4 py-3">{{ employee.branch?.name ?? '-' }}</td>
              <td class="px-4 py-3">{{ employee.department?.name }}</td>
              <td class="px-4 py-3">{{ employee.position?.name }}</td>
              <td class="px-4 py-3">{{ employmentTypeLabel(employee.employment_type) }}</td>
              <td class="px-4 py-3">
                <StatusBadge :status="employee.employment_status" />
              </td>
              <td class="px-4 py-3">{{ currency(employee.basic_salary) }}</td>
              <td class="px-4 py-3">
                <div class="flex gap-2">
                  <RouterLink class="text-hris-primary hover:underline" :to="`/employees/${employee.id}`">
                    View
                  </RouterLink>
                  <RouterLink class="text-hris-primary hover:underline" :to="`/employees/${employee.id}/edit`">
                    Edit
                  </RouterLink>
                  <button
                    type="button"
                    class="text-red-600 hover:underline disabled:text-hris-muted"
                    :disabled="employee.employment_status === 'inactive'"
                    @click="requestDeactivate(employee)"
                  >
                    Deactivate
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <PaginationControls :meta="meta" @change="loadEmployees" />

    <ConfirmationModal
      :open="Boolean(selectedEmployee)"
      title="Deactivate employee"
      :message="`Deactivate ${selectedEmployee?.full_name}? This keeps the record but marks employment as inactive.`"
      confirm-label="Deactivate"
      :loading="deactivating"
      @cancel="selectedEmployee = null"
      @confirm="confirmDeactivate"
    />
  </section>
</template>
