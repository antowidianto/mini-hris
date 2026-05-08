<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
})

const badgeClass = computed(() => {
  return {
    active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    approved: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    present: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    success: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    pending: 'bg-amber-50 text-amber-700 ring-amber-200',
    late: 'bg-amber-50 text-amber-700 ring-amber-200',
    warning: 'bg-amber-50 text-amber-700 ring-amber-200',
    inactive: 'bg-slate-100 text-slate-600 ring-slate-200',
    rejected: 'bg-red-50 text-red-700 ring-red-200',
    absent: 'bg-red-50 text-red-700 ring-red-200',
    alpha: 'bg-red-50 text-red-700 ring-red-200',
    danger: 'bg-red-50 text-red-700 ring-red-200',
    leave: 'bg-blue-50 text-blue-700 ring-blue-200',
    info: 'bg-blue-50 text-blue-700 ring-blue-200',
    sick: 'bg-violet-50 text-violet-700 ring-violet-200',
    permission: 'bg-sky-50 text-sky-700 ring-sky-200',
  }[props.status] ?? 'bg-slate-100 text-slate-600 ring-slate-200'
})

const displayLabel = computed(() => props.label || props.status || '--')
</script>

<template>
  <span class="inline-flex max-w-full items-center rounded-md px-2 py-1 text-xs font-semibold capitalize ring-1 ring-inset" :class="badgeClass">
    {{ displayLabel }}
  </span>
</template>
