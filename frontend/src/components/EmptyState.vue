<script setup>
defineProps({
  title: {
    type: String,
    default: 'No records found',
  },
  message: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <div class="min-h-20 p-4 text-center">
    <p class="text-sm font-semibold text-hris-ink">{{ title }}</p>
    <p v-if="message" class="mt-1 text-sm text-hris-muted">{{ message }}</p>
  </div>
</template>
