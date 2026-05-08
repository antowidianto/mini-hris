<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

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
})

const tileClass = computed(() => {
  return {
    blue: 'border-blue-100 bg-blue-50/70 text-slate-700',
    green: 'border-emerald-100 bg-emerald-50/75 text-slate-700',
    amber: 'border-amber-100 bg-amber-50/75 text-slate-700',
    red: 'border-red-100 bg-red-50/75 text-slate-700',
    violet: 'border-violet-100 bg-violet-50/75 text-slate-700',
    slate: 'border-slate-200 bg-slate-50/80 text-slate-700',
  }[props.tone] ?? 'border-blue-100 bg-blue-50/70 text-slate-700'
})
</script>

<template>
  <component
    :is="to ? RouterLink : 'div'"
    :to="to || undefined"
    class="metric-tile block rounded-md border px-4 py-3"
    :class="tileClass"
  >
    <p class="text-xs font-medium text-slate-500">{{ label }}</p>
    <p class="mt-1 text-2xl font-semibold leading-none text-slate-700">{{ value }}</p>
    <p v-if="detail" class="mt-2 truncate text-xs text-slate-500">{{ detail }}</p>
  </component>
</template>
