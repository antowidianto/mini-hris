<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

import {
  getLeaveBalances,
  getLeaveRequests,
  getLeaveTypes,
  submitLeaveRequest,
} from '@/services/leaves'

const filters = reactive({
  status: '',
  leave_type_id: '',
  per_page: 10,
  page: 1,
})

const form = reactive({
  leave_type_id: '',
  start_date: '',
  end_date: '',
  reason: '',
})

const leaveTypes = ref([])
const balances = ref([])
const leaveRequests = ref([])
const meta = ref(null)
const loading = ref(false)
const submitting = ref(false)
const error = ref(null)
const success = ref(null)

const requestedDays = computed(() => {
  if (!form.start_date || !form.end_date) {
    return 0
  }

  const startDate = new Date(`${form.start_date}T00:00:00`)
  const endDate = new Date(`${form.end_date}T00:00:00`)

  if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime()) || endDate < startDate) {
    return 0
  }

  return Math.floor((endDate - startDate) / 86_400_000) + 1
})

function statusClass(status) {
  return {
    pending: 'bg-amber-50 text-amber-700',
    approved: 'bg-emerald-50 text-emerald-700',
    rejected: 'bg-red-50 text-red-700',
  }[status] ?? 'bg-slate-100 text-slate-600'
}

function workflowLabel(leaveRequest) {
  if (leaveRequest.status === 'rejected' && leaveRequest.supervisor_status === 'rejected') {
    return 'Rejected by Supervisor'
  }

  if (leaveRequest.status === 'rejected') {
    return 'Rejected by HR'
  }

  if (leaveRequest.status === 'approved') {
    return 'Approved'
  }

  if (leaveRequest.supervisor_status === 'pending') {
    return 'Waiting Supervisor'
  }

  return 'Waiting HR'
}

function requestError(requestError, fallback) {
  const errors = requestError.response?.data?.errors
  const firstError = errors ? Object.values(errors)[0]?.[0] : null

  return firstError ?? requestError.response?.data?.message ?? fallback
}

async function loadLookups() {
  try {
    const [types, balanceData] = await Promise.all([getLeaveTypes(), getLeaveBalances()])
    leaveTypes.value = types
    balances.value = balanceData
  } catch {
    error.value = 'Unable to load leave balances'
  }
}

