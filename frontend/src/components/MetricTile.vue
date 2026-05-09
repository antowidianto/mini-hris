<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

import BaseIcon from '@/components/BaseIcon.vue'

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    default: '--',
  },
  detail: {
    type: String,
    default: '',
  },
  tone: {
    type: String,
    default: 'blue',
  },
  to: {
    type: String,
    default: '',
  },
  icon: {
    type: String,
    default: '',
  },
})

const tileClass = computed(() => {
  return {
    blue: 'border-blue-100 bg-blue-50/75 text-slate-700',
    green: 'border-emerald-100 bg-emerald-50/80 text-slate-700',
    amber: 'border-amber-100 bg-amber-50/80 text-slate-700',
    red: 'border-red-100 bg-red-50/80 text-slate-700',
    violet: 'border-violet-100 bg-violet-50/80 text-slate-700',
    slate: 'border-slate-200 bg-slate-50/90 text-slate-700',
  }[props.tone] ?? 'border-blue-100 bg-blue-50/75 text-slate-700'
})
</script>

<template>
  <component
    :is="to ? RouterLink : 'div'"
    :to="to || undefined"
    class="metric-tile block rounded-2xl border px-4 py-4"
    :class="tileClass"
  >
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ label }}</p>
        <p class="mt-2 text-2xl font-semibold leading-none text-slate-800">{{ value }}</p>
      </div>
      <span v-if="icon" class="metric-icon grid size-10 shrink-0 place-items-center rounded-xl bg-white/80 text-hris-primary shadow-sm">
        <BaseIcon :name="icon" />
      </span>
    </div>
    <p v-if="detail" class="mt-3 truncate text-xs text-slate-500">{{ detail }}</p>
  </component>
</template>
