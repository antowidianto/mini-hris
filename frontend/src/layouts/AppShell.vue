<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'

import { ADMIN_HR_ROLES } from '@/config/roles'
import { navigationGroups } from '@/config/navigation'
import {
  getNotifications,
  getUnreadNotificationCount,
  markAllNotificationsAsRead,
  markNotificationAsRead,
} from '@/services/notifications'
import { useAuthStore } from '@/stores/authStore'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const notificationMenuOpen = ref(false)
const notificationLoading = ref(false)
const notificationError = ref(null)
const unreadCount = ref(0)
const recentNotifications = ref([])

const loggingOut = computed(() => auth.loading)
const pageTitle = computed(() => route.meta.label ?? 'Mini HRIS')
const visibleNavigationGroups = computed(() =>
  navigationGroups
    .map((group) => ({
      ...group,
      items: group.items.filter((item) => canSeeNavigationItem(item)),
    }))
    .filter((group) => group.items.length > 0),
)
const userInitials = computed(() => {
  if (!auth.user?.name) {
    return 'U'
  }

  return auth.user.name
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
})

function canSeeNavigationItem(item) {
  if (!auth.canAccess(item.access)) {
    return false
  }

  if (item.to === '/leaves/approvals') {
    return ADMIN_HR_ROLES.includes(auth.role) || Boolean(auth.user?.employee?.has_direct_reports)
  }

  return true
}

function severityClass(severity) {
  return {
    danger: 'bg-red-50 text-red-700',
    warning: 'bg-amber-50 text-amber-700',
    info: 'bg-blue-50 text-blue-700',
  }[severity] ?? 'bg-hris-surface text-hris-muted'
}

async function loadNotificationSummary() {
  notificationLoading.value = true
  notificationError.value = null

  try {
    const data = await getNotifications({ per_page: 5 })
    unreadCount.value = await getUnreadNotificationCount()
    recentNotifications.value = data.notifications
  } catch {
    notificationError.value = 'Unable to load notifications'
  } finally {
    notificationLoading.value = false
  }
}

async function toggleNotificationMenu() {
  notificationMenuOpen.value = !notificationMenuOpen.value

  if (notificationMenuOpen.value) {
    await loadNotificationSummary()
  }
}

async function markRead(notification) {
  if (!notification.is_read) {
    await markNotificationAsRead(notification.id)
  }

  await loadNotificationSummary()
}

async function markAllRead() {
  await markAllNotificationsAsRead()
  await loadNotificationSummary()
}

async function handleLogout() {
  if (loggingOut.value) {
    return
  }

  await auth.logout()
  router.push({ name: 'login' })
}

onMounted(loadNotificationSummary)
</script>

