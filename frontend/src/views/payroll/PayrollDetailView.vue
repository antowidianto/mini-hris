<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { getPayroll, getPayslip } from '@/services/payroll'

const props = defineProps({
  mode: {
    type: String,
    default: 'payroll',
  },
})

const route = useRoute()
const router = useRouter()
const payroll = ref(null)
const loading = ref(false)
const error = ref(null)

function currency(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(value ?? 0))
}

async function loadPayroll() {
  loading.value = true
  error.value = null

  try {
    payroll.value = props.mode === 'payslip'
      ? await getPayslip(route.params.id)
      : await getPayroll(route.params.id)
  } catch {
    error.value = 'Unable to load payroll detail'
  } finally {
    loading.value = false
  }
}

onMounted(loadPayroll)
</script>

<template>
  <section class="mx-auto max-w-5xl">
    <div class="flex flex-col justify-between gap-4 border-b border-hris-border pb-5 sm:flex-row">
      <div>
        <p class="text-xs font-semibold uppercase text-hris-accent">Payroll</p>
        <h2 class="mt-1 text-2xl font-semibold">{{ mode === 'payslip' ? 'Payslip Detail' : 'Payroll Detail' }}</h2>
      </div>
      <button type="button" class="self-start rounded-md border border-hris-border px-4 py-2 text-sm font-medium hover:bg-hris-panel" @click="router.back()">
        Back
      </button>
    </div>

    <div v-if="loading" class="mt-6 rounded-md border border-hris-border bg-hris-panel p-5 text-sm text-hris-muted">
      Loading payroll detail...
    </div>

    <div v-else-if="error" class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>

    <div v-else-if="payroll" class="mt-6 grid gap-5 lg:grid-cols-[1fr_320px]">
      <div class="rounded-md border border-hris-border bg-hris-panel p-5">
        <div class="flex flex-col justify-between gap-3 border-b border-hris-border pb-5 sm:flex-row">
          <div>
            <h3 class="text-xl font-semibold">{{ payroll.employee?.full_name }}</h3>
            <p class="mt-1 text-sm text-hris-muted">
              {{ payroll.employee?.employee_id }} - {{ payroll.period_label }}
            </p>
          </div>
          <div class="text-left sm:text-right">
            <p class="text-xs uppercase text-hris-muted">Take Home Pay</p>
            <p class="text-xl font-semibold">{{ currency(payroll.take_home_pay ?? payroll.net_salary) }}</p>
          </div>
        </div>

        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-xs uppercase text-hris-muted">Basic Salary</dt>
            <dd class="mt-1 font-medium">{{ currency(payroll.basic_salary) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Fixed Allowance</dt>
            <dd class="mt-1 font-medium">{{ currency(payroll.fixed_allowance) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Non-Fixed Allowance</dt>
            <dd class="mt-1 font-medium">{{ currency(payroll.non_fixed_allowance) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Meal Allowance</dt>
            <dd class="mt-1 font-medium">{{ currency(payroll.meal_allowance) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Transport Allowance</dt>
            <dd class="mt-1 font-medium">{{ currency(payroll.transport_allowance) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Total Allowance</dt>
            <dd class="mt-1 font-medium">{{ currency(payroll.allowance) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Gross Salary</dt>
            <dd class="mt-1 font-medium">{{ currency(payroll.gross_salary) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Attendance Deduction</dt>
            <dd class="mt-1 font-medium">{{ currency(payroll.attendance_deduction) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Late Deduction</dt>
            <dd class="mt-1 font-medium">{{ currency(payroll.late_deduction) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Unpaid Leave Deduction</dt>
            <dd class="mt-1 font-medium">{{ currency(payroll.unpaid_leave_deduction) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">BPJS Employee</dt>
            <dd class="mt-1 font-medium">{{ currency(payroll.total_employee_bpjs) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">BPJS Employer</dt>
            <dd class="mt-1 font-medium">{{ currency(payroll.total_employer_bpjs) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">PPh 21 Deduction</dt>
            <dd class="mt-1 font-medium">{{ currency(payroll.pph21_deduction) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Other Deduction</dt>
            <dd class="mt-1 font-medium">{{ currency(payroll.other_deduction ?? payroll.deduction) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Generated By</dt>
            <dd class="mt-1 font-medium">{{ payroll.generator?.name ?? '--' }}</dd>
          </div>
        </dl>
      </div>

      <aside class="rounded-md border border-hris-border bg-hris-panel p-5">
        <h3 class="font-semibold">Calculation Inputs</h3>
        <dl class="mt-4 space-y-4 text-sm">
          <div>
            <dt class="text-xs uppercase text-hris-muted">Absent Days</dt>
            <dd class="mt-1 font-medium">{{ payroll.absent_days }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Late Days</dt>
            <dd class="mt-1 font-medium">{{ payroll.late_days }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Unpaid Leave Days</dt>
            <dd class="mt-1 font-medium">{{ payroll.unpaid_leave_days }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Generated At</dt>
            <dd class="mt-1 font-medium">{{ payroll.generated_at ?? '--' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Approval Status</dt>
            <dd class="mt-1 font-medium">{{ payroll.approval_status ?? '--' }}</dd>
          </div>
          <div v-if="payroll.current_approval_step">
            <dt class="text-xs uppercase text-hris-muted">Current Approval</dt>
            <dd class="mt-1 font-medium">Step {{ payroll.current_approval_step.step_order }} - {{ payroll.current_approval_step.role }}</dd>
          </div>
          <div v-for="step in payroll.approval_steps" :key="step.id">
            <dt class="text-xs uppercase text-hris-muted">Approval Step {{ step.step_order }}</dt>
            <dd class="mt-1 font-medium">{{ step.role }} - {{ step.status }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Work Days Setting</dt>
            <dd class="mt-1 font-medium">{{ payroll.settings_snapshot?.payroll_work_days_per_month ?? '--' }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase text-hris-muted">Late Deduction Setting</dt>
            <dd class="mt-1 font-medium">{{ currency(payroll.settings_snapshot?.late_deduction_amount) }}</dd>
          </div>
        </dl>
      </aside>
    </div>
  </section>
</template>
