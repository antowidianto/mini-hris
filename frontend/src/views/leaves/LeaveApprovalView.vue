<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

import ConfirmationModal from '@/components/ConfirmationModal.vue'
import { ADMIN_HR_ROLES } from '@/config/roles'
import {
  approveLeaveRequest,
  getLeaveApprovals,
  getLeaveTypes,
  getSupervisorLeaveApprovals,
  rejectLeaveRequest,
  supervisorApproveLeaveRequest,
  supervisorRejectLeaveRequest,
} from '@/services/leaves'
import { useAuthStore } from '@/stores/authStore'

const auth = useAuthStore()
const filters = reactive({
  status: 'pending',
  supervisor_status: '',
  hr_status: '',
  leave_type_id: '',
  per_page: 10,
  page: 1,
})

const stage = ref(ADMIN_HR_ROLES.includes(auth.role) ? 'hr' : 'supervisor')
const leaveTypes = ref([])
const leaveRequests = ref([])
const meta = ref(null)
const loading = ref(false)
const deciding = ref(false)
const decision = ref(null)
const notes = reactive({})
const error = ref(null)
const success = ref(null)
const canApproveAsHr = computed(() => ADMIN_HR_ROLES.includes(auth.role))
const canApproveAsSupervisor = computed(() => Boolean(auth.user?.employee?.has_direct_reports))

const decisionTitle = computed(() => {
  if (!decision.value) {
    return ''
  }

  const actor = stage.value === 'supervisor' ? 'supervisor' : 'HR'

  return decision.value.action === 'approve'
    ? `Approve as ${actor}`
    : `Reject as ${actor}`
})

