<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import { deactivateEmployee, getEmployee } from '@/services/employees'

const route = useRoute()
const router = useRouter()
const employee = ref(null)
const loading = ref(false)
const error = ref(null)
const confirmOpen = ref(false)
const deactivating = ref(false)

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
  }[type] ?? 'Not assigned'
}

async function loadEmployee() {
  loading.value = true
  error.value = null

  try {
    employee.value = await getEmployee(route.params.id)
  } catch {
    error.value = 'Unable to load employee'
  } finally {
    loading.value = false
  }
}

async function confirmDeactivate() {
  deactivating.value = true

  try {
    employee.value = await deactivateEmployee(route.params.id)
    confirmOpen.value = false
  } catch {
    error.value = 'Unable to deactivate employee'
  } finally {
    deactivating.value = false
  }
}

onMounted(loadEmployee)
</script>

<template>
  <section class="mx-auto max-w-5xl">
    <div class="flex flex-col justify-between gap-4 border-b border-hris-border pb-5 sm:flex-row">
      <div>
        <p class="text-xs font-semibold uppercase text-hris-accent">People</p>
        <h2 class="mt-1 text-2xl font-semibold">Employee Detail</h2>
      </div>

      <button
        type="button"
        class="self-start rounded-md border border-hris-border px-4 py-2 text-sm font-medium hover:bg-hris-panel"
        @click="router.back()"
      >
        Back
      </button>
    </div>

    <div v-if="loading" class="mt-6 rounded-md border border-hris-border bg-hris-panel p-5 text-sm text-hris-muted">
      Loading employee...
    </div>

    <div v-else-if="error" class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>

    <div v-else-if="employee" class="mt-6 grid gap-5 lg:grid-cols-[1fr_320px]">
      <div class="rounded-md border border-hris-border bg-hris-panel p-5">
        <div class="flex flex-col justify-between gap-3 border-b border-hris-border pb-5 sm:flex-row">
          <div>
            <h3 class="text-xl font-semibold">{{ employee.full_name }}</h3>
            <p class="mt-1 text-sm text-hris-muted">{{ employee.employee_id }} - {{ employee.email }}</p>
          </div>
          <span
            class="self-start rounded-md px-2 py-1 text-xs font-semibold"
            :class="
              employee.employment_status === 'active'
                ? 'bg-emerald-50 text-emerald-700'
                : 'bg-slate-100 text-slate-600'
            "
          >
            {{ employee.employment_status }}
          </span>
        </div>

        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-xs uppercase text-hris-muted">Branch / Outlet / Area</dt>
            <dd class="mt-1 font-medium">{{ employee.branch?.name ?? 'Not assigned' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Department</dt>
            <dd class="mt-1 font-medium">{{ employee.department?.name }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Position</dt>
            <dd class="mt-1 font-medium">{{ employee.position?.name }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Join Date</dt>
            <dd class="mt-1 font-medium">{{ employee.join_date }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Employment Type</dt>
            <dd class="mt-1 font-medium">{{ employmentTypeLabel(employee.employment_type) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Contract Start</dt>
            <dd class="mt-1 font-medium">{{ employee.contract_start_date ?? 'Not set' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Contract End</dt>
            <dd class="mt-1 font-medium">{{ employee.contract_end_date ?? 'Not set' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Basic Salary</dt>
            <dd class="mt-1 font-medium">{{ currency(employee.basic_salary) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Linked User</dt>
            <dd class="mt-1 font-medium">{{ employee.user?.email ?? 'Not linked' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Direct Supervisor</dt>
            <dd class="mt-1 font-medium">{{ employee.supervisor?.full_name ?? 'Not assigned' }}</dd>
          </div>
          <div class="border-t border-hris-border pt-4 sm:col-span-2">
            <dt class="font-semibold">Indonesian Administration</dt>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">NIK KTP</dt>
            <dd class="mt-1 font-medium">{{ employee.nik_ktp ?? 'Not set' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">NPWP</dt>
            <dd class="mt-1 font-medium">{{ employee.npwp ?? 'Not set' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">BPJS Kesehatan</dt>
            <dd class="mt-1 font-medium">{{ employee.bpjs_kesehatan_number ?? 'Not set' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">BPJS Ketenagakerjaan</dt>
            <dd class="mt-1 font-medium">{{ employee.bpjs_ketenagakerjaan_number ?? 'Not set' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Tax Status</dt>
            <dd class="mt-1 font-medium">
              {{ employee.tax_marital_status ? `${employee.tax_marital_status}/${employee.tax_dependents}` : 'Not set' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Bank</dt>
            <dd class="mt-1 font-medium">{{ employee.bank_name ?? 'Not set' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Bank Account</dt>
            <dd class="mt-1 font-medium">{{ employee.bank_account_number ?? 'Not set' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Account Holder</dt>
            <dd class="mt-1 font-medium">{{ employee.bank_account_holder_name ?? 'Not set' }}</dd>
          </div>
        </dl>
      </div>

      <aside class="rounded-md border border-hris-border bg-hris-panel p-5">
        <h3 class="font-semibold">Actions</h3>
        <div class="mt-4 space-y-3">
          <RouterLink
            :to="`/employees/${employee.id}/edit`"
            class="block rounded-md bg-hris-primary px-4 py-2 text-center text-sm font-semibold text-white hover:bg-hris-primary-dark"
          >
            Edit Employee
          </RouterLink>
          <button
            type="button"
            class="w-full rounded-md border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="employee.employment_status === 'inactive'"
            @click="confirmOpen = true"
          >
            Deactivate Employee
          </button>
        </div>
      </aside>
    </div>

    <ConfirmationModal
      :open="confirmOpen"
      title="Deactivate employee"
      :message="`Deactivate ${employee?.full_name}? This keeps the record but marks employment as inactive.`"
      confirm-label="Deactivate"
      :loading="deactivating"
      @cancel="confirmOpen = false"
      @confirm="confirmDeactivate"
    />
  </section>
</template>
