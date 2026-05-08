<script setup>
import { onMounted, reactive, ref } from 'vue'

import { getAuditLogs } from '@/services/auditLogs'

const filters = reactive({
  module: '',
  action: '',
  user_id: '',
  date_from: '',
  date_to: '',
  per_page: 10,
  page: 1,
})

const auditLogs = ref([])
const meta = ref(null)
const loading = ref(false)
const error = ref(null)

async function loadAuditLogs(page = 1) {
  loading.value = true
  error.value = null
  filters.page = page

  try {
    const data = await getAuditLogs(filters)
    auditLogs.value = data.audit_logs
    meta.value = data.meta
  } catch {
    error.value = 'Unable to load audit logs'
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.module = ''
  filters.action = ''
  filters.user_id = ''
  filters.date_from = ''
  filters.date_to = ''
  filters.per_page = 10
  loadAuditLogs(1)
}

onMounted(() => loadAuditLogs())
</script>

<template>
  <section class="mx-auto max-w-7xl">
    <div class="border-b border-hris-border pb-5">
      <p class="text-xs font-semibold uppercase text-hris-accent">System</p>
      <h2 class="mt-1 text-2xl font-semibold">Audit Logs</h2>
      <p class="mt-1 text-sm text-hris-muted">Review important user and system actions.</p>
    </div>

    <div v-if="error" class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>

    <form class="mt-5 grid gap-3 rounded-md border border-hris-border bg-hris-panel p-4 lg:grid-cols-7" @submit.prevent="loadAuditLogs(1)">
      <select v-model="filters.module" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All modules</option>
        <option value="auth">Auth</option>
        <option value="employee">Employee</option>
        <option value="leave">Leave</option>
        <option value="payroll">Payroll</option>
      </select>
      <select v-model="filters.action" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All actions</option>
        <option value="login">Login</option>
        <option value="logout">Logout</option>
        <option value="created">Created</option>
        <option value="updated">Updated</option>
        <option value="deactivated">Deactivated</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
        <option value="generated">Generated</option>
      </select>
      <input
        v-model="filters.user_id"
        type="number"
        min="1"
        class="rounded-md border border-hris-border px-3 py-2 text-sm"
        placeholder="User ID"
      />
      <input v-model="filters.date_from" type="date" class="rounded-md border border-hris-border px-3 py-2 text-sm" aria-label="Date from" />
      <input v-model="filters.date_to" type="date" class="rounded-md border border-hris-border px-3 py-2 text-sm" aria-label="Date to" />
      <button type="submit" class="rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark">
        Apply
      </button>
      <button type="button" class="rounded-md border border-hris-border px-4 py-2 text-sm font-medium hover:bg-hris-surface" @click="resetFilters">
        Reset
      </button>
    </form>

    <div class="mt-5 overflow-hidden rounded-md border border-hris-border bg-hris-panel">
      <div v-if="loading" class="p-6 text-sm text-hris-muted">Loading audit logs...</div>
      <div v-else-if="auditLogs.length === 0" class="p-6 text-sm text-hris-muted">No audit logs found.</div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-hris-border text-sm">
          <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
            <tr>
              <th class="px-4 py-3 font-semibold">Time</th>
              <th class="px-4 py-3 font-semibold">User</th>
              <th class="px-4 py-3 font-semibold">Module</th>
              <th class="px-4 py-3 font-semibold">Action</th>
              <th class="px-4 py-3 font-semibold">Description</th>
              <th class="px-4 py-3 font-semibold">Client</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-hris-border">
            <tr v-for="auditLog in auditLogs" :key="auditLog.id">
              <td class="whitespace-nowrap px-4 py-3">{{ auditLog.created_at }}</td>
              <td class="px-4 py-3">
                <p class="font-medium">{{ auditLog.user?.name ?? 'System' }}</p>
                <p class="text-xs text-hris-muted">{{ auditLog.user?.email ?? '--' }}</p>
              </td>
              <td class="px-4 py-3">
                <span class="rounded-md bg-hris-surface px-2 py-1 text-xs font-semibold">{{ auditLog.module }}</span>
              </td>
              <td class="px-4 py-3">{{ auditLog.action }}</td>
              <td class="max-w-md px-4 py-3">{{ auditLog.description }}</td>
              <td class="px-4 py-3">
                <p class="text-xs text-hris-muted">{{ auditLog.ip_address ?? '--' }}</p>
                <p class="max-w-56 truncate text-xs text-hris-muted">{{ auditLog.user_agent ?? '--' }}</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="meta" class="mt-4 flex items-center justify-between gap-3 text-sm">
      <p class="text-hris-muted">Page {{ meta.current_page }} of {{ meta.last_page }}</p>
      <div class="flex gap-2">
        <button type="button" class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50" :disabled="meta.current_page <= 1" @click="loadAuditLogs(meta.current_page - 1)">
          Previous
        </button>
        <button type="button" class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50" :disabled="meta.current_page >= meta.last_page" @click="loadAuditLogs(meta.current_page + 1)">
          Next
        </button>
      </div>
    </div>
  </section>
</template>