<template>
  <div class="app-shell min-h-screen text-hris-ink">
    <aside
      class="app-sidebar fixed inset-y-0 left-0 hidden w-64 border-r border-slate-800 bg-slate-900 px-4 py-5 text-white lg:block"
    >
      <RouterLink to="/" class="flex items-center gap-3 rounded-md px-2 py-1.5">
        <span class="app-brand-mark">HR</span>
        <span>
          <span class="block text-base font-semibold">Mini HRIS</span>
          <span class="block text-xs text-slate-400">Indonesia SME Edition</span>
        </span>
      </RouterLink>

      <nav class="mt-7 space-y-5">
        <section v-for="group in visibleNavigationGroups" :key="group.label">
          <p class="px-3 pb-2 text-xs font-semibold uppercase text-slate-400">{{ group.label }}</p>

          <RouterLink
            v-for="item in group.items"
            :key="item.to"
            :to="item.to"
            class="app-nav-link mb-1 block rounded-md px-3 py-2 pl-5 text-sm font-medium text-slate-300 hover:bg-white/10 hover:text-white"
            active-class="bg-white/10 text-white shadow-sm hover:bg-white/10 hover:text-white"
          >
            {{ item.label }}
          </RouterLink>
        </section>
      </nav>
    </aside>

    <div class="lg:pl-64">
      <header class="sticky top-0 z-30 px-4 py-4 sm:px-5 lg:px-6">
        <div class="rounded-xl border border-hris-border bg-hris-panel/95 px-4 py-3 shadow-sm backdrop-blur">
          <div class="flex items-center justify-between gap-4">
            <div class="min-w-0">
              <p class="text-xs font-semibold uppercase tracking-wide text-hris-muted">Admin Workspace</p>
              <h1 class="truncate text-xl font-semibold">{{ pageTitle }}</h1>
            </div>

            <div class="flex items-center gap-3">
              <div class="relative">
                <button
                  type="button"
                  class="relative rounded-md border border-hris-border bg-hris-panel px-3 py-2 text-sm font-medium text-hris-ink hover:bg-hris-surface"
                  @click="toggleNotificationMenu"
                >
                  <span class="hidden sm:inline">Notifications</span>
                  <span class="sm:hidden">Alerts</span>
                  <span
                    v-if="unreadCount > 0"
                    class="absolute -right-2 -top-2 min-w-5 rounded-full bg-red-600 px-1.5 py-0.5 text-center text-xs font-semibold text-white"
                  >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                  </span>
                </button>

              <div
                v-if="notificationMenuOpen"
                class="absolute right-0 mt-2 w-80 overflow-hidden rounded-md border border-hris-border bg-hris-panel shadow-lg"
              >
                <div class="flex items-center justify-between border-b border-hris-border px-4 py-3">
                  <p class="text-sm font-semibold">Notifications</p>
                  <button
                    type="button"
                    class="text-xs font-medium text-hris-primary hover:underline disabled:opacity-50"
                    :disabled="notificationLoading || unreadCount === 0"
                    @click="markAllRead"
                  >
                    Mark all read
                  </button>
                </div>

                <div v-if="notificationLoading" class="p-4 text-sm text-hris-muted">
                  Loading notifications...
                </div>
                <div v-else-if="notificationError" class="p-4 text-sm text-red-600">
                  {{ notificationError }}
                </div>
                <div v-else-if="recentNotifications.length === 0" class="p-4 text-sm text-hris-muted">
                  No notifications.
                </div>
                <div v-else class="max-h-96 divide-y divide-hris-border overflow-y-auto">
                  <RouterLink
                    v-for="notification in recentNotifications"
                    :key="notification.id"
                    :to="notification.action_url || '/notifications'"
                    class="block px-4 py-3 text-sm hover:bg-hris-surface"
                    :class="notification.is_read ? '' : 'bg-blue-50/45'"
                    @click="markRead(notification)"
                  >
                    <div class="flex items-center justify-between gap-3">
                      <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="severityClass(notification.severity)">
                        {{ notification.type.replace('_', ' ') }}
                      </span>
                      <span v-if="!notification.is_read" class="size-2 rounded-full bg-hris-primary"></span>
                    </div>
                    <p class="mt-2 font-semibold">{{ notification.title }}</p>
                    <p class="mt-1 line-clamp-2 text-xs text-hris-muted">{{ notification.message }}</p>
                  </RouterLink>
                </div>

                <RouterLink
                  to="/notifications"
                  class="block border-t border-hris-border px-4 py-3 text-center text-sm font-semibold text-hris-primary hover:bg-hris-surface"
                  @click="notificationMenuOpen = false"
                >
                  View all
                </RouterLink>
              </div>
            </div>

              <div class="hidden text-right sm:block">
                <p class="text-sm font-medium">{{ auth.user?.name }}</p>
                <p class="text-xs uppercase text-hris-muted">{{ auth.user?.role }}</p>
              </div>

              <div
                class="grid size-9 place-items-center rounded-md bg-hris-primary text-sm font-semibold text-white ring-2 ring-blue-100"
              >
                {{ userInitials }}
              </div>

              <button
                type="button"
                class="rounded-md border border-hris-border px-3 py-2 text-sm font-medium text-hris-ink hover:bg-hris-surface disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="loggingOut"
                @click="handleLogout"
              >
                {{ loggingOut ? 'Signing out...' : 'Logout' }}
              </button>
            </div>
          </div>

          <nav class="mt-3 flex gap-2 overflow-x-auto lg:hidden">
            <div v-for="group in visibleNavigationGroups" :key="group.label" class="flex shrink-0 gap-2">
              <span class="self-center px-1 text-xs font-semibold uppercase text-hris-muted">{{ group.label }}</span>
              <RouterLink
                v-for="item in group.items"
                :key="item.to"
                :to="item.to"
                class="shrink-0 rounded-md border border-hris-border bg-hris-panel px-3 py-2 text-sm font-medium text-hris-muted"
                active-class="border-hris-primary bg-hris-primary text-white"
              >
                {{ item.label }}
              </RouterLink>
            </div>
          </nav>
        </div>
      </header>

      <main class="px-4 pb-6 sm:px-5 lg:px-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>
