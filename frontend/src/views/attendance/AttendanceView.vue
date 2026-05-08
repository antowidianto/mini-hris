<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

import { clockIn, clockOut, getMyAttendance } from '@/services/attendance'

const filters = reactive({
  date_from: '',
  date_to: '',
  per_page: 10,
  page: 1,
})

const today = ref(null)
const attendances = ref([])
const meta = ref(null)
const loading = ref(false)
const actionLoading = ref(false)
const error = ref(null)

const canClockIn = computed(() => !today.value)
const canClockOut = computed(() => today.value?.time_in && !today.value?.time_out)

function statusClass(status) {
  return {
    present: 'bg-emerald-50 text-emerald-700',
    late: 'bg-amber-50 text-amber-700',
    absent: 'bg-red-50 text-red-700',
    leave: 'bg-blue-50 text-blue-700',
    sick: 'bg-purple-50 text-purple-700',
    permission: 'bg-sky-50 text-sky-700',
    alpha: 'bg-red-50 text-red-700',
  }[status] ?? 'bg-slate-100 text-slate-600'
}

function attendanceError(requestError, fallback) {
  const errors = requestError.response?.data?.errors
  const firstError = errors ? Object.values(errors)[0]?.[0] : null

  return firstError ?? requestError.response?.data?.message ?? fallback
}

async function loadAttendance(page = 1) {
  loading.value = true
  error.value = null
  filters.page = page

  try {
    const data = await getMyAttendance(filters)
    today.value = data.today?.id ? data.today : null
    attendances.value = data.attendances
    meta.value = data.meta
  } catch {
    error.value = 'Unable to load attendance history'
  } finally {
    loading.value = false
  }
}

async function handleClockIn() {
  actionLoading.value = true
  error.value = null

  try {
    today.value = await clockIn()
    await loadAttendance(meta.value?.current_page ?? 1)
  } catch (requestError) {
    error.value = attendanceError(requestError, 'Unable to clock in')
  } finally {
    actionLoading.value = false
  }
}

async function handleClockOut() {
  actionLoading.value = true
  error.value = null

  try {
    today.value = await clockOut()
    await loadAttendance(meta.value?.current_page ?? 1)
  } catch (requestError) {
    error.value = attendanceError(requestError, 'Unable to clock out')
  } finally {
    actionLoading.value = false
  }
}

function resetFilters() {
  filters.date_from = ''
  filters.date_to = ''
  filters.per_page = 10
  loadAttendance(1)
}

onMounted(() => loadAttendance())
</script>

<template>
  <section class="mx-auto max-w-6xl">
    <div class="border-b border-hris-border pb-5">
      <p class="text-xs font-semibold uppercase text-hris-accent">Time</p>
      <h2 class="mt-1 text-2xl font-semibold">My Attendance</h2>
      <p class="mt-1 text-sm text-hris-muted">Record your daily clock-in and clock-out.</p>
    </div>

    <div v-if="error" class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-[340px_1fr]">
      <aside class="rounded-md border border-hris-border bg-hris-panel p-5">
        <h3 class="font-semibold">Today</h3>

        <div class="mt-4 rounded-md bg-hris-surface p-4">
          <p class="text-sm text-hris-muted">Status</p>
          <p class="mt-1 text-lg font-semibold">
            {{ today?.status ?? 'Not clocked in' }}
          </p>
          <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div>
              <p class="text-hris-muted">Time In</p>
              <p class="font-medium">{{ today?.time_in ?? '--' }}</p>
            </div>
            <div>
              <p class="text-hris-muted">Time Out</p>
              <p class="font-medium">{{ today?.time_out ?? '--' }}</p>
            </div>
            <div>
              <p class="text-hris-muted">Shift</p>
              <p class="font-medium">{{ today?.shift_start ?? '--' }} - {{ today?.shift_end ?? '--' }}</p>
            </div>
            <div>
              <p class="text-hris-muted">Overtime</p>
              <p class="font-medium">{{ today?.overtime_minutes ?? 0 }} min</p>
            </div>
          </div>
        </div>

        <div class="mt-5 grid gap-3">
          <button
            type="button"
            class="rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="!canClockIn || actionLoading"
            @click="handleClockIn"
          >
            {{ actionLoading && canClockIn ? 'Processing...' : 'Clock In' }}
          </button>
          <button
            type="button"
            class="rounded-md border border-hris-border px-4 py-2 text-sm font-semibold hover:bg-hris-surface disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="!canClockOut || actionLoading"
            @click="handleClockOut"
          >
            {{ actionLoading && canClockOut ? 'Processing...' : 'Clock Out' }}
          </button>
        </div>
      </aside>

      <div>
        <form class="grid gap-3 rounded-md border border-hris-border bg-hris-panel p-4 sm:grid-cols-4" @submit.prevent="loadAttendance(1)">
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">From</span>
            <input
              v-model="filters.date_from"
              type="date"
              class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
            />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-hris-muted">To</span>
            <input
              v-model="filters.date_to"
              type="date"
              class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm"
            />
          </label>
          <button
            type="submit"
            class="self-end rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark"
          >
            Apply
          </button>
          <button
            type="button"
            class="self-end rounded-md border border-hris-border px-4 py-2 text-sm font-medium hover:bg-hris-surface"
            @click="resetFilters"
          >
            Reset
          </button>
        </form>

        <div class="mt-4 overflow-hidden rounded-md border border-hris-border bg-hris-panel">
          <div v-if="loading" class="p-6 text-sm text-hris-muted">Loading attendance...</div>
          <div v-else-if="attendances.length === 0" class="p-6 text-sm text-hris-muted">
            No attendance records found.
          </div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-hris-border text-sm">
              <thead class="bg-hris-surface text-left text-xs uppercase text-hris-muted">
                <tr>
                  <th class="px-4 py-3 font-semibold">Date</th>
                  <th class="px-4 py-3 font-semibold">Time In</th>
                  <th class="px-4 py-3 font-semibold">Time Out</th>
                  <th class="px-4 py-3 font-semibold">Shift</th>
                  <th class="px-4 py-3 font-semibold">Overtime</th>
                  <th class="px-4 py-3 font-semibold">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-hris-border">
                <tr v-for="attendance in attendances" :key="attendance.id">
                  <td class="px-4 py-3">{{ attendance.attendance_date }}</td>
                  <td class="px-4 py-3">{{ attendance.time_in ?? '--' }}</td>
                  <td class="px-4 py-3">{{ attendance.time_out ?? '--' }}</td>
                  <td class="px-4 py-3">{{ attendance.shift_start ?? '--' }} - {{ attendance.shift_end ?? '--' }}</td>
                  <td class="px-4 py-3">{{ attendance.overtime_minutes ?? 0 }} min</td>
                  <td class="px-4 py-3">
                    <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="statusClass(attendance.status)">
                      {{ attendance.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="meta" class="mt-4 flex items-center justify-between gap-3 text-sm">
          <p class="text-hris-muted">Page {{ meta.current_page }} of {{ meta.last_page }}</p>
          <div class="flex gap-2">
            <button
              type="button"
              class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="meta.current_page <= 1"
              @click="loadAttendance(meta.current_page - 1)"
            >
              Previous
            </button>
            <button
              type="button"
              class="rounded-md border border-hris-border px-3 py-2 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="meta.current_page >= meta.last_page"
              @click="loadAttendance(meta.current_page + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
