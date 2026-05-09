<script setup>
defineProps({
  name: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <svg
    class="ui-icon"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
    :aria-hidden="label ? undefined : true"
    :aria-label="label || undefined"
    :role="label ? 'img' : undefined"
  >
    <template v-if="name === 'dashboard'">
      <path d="M4 13.5h6.5V4H4z" />
      <path d="M13.5 20H20v-9.5h-6.5z" />
      <path d="M13.5 7.5H20V4h-6.5z" />
      <path d="M4 20h6.5v-3.5H4z" />
    </template>
    <template v-else-if="name === 'people'">
      <path d="M16.5 19.5v-1.8c0-1.8-1.5-3.2-3.3-3.2H6.8c-1.8 0-3.3 1.4-3.3 3.2v1.8" />
      <path d="M10 11.2a3.4 3.4 0 1 0 0-6.8 3.4 3.4 0 0 0 0 6.8" />
      <path d="M20.5 19.5v-1.7a3.2 3.2 0 0 0-2.4-3.1" />
      <path d="M16 4.7a3.4 3.4 0 0 1 0 6.4" />
    </template>
    <template v-else-if="name === 'briefcase'">
      <path d="M9 7V5.8C9 4.8 9.8 4 10.8 4h2.4c1 0 1.8.8 1.8 1.8V7" />
      <path d="M4.5 7h15A1.5 1.5 0 0 1 21 8.5v9A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5v-9A1.5 1.5 0 0 1 4.5 7" />
      <path d="M3 12.5a24 24 0 0 0 18 0" />
      <path d="M12 11.5v2" />
    </template>
    <template v-else-if="name === 'document'">
      <path d="M7 3.5h6l4 4V19a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 5 19V5A1.5 1.5 0 0 1 6.5 3.5" />
      <path d="M13 3.5V8h4" />
      <path d="M8 12h8" />
      <path d="M8 16h5" />
    </template>
    <template v-else-if="name === 'clock'">
      <circle cx="12" cy="12" r="8.5" />
      <path d="M12 7.5V12l3 1.8" />
    </template>
    <template v-else-if="name === 'calendar'">
      <path d="M7 3.5v3" />
      <path d="M17 3.5v3" />
      <path d="M4.5 8.5h15" />
      <path d="M6 5.5h12A1.5 1.5 0 0 1 19.5 7v11A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18V7A1.5 1.5 0 0 1 6 5.5" />
    </template>
    <template v-else-if="name === 'check-circle'">
      <circle cx="12" cy="12" r="8.5" />
      <path d="m8.5 12.2 2.2 2.2 4.8-5" />
    </template>
    <template v-else-if="name === 'wallet'">
      <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5H18a1.5 1.5 0 0 1 1.5 1.5V9" />
      <path d="M4 7.5v9A2.5 2.5 0 0 0 6.5 19h12A1.5 1.5 0 0 0 20 17.5v-7A1.5 1.5 0 0 0 18.5 9H6.5A2.5 2.5 0 0 1 4 7.5" />
      <path d="M16.5 14h.2" />
    </template>
    <template v-else-if="name === 'chart'">
      <path d="M4 19.5h16" />
      <path d="M7 16v-5" />
      <path d="M12 16V6" />
      <path d="M17 16V9" />
    </template>
    <template v-else-if="name === 'settings'">
      <circle cx="12" cy="12" r="3" />
      <path d="M19 12a7 7 0 0 0-.1-1l2-1.5-2-3.4-2.4 1a8 8 0 0 0-1.8-1L14.4 3h-4l-.3 3.1a8 8 0 0 0-1.8 1l-2.4-1-2 3.4 2 1.5a7 7 0 0 0 0 2l-2 1.5 2 3.4 2.4-1a8 8 0 0 0 1.8 1l.3 3.1h4l.3-3.1a8 8 0 0 0 1.8-1l2.4 1 2-3.4-2-1.5c.1-.3.1-.7.1-1" />
    </template>
    <template v-else-if="name === 'bell'">
      <path d="M18 10.5a6 6 0 1 0-12 0c0 7-2 7-2 7h16s-2 0-2-7" />
      <path d="M9.8 20a2.5 2.5 0 0 0 4.4 0" />
    </template>
    <template v-else-if="name === 'logout'">
      <path d="M10 5H6.5A1.5 1.5 0 0 0 5 6.5v11A1.5 1.5 0 0 0 6.5 19H10" />
      <path d="M15 8l4 4-4 4" />
      <path d="M19 12H9" />
    </template>
    <template v-else-if="name === 'spark'">
      <path d="m12 3 1.6 5.2L19 10l-5.4 1.8L12 17l-1.6-5.2L5 10l5.4-1.8z" />
      <path d="m18.5 15 .6 2 1.9.7-1.9.7-.6 2-.7-2-1.8-.7 1.8-.7z" />
    </template>
    <template v-else>
      <circle cx="12" cy="12" r="8.5" />
      <path d="M12 8v4l2.5 2" />
    </template>
  </svg>
</template>
