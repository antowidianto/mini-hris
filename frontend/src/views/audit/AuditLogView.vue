<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

import EmptyState from '@/components/EmptyState.vue'
import LoadingState from '@/components/LoadingState.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationControls from '@/components/PaginationControls.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { getAuditLogs } from '@/services/auditLogs'

const filters = reactive({
  search: '',
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
const filterOptions = ref({
  modules: [],
  actions: [],
  users: [],
})
const loading = ref(false)
const error = ref(null)

const activeFilterCount = computed(() =>
  ['search', 'module', 'action', 'user_id', 'date_from', 'date_to'].filter((key) => Boolean(filters[key])).length,
)

async function loadAuditLogs(page = 1) {
  loading.value = true
  error.value = null
  filters.page = page

  try {
    const data = await getAuditLogs(filters)
    auditLogs.value = data.audit_logs
    meta.value = data.meta
    filterOptions.value = data.filters ?? filterOptions.value
  } catch {
    error.value = 'Unable to load audit logs'
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.search = ''
  filters.module = ''
  filters.action = ''
  filters.user_id = ''
  filters.date_from = ''
  filters.date_to = ''
  filters.per_page = 10
  loadAuditLogs(1)
}

function userLabel(user) {
  return `${user.name} (${user.email})`
}

onMounted(() => loadAuditLogs())
</script>

<template>
  <section class="mx-auto max-w-7xl">
    <PageHeader eyebrow="System" title="Audit Logs" description="Understand who did what and when across HR operations.">
      <template #actions>
        <div class="rounded-md border border-hris-border bg-hris-surface px-3 py-1.5 text-sm text-hris-muted">
          {{ activeFilterCount }} active filter{{ activeFilterCount === 1 ? '' : 's' }}
        </div>
      </template>
    </PageHeader>

    <div v-if="error" class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>

    <form class="ui-filter-bar mt-4 grid rounded-md border border-hris-border bg-hris-panel lg:grid-cols-12" @submit.prevent="loadAuditLogs(1)">
      <input
        v-model="filters.search"
        type="search"
        class="rounded-md border border-hris-border px-3 py-2 text-sm lg:col-span-3"
        placeholder="Search user or description"
      />

      <select v-model="filters.user_id" class="rounded-md border border-hris-border px-3 py-2 text-sm lg:col-span-3">
        <option value="">All users</option>
        <option v-for="user in filterOptions.users" :key="user.id" :value="user.id">
          {{ userLabel(user) }}
        </option>
      </select>

      <select v-model="filters.module" class="rounded-md border border-hris-border px-3 py-2 text-sm lg:col-span-2">
        <option value="">All modules</option>
        <option v-for="module in filterOptions.modules" :key="module.value" :value="module.value">
          {{ module.label }}
        </option>
      </select>

      <select v-model="filters.action" class="rounded-md border border-hris-border px-3 py-2 text-sm lg:col-span-2">
        <option value="">All actions</option>
        <option v-for="action in filterOptions.actions" :key="action.value" :value="action.value">
          {{ action.label }}
        </option>
      </select>

      <select v-model.number="filters.per_page" class="rounded-md border border-hris-border px-3 py-2 text-sm lg:col-span-2" aria-label="Rows per page">
        <option :value="10">10 rows</option>
        <option :value="20">20 rows</option>
        <option :value="50">50 rows</option>
      </select>

      <input v-model="filters.date_from" type="date" class="rounded-md border border-hris-border px-3 py-2 text-sm lg:col-span-2" aria-label="Date from" />
      <input v-model="filters.date_to" type="date" class="rounded-md border border-hris-border px-3 py-2 text-sm lg:col-span-2" aria-label="Date to" />

      <div class="flex gap-2 lg:col-span-8">
        <button type="submit" class="rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark">
          Apply
        </button>
        <button type="button" class="rounded-md border border-hris-border px-4 py-2 text-sm font-medium hover:bg-hris-surface" @click="resetFilters">
          Reset
        </button>
      </div>
    </form>

    <div class="ui-table-card mt-4 overflow-hidden rounded-md border border-hris-border bg-hris-panel">
      <LoadingState v-if="loading" label="Loading audit logs..." />
      <EmptyState v-else-if="auditLogs.length === 0" title="No audit logs found" message="Adjust filters or wait for new system activity." />
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-hris-border text-sm">
          <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
            <tr>
              <th class="font-semibold">Time</th>
              <th class="font-semibold">Event</th>
              <th class="font-semibold">User</th>
              <th class="font-semibold">Module</th>
              <th class="font-semibold">Action</th>
              <th class="font-semibold">Client</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-hris-border">
            <tr v-for="auditLog in auditLogs" :key="auditLog.id">
              <td class="text-xs text-hris-muted">{{ auditLog.created_at_display }}</td>
              <td class="min-w-80">
                <p class="font-semibold text-hris-ink">{{ auditLog.summary }}</p>
                <p class="mt-1 max-w-xl text-xs text-hris-muted">{{ auditLog.description }}</p>
              </td>
              <td>
                <p class="font-medium">{{ auditLog.user?.name ?? 'System' }}</p>
                <p class="text-xs text-hris-muted">{{ auditLog.user?.email ?? '--' }}</p>
              </td>
              <td>
                <span class="rounded-md bg-hris-surface px-2 py-1 text-xs font-semibold">{{ auditLog.module_label }}</span>
              </td>
              <td>
                <StatusBadge :status="auditLog.action" :label="auditLog.action_label" />
              </td>
              <td>
                <p class="text-xs text-hris-muted">{{ auditLog.ip_address ?? '--' }}</p>
                <p class="max-w-52 truncate text-xs text-hris-muted">{{ auditLog.user_agent ?? '--' }}</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <PaginationControls :meta="meta" @change="loadAuditLogs" />
  </section>
</template>
