<script setup>
defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'lg',
    validator: (value) => ['md', 'lg', 'xl', '2xl', '4xl'].includes(value),
  },
})

const emit = defineEmits(['close'])

const sizeClass = {
  md: 'max-w-md',
  lg: 'max-w-2xl',
  xl: 'max-w-3xl',
  '2xl': 'max-w-5xl',
  '4xl': 'max-w-7xl',
}
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-6" role="dialog" aria-modal="true">
      <div class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" @click="emit('close')"></div>

      <div class="relative mx-auto my-6 w-full rounded-2xl border border-hris-border bg-hris-panel shadow-2xl" :class="sizeClass[size]">
        <div class="flex items-start justify-between gap-4 border-b border-hris-border px-5 py-4">
          <div>
            <h3 class="text-lg font-semibold text-hris-ink">{{ title }}</h3>
            <p v-if="description" class="mt-1 text-sm text-hris-muted">{{ description }}</p>
          </div>
          <button
            type="button"
            class="rounded-full p-2 text-hris-muted hover:bg-hris-surface hover:text-hris-ink"
            aria-label="Close modal"
            @click="emit('close')"
          >
            ✕
          </button>
        </div>

        <div class="max-h-[75vh] overflow-y-auto px-5 py-5">
          <slot />
        </div>

        <div v-if="$slots.footer" class="flex flex-wrap justify-end gap-2 border-t border-hris-border px-5 py-4">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </Teleport>
</template>
