<script setup>
import BaseIcon from '@/components/BaseIcon.vue'

defineProps({
  eyebrow: {
    type: String,
    default: '',
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  icon: {
    type: String,
    default: 'spark',
  },
})
</script>

<template>
  <div class="page-header flex flex-col justify-between gap-4 rounded-2xl border border-white/70 bg-hris-panel px-5 py-4 shadow-sm sm:flex-row sm:items-center">
    <div class="flex min-w-0 items-start gap-3">
      <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-blue-50 text-hris-primary ring-1 ring-blue-100">
        <BaseIcon :name="icon" />
      </span>
      <div class="min-w-0">
        <p v-if="eyebrow" class="text-xs font-semibold uppercase tracking-[0.18em] text-hris-muted">{{ eyebrow }}</p>
        <h2 class="text-xl font-semibold leading-tight tracking-tight text-slate-900">{{ title }}</h2>
        <p v-if="description" class="mt-1 text-sm text-hris-muted">{{ description }}</p>
      </div>
    </div>

    <slot name="actions" />
  </div>
</template>
