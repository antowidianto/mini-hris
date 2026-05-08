<script setup>
defineProps({
  label: {
    type: String,
    default: 'Loading...',
  },
})
</script>

<template>
  <div class="flex min-h-20 items-center justify-center gap-3 p-4 text-sm text-hris-muted">
    <span class="size-4 animate-spin rounded-full border-2 border-hris-border border-t-hris-primary"></span>
    <span>{{ label }}</span>
  </div>
</template>