const decisionMessage = computed(() => {
  if (!decision.value) {
    return ''
  }

  const employee = decision.value.request.employee?.full_name ?? 'this employee'

  return `${decision.value.action === 'approve' ? 'Approve' : 'Reject'} ${employee}'s leave request?`
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

function canUseStage(targetStage) {
  if (targetStage === 'hr') {
    return canApproveAsHr.value
  }

  return canApproveAsSupervisor.value
}

function requestError(requestError, fallback) {
  const errors = requestError.response?.data?.errors
  const firstError = errors ? Object.values(errors)[0]?.[0] : null

  return firstError ?? requestError.response?.data?.message ?? fallback
}

async function loadLookups() {
  try {
    leaveTypes.value = await getLeaveTypes()
  } catch {
    error.value = 'Unable to load leave types'
  }
}

async function loadApprovals(page = 1) {
  loading.value = true
  error.value = null
  filters.page = page

  try {
    const params = {
      ...filters,
      supervisor_status: filters.supervisor_status || undefined,
      hr_status: filters.hr_status || undefined,
    }
    const data = stage.value === 'supervisor'
      ? await getSupervisorLeaveApprovals(params)
      : await getLeaveApprovals(params)
    leaveRequests.value = data.leave_requests
    meta.value = data.meta
  } catch {
    error.value = 'Unable to load leave approvals'
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.status = 'pending'
  filters.supervisor_status = ''
  filters.hr_status = ''
  filters.leave_type_id = ''
  filters.per_page = 10
  loadApprovals(1)
}

function changeStage(nextStage) {
  stage.value = nextStage
  resetFilters()
}

function requestDecision(leaveRequest, action) {
  decision.value = { request: leaveRequest, action }
}

async function confirmDecision() {
  if (!decision.value) {
    return
  }

  deciding.value = true
  error.value = null
  success.value = null

  const leaveRequest = decision.value.request
  const payload = {
    approval_notes: notes[leaveRequest.id] ?? '',
  }

  try {
    if (decision.value.action === 'approve') {
      if (stage.value === 'supervisor') {
        await supervisorApproveLeaveRequest(leaveRequest.id, payload)
      } else {
        await approveLeaveRequest(leaveRequest.id, payload)
      }
      success.value = 'Leave request approved.'
    } else {
      if (stage.value === 'supervisor') {
        await supervisorRejectLeaveRequest(leaveRequest.id, payload)
      } else {
        await rejectLeaveRequest(leaveRequest.id, payload)
      }
      success.value = 'Leave request rejected.'
    }

    notes[leaveRequest.id] = ''
    decision.value = null
    await loadApprovals(meta.value?.current_page ?? 1)
  } catch (decisionError) {
    error.value = requestError(decisionError, 'Unable to update leave request')
  } finally {
    deciding.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadLookups(), loadApprovals()])
})
</script>

<template>
  <section class="mx-auto max-w-7xl">
    <div class="border-b border-hris-border pb-5">
      <p class="text-xs font-semibold uppercase text-hris-accent">Leave</p>
      <h2 class="mt-1 text-2xl font-semibold">Leave Approvals</h2>
      <p class="mt-1 text-sm text-hris-muted">Review employee leave requests and record approval decisions.</p>
    </div>

    <div v-if="error" class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>
    <div v-if="success" class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ success }}
    </div>

    <form class="mt-5 grid gap-3 rounded-md border border-hris-border bg-hris-panel p-4 lg:grid-cols-7" @submit.prevent="loadApprovals(1)">
      <select
        v-if="canUseStage('hr') && canUseStage('supervisor')"
        v-model="stage"
        class="rounded-md border border-hris-border px-3 py-2 text-sm"
        @change="changeStage(stage)"
      >
        <option value="supervisor">Supervisor approvals</option>
        <option value="hr">HR approvals</option>
      </select>
      <select v-model="filters.status" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
      </select>
      <select v-model="filters.supervisor_status" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">Supervisor status</option>
        <option value="pending">Supervisor pending</option>
        <option value="approved">Supervisor approved</option>
        <option value="rejected">Supervisor rejected</option>
      </select>
      <select v-model="filters.hr_status" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">HR status</option>
        <option value="pending">HR pending</option>
        <option value="approved">HR approved</option>
        <option value="rejected">HR rejected</option>
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

    <div class="mt-5 overflow-hidden rounded-md border border-hris-border bg-hris-panel">
      <div v-if="loading" class="p-6 text-sm text-hris-muted">Loading leave approvals...</div>
      <div v-else-if="leaveRequests.length === 0" class="p-6 text-sm text-hris-muted">No leave requests found.</div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-hris-border text-sm">
          <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
            <tr>
              <th class="px-4 py-3 font-semibold">Employee</th>
              <th class="px-4 py-3 font-semibold">Leave</th>
              <th class="px-4 py-3 font-semibold">Dates</th>
              <th class="px-4 py-3 font-semibold">Status</th>
              <th class="px-4 py-3 font-semibold">Flow</th>
              <th class="px-4 py-3 font-semibold">Notes</th>
              <th class="px-4 py-3 font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-hris-border">
            <tr v-for="leaveRequest in leaveRequests" :key="leaveRequest.id">
              <td class="px-4 py-3">
                <p class="font-medium">{{ leaveRequest.employee?.full_name }}</p>
                <p class="text-xs text-hris-muted">
                  {{ leaveRequest.employee?.employee_id }} - {{ leaveRequest.employee?.department?.name }}
                </p>
              </td>
              <td class="px-4 py-3">
                <p class="font-medium">{{ leaveRequest.leave_type?.name }}</p>
                <p class="text-xs text-hris-muted">{{ leaveRequest.reason }}</p>
              </td>
              <td class="px-4 py-3">
                <p>{{ leaveRequest.start_date }} to {{ leaveRequest.end_date }}</p>
                <p class="text-xs text-hris-muted">{{ leaveRequest.total_days }} day(s)</p>
              </td>
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
                <textarea
                  v-if="leaveRequest.status === 'pending'"
                  v-model="notes[leaveRequest.id]"
                  rows="2"
                  class="w-56 rounded-md border border-hris-border px-3 py-2 text-sm"
                  placeholder="Approval notes"
                ></textarea>
                <div v-else class="max-w-56 text-xs text-hris-muted">
                  <p>Supervisor: {{ leaveRequest.supervisor_notes ?? '--' }}</p>
                  <p>HR: {{ leaveRequest.hr_notes ?? leaveRequest.approval_notes ?? '--' }}</p>
                </div>
              </td>
              <td class="px-4 py-3">
                <div v-if="leaveRequest.status === 'pending'" class="flex gap-2">
                  <button type="button" class="text-emerald-700 hover:underline" @click="requestDecision(leaveRequest, 'approve')">
                    Approve
                  </button>
                  <button type="button" class="text-red-600 hover:underline" @click="requestDecision(leaveRequest, 'reject')">
                    Reject
                  </button>
                </div>
                <div v-else class="text-xs text-hris-muted">
                  <p>Supervisor: {{ leaveRequest.supervisor_approver?.name ?? '--' }}</p>
                  <p>HR: {{ leaveRequest.hr_approver?.name ?? leaveRequest.approver?.name ?? '--' }}</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="meta" class="mt-4 flex items-center justify-between gap-3 text-sm">
      <p class="text-hris-muted">Page {{ meta.current_page }} of {{ meta.last_page }}</p>
      <div class="flex gap-2">
        <button type="button" class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50" :disabled="meta.current_page <= 1" @click="loadApprovals(meta.current_page - 1)">
          Previous
        </button>
        <button type="button" class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50" :disabled="meta.current_page >= meta.last_page" @click="loadApprovals(meta.current_page + 1)">
          Next
        </button>
      </div>
    </div>

    <ConfirmationModal
      :open="Boolean(decision)"
      :title="decisionTitle"
      :message="decisionMessage"
      :confirm-label="decision?.action === 'approve' ? 'Approve' : 'Reject'"
      :variant="decision?.action === 'approve' ? 'success' : 'danger'"
      :loading="deciding"
      @cancel="decision = null"
      @confirm="confirmDecision"
    />
  </section>
</template>
