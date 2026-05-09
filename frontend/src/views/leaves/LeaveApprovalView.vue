<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'

import BaseModal from '@/components/BaseModal.vue'
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
const bulkDecision = ref(null)
const detailRequest = ref(null)
const selectedIds = ref([])
const notes = reactive({})
const error = ref(null)
const success = ref(null)
const canApproveAsHr = computed(() => ADMIN_HR_ROLES.includes(auth.role))
const canApproveAsSupervisor = computed(() => Boolean(auth.user?.employee?.has_direct_reports))
const selectableRequests = computed(() => leaveRequests.value.filter((leaveRequest) => leaveRequest.status === 'pending'))
const selectedRequests = computed(() => leaveRequests.value.filter((leaveRequest) => selectedIds.value.includes(leaveRequest.id)))
const allVisibleSelected = computed(() => (
  selectableRequests.value.length > 0
  && selectableRequests.value.every((leaveRequest) => selectedIds.value.includes(leaveRequest.id))
))

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
  if (leaveRequest.current_approval_step) {
    return `Waiting ${leaveRequest.current_approval_step.role}`
  }

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

function toggleSelectAll(event) {
  selectedIds.value = event.target.checked ? selectableRequests.value.map((leaveRequest) => leaveRequest.id) : []
}

function requestBulkDecision(action) {
  if (selectedRequests.value.length === 0) {
    return
  }

  bulkDecision.value = { requests: [...selectedRequests.value], action }
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


async function confirmBulkDecision() {
  if (!bulkDecision.value) {
    return
  }

  deciding.value = true
  error.value = null
  success.value = null

  try {
    const action = bulkDecision.value.action
    const requests = bulkDecision.value.requests

    for (const leaveRequest of requests) {
      const payload = {
        approval_notes: notes[leaveRequest.id] ?? '',
      }

      if (action === 'approve') {
        if (stage.value === 'supervisor') {
          await supervisorApproveLeaveRequest(leaveRequest.id, payload)
        } else {
          await approveLeaveRequest(leaveRequest.id, payload)
        }
      } else if (stage.value === 'supervisor') {
        await supervisorRejectLeaveRequest(leaveRequest.id, payload)
      } else {
        await rejectLeaveRequest(leaveRequest.id, payload)
      }

      notes[leaveRequest.id] = ''
    }

    selectedIds.value = []
    bulkDecision.value = null
    success.value = `${requests.length} leave request(s) ${action === 'approve' ? 'approved' : 'rejected'}.`
    await loadApprovals(meta.value?.current_page ?? 1)
  } catch (decisionError) {
    error.value = requestError(decisionError, 'Unable to update selected leave requests')
  } finally {
    deciding.value = false
  }
}

watch(leaveRequests, () => {
  const visibleIds = new Set(leaveRequests.value.map((leaveRequest) => leaveRequest.id))
  selectedIds.value = selectedIds.value.filter((id) => visibleIds.has(id))
})

onMounted(async () => {
  await Promise.all([loadLookups(), loadApprovals()])
})
</script>

<template>
  <section class="mx-auto max-w-screen-2xl">
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

    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-md border border-hris-border bg-hris-panel px-4 py-3 text-sm">
      <p class="text-hris-muted">
        {{ selectedRequests.length }} selected from {{ selectableRequests.length }} pending request(s) on this page.
      </p>
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-md bg-emerald-600 px-3 py-2 font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="selectedRequests.length === 0 || deciding"
          @click="requestBulkDecision('approve')"
        >
          Approve Selected
        </button>
        <button
          type="button"
          class="rounded-md bg-red-600 px-3 py-2 font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="selectedRequests.length === 0 || deciding"
          @click="requestBulkDecision('reject')"
        >
          Reject Selected
        </button>
      </div>
    </div>

    <div class="mt-5 overflow-hidden rounded-md border border-hris-border bg-hris-panel">
      <div v-if="loading" class="p-6 text-sm text-hris-muted">Loading leave approvals...</div>
      <div v-else-if="leaveRequests.length === 0" class="p-6 text-sm text-hris-muted">No leave requests found.</div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-hris-border text-sm">
          <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
            <tr>
              <th class="px-4 py-3 font-semibold">
                <input
                  type="checkbox"
                  class="rounded border-hris-border"
                  :checked="allVisibleSelected"
                  :disabled="selectableRequests.length === 0"
                  aria-label="Select all visible pending leave requests"
                  @change="toggleSelectAll"
                />
              </th>
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
                <input
                  v-model="selectedIds"
                  type="checkbox"
                  class="rounded border-hris-border"
                  :value="leaveRequest.id"
                  :disabled="leaveRequest.status !== 'pending'"
                  :aria-label="`Select leave request for ${leaveRequest.employee?.full_name}`"
                />
              </td>
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
                <template v-if="leaveRequest.approval_steps?.length">
                  <p v-for="step in leaveRequest.approval_steps" :key="step.id" class="text-xs text-hris-muted">
                    Step {{ step.step_order }}: {{ step.role }} - {{ step.status }}
                  </p>
                </template>
                <template v-else>
                  <p class="text-xs text-hris-muted">Supervisor: {{ leaveRequest.supervisor_status }}</p>
                  <p class="text-xs text-hris-muted">HR: {{ leaveRequest.hr_status }}</p>
                </template>
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
                <div v-if="leaveRequest.status === 'pending'" class="flex flex-wrap gap-2">
                  <button type="button" class="text-hris-primary hover:underline" @click="detailRequest = leaveRequest">
                    Detail
                  </button>
                  <button type="button" class="text-emerald-700 hover:underline" @click="requestDecision(leaveRequest, 'approve')">
                    Approve
                  </button>
                  <button type="button" class="text-red-600 hover:underline" @click="requestDecision(leaveRequest, 'reject')">
                    Reject
                  </button>
                </div>
                <div v-else class="text-xs text-hris-muted">
                  <button type="button" class="mb-1 text-hris-primary hover:underline" @click="detailRequest = leaveRequest">Detail</button>
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


    <BaseModal
      :open="Boolean(detailRequest)"
      title="Leave request details"
      :description="detailRequest?.employee?.full_name"
      size="xl"
      @close="detailRequest = null"
    >
      <div v-if="detailRequest" class="grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-hris-border p-4">
          <p class="text-xs font-semibold uppercase text-hris-muted">Employee</p>
          <p class="mt-2 font-semibold">{{ detailRequest.employee?.full_name }}</p>
          <p class="text-sm text-hris-muted">{{ detailRequest.employee?.employee_id }} · {{ detailRequest.employee?.department?.name ?? '-' }}</p>
        </div>
        <div class="rounded-xl border border-hris-border p-4">
          <p class="text-xs font-semibold uppercase text-hris-muted">Leave</p>
          <p class="mt-2 font-semibold">{{ detailRequest.leave_type?.name }}</p>
          <p class="text-sm text-hris-muted">{{ detailRequest.start_date }} to {{ detailRequest.end_date }} · {{ detailRequest.total_days }} day(s)</p>
        </div>
        <div class="rounded-xl border border-hris-border p-4 md:col-span-2">
          <p class="text-xs font-semibold uppercase text-hris-muted">Reason</p>
          <p class="mt-2 text-sm">{{ detailRequest.reason || '--' }}</p>
        </div>
        <div class="rounded-xl border border-hris-border p-4 md:col-span-2">
          <p class="text-xs font-semibold uppercase text-hris-muted">Approval flow</p>
          <div class="mt-3 space-y-2 text-sm">
            <p class="font-medium">{{ workflowLabel(detailRequest) }}</p>
            <template v-if="detailRequest.approval_steps?.length">
              <p v-for="step in detailRequest.approval_steps" :key="step.id" class="text-hris-muted">
                Step {{ step.step_order }}: {{ step.role }} · {{ step.status }} · {{ step.approver?.name ?? 'Unassigned' }}
              </p>
            </template>
            <template v-else>
              <p class="text-hris-muted">Supervisor: {{ detailRequest.supervisor_status }} · {{ detailRequest.supervisor_approver?.name ?? '--' }}</p>
              <p class="text-hris-muted">HR: {{ detailRequest.hr_status }} · {{ detailRequest.hr_approver?.name ?? detailRequest.approver?.name ?? '--' }}</p>
            </template>
          </div>
        </div>
      </div>
    </BaseModal>

    <ConfirmationModal
      :open="Boolean(bulkDecision)"
      :title="bulkDecision?.action === 'approve' ? 'Approve selected leave requests' : 'Reject selected leave requests'"
      :message="`${bulkDecision?.action === 'approve' ? 'Approve' : 'Reject'} ${bulkDecision?.requests?.length ?? 0} selected leave request(s)?`"
      :confirm-label="bulkDecision?.action === 'approve' ? 'Approve Selected' : 'Reject Selected'"
      :variant="bulkDecision?.action === 'approve' ? 'success' : 'danger'"
      :loading="deciding"
      @cancel="bulkDecision = null"
      @confirm="confirmBulkDecision"
    />

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
