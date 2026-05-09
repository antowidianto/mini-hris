<script setup>
import { reactive } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import BaseIcon from '@/components/BaseIcon.vue'
import { useAuthStore } from '@/stores/authStore'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const form = reactive({
  email: '',
  password: '',
})

async function submitLogin() {
  if (auth.loading) {
    return
  }

  try {
    await auth.login(form)
  } catch {
    return
  }

  router.push(route.query.redirect?.toString() || { name: 'dashboard' })
}
</script>

<template>
  <main class="login-shell grid min-h-screen place-items-center px-5 py-10">
    <form
      class="w-full max-w-md rounded-3xl border border-white/75 bg-hris-panel/95 p-8 shadow-2xl shadow-slate-300/50 backdrop-blur"
      @submit.prevent="submitLogin"
    >
      <div class="flex items-center gap-3">
        <span class="app-brand-mark"><BaseIcon name="spark" /></span>
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">Mini HRIS</h1>
          <p class="mt-1 text-sm text-hris-muted">Sign in to your SaaS workspace</p>
        </div>
      </div>

      <div class="mt-6 space-y-4">
        <label class="block">
          <span class="text-sm font-medium">Email</span>
          <input
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            class="mt-1 w-full rounded-xl border border-hris-border bg-hris-surface px-3 py-2.5 text-sm"
            placeholder="name@example.com"
          />
        </label>

        <label class="block">
          <span class="text-sm font-medium">Password</span>
          <input
            v-model="form.password"
            type="password"
            required
            autocomplete="current-password"
            class="mt-1 w-full rounded-xl border border-hris-border bg-hris-surface px-3 py-2.5 text-sm"
            placeholder="Password"
          />
        </label>

        <p v-if="auth.error" class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
          {{ auth.error }}
        </p>

        <button
          type="submit"
          class="w-full rounded-xl bg-hris-primary px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-200 hover:bg-hris-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="auth.loading"
        >
          {{ auth.loading ? 'Signing in...' : 'Login' }}
        </button>

        <p class="text-center text-xs text-hris-muted">
          Need a tenant workspace?
          <RouterLink to="/welcome" class="font-semibold text-hris-primary hover:underline">Start a trial</RouterLink>
        </p>
      </div>
    </form>
  </main>
</template>
