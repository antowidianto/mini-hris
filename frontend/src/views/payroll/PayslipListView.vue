<script setup>
import { onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'

import { getPayslips } from '@/services/payroll'

const currentDate = new Date()

const filters = reactive({
  period_year: '',
  period_month: '',
  per_page: 10,
  page: 1,
})

const payrolls = ref([])
const meta = ref(null)
const loading = ref(false)
const error = ref(null)

function currency(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(value ?? 0))
}

async function loadPayslips(page = 1) {
  loading.value = true
  error.value = null
  filters.page = page

  try {
    const data = await getPayslips(filters)
    payrolls.value = data.payrolls
    meta.value = data.meta
  } catch {
    error.value = 'Unable to load payslips'
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.period_year = ''
  filters.period_month = ''
  filters.per_page = 10
  loadPayslips(1)
}

onMounted(loadPayslips)
</script>

<template>
  <section class="mx-auto max-w-6xl">
    <div class="border-b border-hris-border pb-5">
      <p class="text-xs font-semibold uppercase text-hris-accent">Payroll</p>
      <h2 class="mt-1 text-2xl font-semibold">Payslips</h2>
      <p class="mt-1 text-sm text-hris-muted">View your monthly salary records.</p>
    </div>

    <div v-if="error" class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>

    <form class="mt-5 grid gap-3 rounded-md border border-hris-border bg-hris-panel p-4 sm:grid-cols-4" @submit.prevent="loadPayslips(1)">
      <input v-model.number="filters.period_year" type="number" min="2020" max="2100" class="rounded-md border border-hris-border px-3 py-2 text-sm" :placeholder="String(currentDate.getFullYear())" aria-label="Filter year" />
      <input v-model.number="filters.period_month" type="number" min="1" max="12" class="rounded-md border border-hris-border px-3 py-2 text-sm" :placeholder="String(currentDate.getMonth() + 1)" aria-label="Filter month" />
      <button type="submit" class="rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark">
        Apply
      </button>
      <button type="button" class="rounded-md border border-hris-border px-4 py-2 text-sm font-medium hover:bg-hris-surface" @click="resetFilters">
        Reset
      </button>
    </form>

    <div class="mt-5 overflow-hidden rounded-md border border-hris-border bg-hris-panel">
      <div v-if="loading" class="p-6 text-sm text-hris-muted">Loading payslips...</div>
      <div v-else-if="payrolls.length === 0" class="p-6 text-sm text-hris-muted">No payslips found.</div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-hris-border text-sm">
          <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
            <tr>
              <th class="px-4 py-3 font-semibold">Period</th>
              <th class="px-4 py-3 font-semibold">Gross Salary</th>
              <th class="px-4 py-3 font-semibold">Deductions</th>
              <th class="px-4 py-3 font-semibold">Take Home Pay</th>
              <th class="px-4 py-3 font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-hris-border">
            <tr v-for="payroll in payrolls" :key="payroll.id">
              <td class="px-4 py-3">{{ payroll.period_label }}</td>
              <td class="px-4 py-3">{{ currency(payroll.gross_salary) }}</td>
              <td class="px-4 py-3">{{ currency(payroll.total_deductions) }}</td>
              <td class="px-4 py-3 font-semibold">{{ currency(payroll.take_home_pay ?? payroll.net_salary) }}</td>
              <td class="px-4 py-3">
                <RouterLink class="text-hris-primary hover:underline" :to="`/payslips/${payroll.id}`">
                  View
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="meta" class="mt-4 flex items-center justify-between gap-3 text-sm">
      <p class="text-hris-muted">Page {{ meta.current_page }} of {{ meta.last_page }}</p>
      <div class="flex gap-2">
        <button type="button" class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50" :disabled="meta.current_page <= 1" @click="loadPayslips(meta.current_page - 1)">
          Previous
        </button>
        <button type="button" class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50" :disabled="meta.current_page >= meta.last_page" @click="loadPayslips(meta.current_page + 1)">
          Next
        </button>
      </div>
    </div>
  </section>
</template>
