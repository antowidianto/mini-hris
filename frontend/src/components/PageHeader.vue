<script setup>
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
})
</script>

<template>
  <div class="flex flex-col justify-between gap-3 rounded-md border border-hris-border bg-hris-panel px-4 py-3 sm:flex-row sm:items-center">
    <div class="min-w-0">
      <p v-if="eyebrow" class="text-xs font-semibold uppercase text-hris-muted">{{ eyebrow }}</p>
      <h2 class="text-lg font-semibold leading-tight">{{ title }}</h2>
      <p v-if="description" class="mt-0.5 text-sm text-hris-muted">{{ description }}</p>
    </div>

    <slot name="actions" />
  </div>
</template>
