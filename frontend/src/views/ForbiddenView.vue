<template>
  <section class="mx-auto max-w-3xl">
    <div class="rounded-md border border-hris-border bg-hris-panel p-6">
      <p class="text-sm font-semibold uppercase text-hris-accent">403</p>
      <h2 class="mt-2 text-2xl font-semibold">Access restricted</h2>
      <p class="mt-2 text-sm text-hris-muted">
        Your role does not have permission to open this workspace area.
      </p>

      <RouterLink
        to="/"
        class="mt-5 inline-block rounded-md bg-hris-primary px-4 py-2 text-sm font-semibold text-white hover:bg-hris-primary-dark"
      >
        Back to dashboard
      </RouterLink>
    </div>
  </section>
</template>
