<script setup>
import { onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'

import {
  getNotifications,
  markAllNotificationsAsRead,
  markNotificationAsRead,
} from '@/services/notifications'

const filters = reactive({
  type: '',
  severity: '',
  unread: false,
  per_page: 10,
  page: 1,
})

const notifications = ref([])
const meta = ref(null)
const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const success = ref(null)

function severityClass(severity) {
  return {
    danger: 'bg-red-50 text-red-700',
    warning: 'bg-amber-50 text-amber-700',
    info: 'bg-blue-50 text-blue-700',
  }[severity] ?? 'bg-hris-surface text-hris-muted'
}

function typeLabel(type) {
  return {
    contract_expiry: 'Contract',
    probation_ending: 'Probation',
    pending_approval: 'Approval',
    payroll_alert: 'Payroll',
  }[type] ?? type
}

function formatDate(value) {
  if (!value) {
    return '-'
  }

  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

async function loadNotifications(page = 1) {
  loading.value = true
  error.value = null
  filters.page = page

  try {
    const data = await getNotifications({
      ...filters,
      unread: filters.unread ? 1 : undefined,
    })
    notifications.value = data.notifications
    meta.value = data.meta
  } catch {
    error.value = 'Unable to load notifications'
  } finally {
    loading.value = false
  }
}

async function markRead(notification) {
  if (notification.is_read) {
    return
  }

  saving.value = true
  error.value = null

  try {
    await markNotificationAsRead(notification.id)
    await loadNotifications(filters.page)
  } catch {
    error.value = 'Unable to mark notification as read'
  } finally {
    saving.value = false
  }
}

async function markAllRead() {
  saving.value = true
  error.value = null
  success.value = null

  try {
    await markAllNotificationsAsRead()
    success.value = 'All notifications marked as read.'
    await loadNotifications(filters.page)
  } catch {
    error.value = 'Unable to mark notifications as read'
  } finally {
    saving.value = false
  }
}

onMounted(loadNotifications)
</script>

<template>
  <section class="mx-auto max-w-6xl">
    <div class="flex flex-col justify-between gap-4 border-b border-hris-border pb-5 sm:flex-row">
      <div>
        <p class="text-xs font-semibold uppercase text-hris-accent">Workspace</p>
        <h2 class="mt-1 text-2xl font-semibold">Notifications</h2>
        <p class="mt-1 text-sm text-hris-muted">Operational reminders and approval alerts.</p>
      </div>
      <button
        type="button"
        class="self-start rounded-md border border-hris-border px-4 py-2 text-sm font-semibold hover:bg-hris-panel disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="saving"
        @click="markAllRead"
      >
        Mark All Read
      </button>
    </div>

    <form class="mt-5 flex flex-wrap gap-3" @submit.prevent="loadNotifications(1)">
      <select v-model="filters.type" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All types</option>
        <option value="contract_expiry">Contract</option>
        <option value="probation_ending">Probation</option>
        <option value="pending_approval">Approval</option>
        <option value="payroll_alert">Payroll</option>
      </select>
      <select v-model="filters.severity" class="rounded-md border border-hris-border px-3 py-2 text-sm">
        <option value="">All severities</option>
        <option value="info">Info</option>
        <option value="warning">Warning</option>
        <option value="danger">Danger</option>
      </select>
      <label class="flex items-center gap-2 rounded-md border border-hris-border px-3 py-2 text-sm">
        <input v-model="filters.unread" type="checkbox" class="size-4" />
        Unread only
      </label>
      <button
        type="submit"
        class="rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark"
      >
        Apply
      </button>
    </form>

    <div v-if="error" class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>
    <div v-if="success" class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ success }}
    </div>

    <div class="mt-5 overflow-hidden rounded-md border border-hris-border bg-hris-panel">
      <div v-if="loading" class="p-6 text-sm text-hris-muted">Loading notifications...</div>
      <div v-else-if="notifications.length === 0" class="p-6 text-sm text-hris-muted">No notifications found.</div>

      <div v-else class="divide-y divide-hris-border">
        <article
          v-for="notification in notifications"
          :key="notification.id"
          class="grid gap-3 px-5 py-4 sm:grid-cols-[1fr_auto]"
          :class="notification.is_read ? 'bg-hris-panel' : 'bg-blue-50/35'"
        >
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="severityClass(notification.severity)">
                {{ typeLabel(notification.type) }}
              </span>
              <span v-if="!notification.is_read" class="rounded-md bg-hris-primary px-2 py-1 text-xs font-semibold text-white">
                New
              </span>
            </div>
            <h3 class="mt-2 font-semibold">{{ notification.title }}</h3>
            <p class="mt-1 text-sm text-hris-muted">{{ notification.message }}</p>
            <p class="mt-2 text-xs text-hris-muted">{{ formatDate(notification.triggered_at ?? notification.created_at) }}</p>
          </div>

          <div class="flex items-start gap-2 sm:justify-end">
            <RouterLink
              v-if="notification.action_url"
              :to="notification.action_url"
              class="rounded-md border border-hris-border px-3 py-2 text-sm font-medium hover:bg-hris-surface"
              @click="markRead(notification)"
            >
              Open
            </RouterLink>
            <button
              type="button"
              class="rounded-md border border-hris-border px-3 py-2 text-sm font-medium hover:bg-hris-surface disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="notification.is_read || saving"
              @click="markRead(notification)"
            >
              Read
            </button>
          </div>
        </article>
      </div>

      <div v-if="meta" class="flex items-center justify-between gap-3 border-t border-hris-border px-4 py-3 text-sm">
        <p class="text-hris-muted">Page {{ meta.current_page }} of {{ meta.last_page }}</p>
        <div class="flex gap-2">
          <button
            type="button"
            class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="meta.current_page <= 1"
            @click="loadNotifications(meta.current_page - 1)"
          >
            Previous
          </button>
          <button
            type="button"
            class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="meta.current_page >= meta.last_page"
            @click="loadNotifications(meta.current_page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