async function loadRequests(page = 1) {
  loading.value = true
  error.value = null
  filters.page = page

  try {
    const data = await getLeaveRequests(filters)
    leaveRequests.value = data.leave_requests
    meta.value = data.meta
  } catch {
    error.value = 'Unable to load leave requests'
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  submitting.value = true
  error.value = null
  success.value = null

  try {
    await submitLeaveRequest(form)
    form.leave_type_id = ''
    form.start_date = ''
    form.end_date = ''
    form.reason = ''
    success.value = 'Leave request submitted for approval.'
    await Promise.all([loadLookups(), loadRequests(1)])
  } catch (submitError) {
    error.value = requestError(submitError, 'Unable to submit leave request')
  } finally {
    submitting.value = false
  }
}

function resetFilters() {
  filters.status = ''
  filters.leave_type_id = ''
  filters.per_page = 10
  loadRequests(1)
}

onMounted(async () => {
  await Promise.all([loadLookups(), loadRequests()])
})
</script>

<template>
  <section class="mx-auto max-w-7xl">
    <div class="border-b border-hris-border pb-5">
      <p class="text-xs font-semibold uppercase text-hris-accent">Leave</p>
      <h2 class="mt-1 text-2xl font-semibold">My Leave</h2>
      <p class="mt-1 text-sm text-hris-muted">Submit leave requests and track approval status.</p>
    </div>

    <div v-if="error" class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>
    <div v-if="success" class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ success }}
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-[360px_1fr]">
      <aside class="space-y-4">
        <form class="rounded-md border border-hris-border bg-hris-panel p-5" @submit.prevent="handleSubmit">
          <h3 class="font-semibold">New Request</h3>

          <label class="mt-4 block">
            <span class="text-xs font-medium text-hris-muted">Leave type</span>
            <select v-model="form.leave_type_id" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm">
              <option value="">Select leave type</option>
              <option v-for="leaveType in leaveTypes" :key="leaveType.id" :value="leaveType.id">
                {{ leaveType.name }}
              </option>
            </select>
          </label>

          <div class="mt-3 grid grid-cols-2 gap-3">
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">Start</span>
              <input v-model="form.start_date" type="date" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
            </label>
            <label class="block">
              <span class="text-xs font-medium text-hris-muted">End</span>
              <input v-model="form.end_date" type="date" class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" />
            </label>
          </div>

          <p class="mt-2 text-xs text-hris-muted">Requested days: {{ requestedDays }}</p>

          <label class="mt-3 block">
            <span class="text-xs font-medium text-hris-muted">Reason</span>
            <textarea
              v-model="form.reason"
              rows="4"
              class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
              placeholder="Reason for leave"
            ></textarea>
          </label>

          <button
            type="submit"
            class="mt-4 w-full rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="submitting"
          >
            {{ submitting ? 'Submitting...' : 'Submit Request' }}
          </button>
        </form>

        <div class="rounded-md border border-hris-border bg-hris-panel p-5">
          <h3 class="font-semibold">Balances</h3>
          <div v-if="balances.length === 0" class="mt-4 text-sm text-hris-muted">No leave balances found.</div>
          <div v-else class="mt-4 space-y-3">
            <div v-for="balance in balances" :key="balance.id" class="rounded-md bg-hris-surface p-3 text-sm">
              <div class="flex items-center justify-between gap-3">
                <p class="font-medium">{{ balance.leave_type?.name }}</p>
                <span class="text-xs text-hris-muted">{{ balance.year }}</span>
              </div>
              <div class="mt-2 grid grid-cols-3 gap-2 text-xs">
                <div>
                  <p class="text-hris-muted">Entitled</p>
                  <p class="font-semibold">{{ balance.entitlement_days }}</p>
                </div>
                <div>
                  <p class="text-hris-muted">Used</p>
                  <p class="font-semibold">{{ balance.used_days }}</p>
                </div>
                <div>
                  <p class="text-hris-muted">Remaining</p>
                  <p class="font-semibold">{{ balance.remaining_days }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </aside>

      <div>
        <form class="grid gap-3 rounded-md border border-hris-border bg-hris-panel p-4 sm:grid-cols-4" @submit.prevent="loadRequests(1)">
          <select v-model="filters.status" class="rounded-md border border-hris-border px-3 py-2 text-sm">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
          <select v-model="filters.leave_type_id" class="rounded-md border border-hris-border px-3 py-2 text-sm">
            <option value="">All types</option>
            <option v-for="leaveType in leaveTypes" :key="leaveType.id" :value="leaveType.id">
              {{ leaveType.name }}
            </option>
          </select>
          <button type="submit" class="rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark">
            Apply
          </button>
          <button type="button" class="rounded-md border border-hris-border px-4 py-2 text-sm font-medium hover:bg-hris-surface" @click="resetFilters">
            Reset
          </button>
        </form>

        <div class="mt-4 overflow-hidden rounded-md border border-hris-border bg-hris-panel">
          <div v-if="loading" class="p-6 text-sm text-hris-muted">Loading leave requests...</div>
          <div v-else-if="leaveRequests.length === 0" class="p-6 text-sm text-hris-muted">No leave requests found.</div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-hris-border text-sm">
              <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
                <tr>
                  <th class="px-4 py-3 font-semibold">Leave</th>
                  <th class="px-4 py-3 font-semibold">Dates</th>
                  <th class="px-4 py-3 font-semibold">Days</th>
                  <th class="px-4 py-3 font-semibold">Status</th>
                  <th class="px-4 py-3 font-semibold">Flow</th>
                  <th class="px-4 py-3 font-semibold">Decision</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-hris-border">
                <tr v-for="leaveRequest in leaveRequests" :key="leaveRequest.id">
                  <td class="px-4 py-3">
                    <p class="font-medium">{{ leaveRequest.leave_type?.name }}</p>
                    <p class="text-xs text-hris-muted">{{ leaveRequest.reason }}</p>
                  </td>
                  <td class="px-4 py-3">{{ leaveRequest.start_date }} to {{ leaveRequest.end_date }}</td>
                  <td class="px-4 py-3">{{ leaveRequest.total_days }}</td>
                  <td class="px-4 py-3">
                    <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="statusClass(leaveRequest.status)">
                      {{ leaveRequest.status }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <p class="text-sm font-medium">{{ workflowLabel(leaveRequest) }}</p>
                    <p class="text-xs text-hris-muted">Supervisor: {{ leaveRequest.supervisor_status }}</p>
                    <p class="text-xs text-hris-muted">HR: {{ leaveRequest.hr_status }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <p class="text-xs text-hris-muted">Supervisor: {{ leaveRequest.supervisor_approver?.name ?? '--' }}</p>
                    <p class="text-xs">{{ leaveRequest.supervisor_notes ?? '--' }}</p>
                    <p class="mt-1 text-xs text-hris-muted">HR: {{ leaveRequest.hr_approver?.name ?? leaveRequest.approver?.name ?? '--' }}</p>
                    <p class="text-xs">{{ leaveRequest.hr_notes ?? leaveRequest.approval_notes ?? '--' }}</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="meta" class="mt-4 flex items-center justify-between gap-3 text-sm">
          <p class="text-hris-muted">Page {{ meta.current_page }} of {{ meta.last_page }}</p>
          <div class="flex gap-2">
            <button type="button" class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50" :disabled="meta.current_page <= 1" @click="loadRequests(meta.current_page - 1)">
              Previous
            </button>
            <button type="button" class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50" :disabled="meta.current_page >= meta.last_page" @click="loadRequests(meta.current_page + 1)">
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
