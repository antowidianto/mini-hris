<script setup>
defineProps({
  meta: {
    type: Object,
    default: null,
  },
})

defineEmits(['change'])
</script>

<template>
  <div v-if="meta" class="mt-3 flex flex-col gap-2 text-sm sm:flex-row sm:items-center sm:justify-between">
    <p class="text-hris-muted">
      Page {{ meta.current_page }} of {{ meta.last_page }}
      <span v-if="meta.total !== undefined"> / {{ meta.total }} record(s)</span>
    </p>
    <div class="flex gap-2">
      <button
        type="button"
        class="rounded-md border border-hris-border bg-hris-panel px-3 py-1.5 text-sm font-medium hover:bg-hris-surface disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="meta.current_page <= 1"
        @click="$emit('change', meta.current_page - 1)"
      >
        Previous
      </button>
      <button
        type="button"
        class="rounded-md border border-hris-border bg-hris-panel px-3 py-1.5 text-sm font-medium hover:bg-hris-surface disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="meta.current_page >= meta.last_page"
        @click="$emit('change', meta.current_page + 1)"
      >
        Next
      </button>
    </div>
  </div>
</template>
