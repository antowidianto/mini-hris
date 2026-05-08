<script setup>
import { computed } from 'vue'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    required: true,
  },
  message: {
    type: String,
    required: true,
  },
  confirmLabel: {
    type: String,
    default: 'Confirm',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  variant: {
    type: String,
    default: 'danger',
    validator: (value) => ['danger', 'primary', 'success'].includes(value),
  },
})

defineEmits(['cancel', 'confirm'])

const confirmButtonClass = computed(() => {
  return {
    danger: 'bg-red-600 text-white hover:bg-red-700',
    primary: 'bg-hris-primary text-white hover:bg-hris-primary-dark',
    success: 'bg-emerald-600 text-white hover:bg-emerald-700',
  }[props.variant]
})
const titleId = computed(() => `confirmation-modal-${props.title.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`)
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50 grid place-items-center bg-slate-950/45 px-4 backdrop-blur-sm">
      <section
        class="w-full max-w-md rounded-md border border-hris-border bg-hris-panel p-5 shadow-xl"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
      >
        <h2 :id="titleId" class="text-lg font-semibold">{{ title }}</h2>
        <p class="mt-2 text-sm text-hris-muted">{{ message }}</p>

        <div class="mt-5 flex justify-end gap-3">
          <button
            type="button"
            class="rounded-md border border-hris-border px-4 py-2 text-sm font-medium hover:bg-hris-surface"
            :disabled="loading"
            @click="$emit('cancel')"
          >
            Cancel
          </button>
          <button
            type="button"
            class="rounded-md px-4 py-2 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-60"
            :class="confirmButtonClass"
            :disabled="loading"
            @click="$emit('confirm')"
          >
            {{ loading ? 'Processing...' : confirmLabel }}
          </button>
        </div>
      </section>
    </div>
  </Teleport>
</template>
