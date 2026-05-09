<script setup>
import { computed, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const auth = useAuthStore()
const selectedPlan = ref('starter')
const form = reactive({
  company_name: '',
  company_code: '',
  billing_email: '',
  admin_name: '',
  password: '',
  password_confirmation: '',
  plan: selectedPlan.value,
})
const validationErrors = ref({})

const plans = [
  {
    code: 'starter',
    name: 'Starter',
    price: '$29',
    limit: '25 employees',
    description: 'For founders and HR teams moving away from spreadsheets.',
    features: ['Core employee records', 'Attendance and leave workflows', 'Payroll-ready reports'],
  },
  {
    code: 'growth',
    name: 'Growth',
    price: '$79',
    limit: '100 employees',
    description: 'For growing teams that need structured HR operations.',
    features: ['Multi-branch organization setup', 'Approval flows', 'Contracts and documents'],
  },
  {
    code: 'scale',
    name: 'Scale',
    price: '$199',
    limit: '500 employees',
    description: 'For mature teams preparing for compliance-heavy operations.',
    features: ['Audit logs', 'Operational reports', 'Advanced configuration'],
  },
]

const selectedPlanDetails = computed(() => plans.find((plan) => plan.code === selectedPlan.value) ?? plans[0])

function choosePlan(plan) {
  selectedPlan.value = plan.code
  form.plan = plan.code
}

async function submitSignup() {
  if (auth.loading) {
    return
  }

  validationErrors.value = {}
  form.plan = selectedPlan.value

  try {
    await auth.register(form)
    router.push({ name: 'dashboard' })
  } catch (error) {
    validationErrors.value = error.response?.data?.errors ?? {}
  }
}
</script>

<template>
  <main class="min-h-screen bg-slate-950 text-white">
    <section class="mx-auto grid max-w-7xl gap-10 px-5 py-10 lg:grid-cols-[1.08fr_0.92fr] lg:px-8 lg:py-14">
      <div>
        <nav class="flex items-center justify-between gap-4">
          <RouterLink to="/welcome" class="flex items-center gap-3">
            <span class="grid size-10 place-items-center rounded-xl bg-hris-primary font-bold">HR</span>
            <span>
              <span class="block text-lg font-semibold">Mini HRIS SaaS</span>
              <span class="block text-xs text-slate-400">Multi-tenant HR operations</span>
            </span>
          </RouterLink>

          <RouterLink
            to="/login"
            class="rounded-full border border-white/15 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10"
          >
            Sign in
          </RouterLink>
        </nav>

        <div class="pt-16 lg:pt-24">
          <p class="inline-flex rounded-full border border-cyan-300/30 bg-cyan-300/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-cyan-200">
            14-day trial • no card required
          </p>
          <h1 class="mt-6 max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
            Launch a dedicated HR workspace for every customer company.
          </h1>
          <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-300">
            Mini HRIS now supports SaaS onboarding with isolated tenant workspaces, subscription status checks,
            starter organization data, and plan-based employee limits.
          </p>

          <div class="mt-8 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
              <p class="text-2xl font-bold">Multi-tenant</p>
              <p class="mt-1 text-sm text-slate-400">Company-scoped users, HR data, settings, and audit logs.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
              <p class="text-2xl font-bold">Self-serve</p>
              <p class="mt-1 text-sm text-slate-400">Customers create their own trial workspace in minutes.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
              <p class="text-2xl font-bold">Subscription gated</p>
              <p class="mt-1 text-sm text-slate-400">Inactive tenants are blocked from protected APIs.</p>
            </div>
          </div>
        </div>

        <div class="mt-10 grid gap-4 lg:grid-cols-3">
          <button
            v-for="plan in plans"
            :key="plan.code"
            type="button"
            class="rounded-2xl border p-5 text-left hover:-translate-y-0.5 hover:bg-white/10"
            :class="selectedPlan === plan.code ? 'border-cyan-300 bg-cyan-300/10 shadow-2xl shadow-cyan-950/40' : 'border-white/10 bg-white/5'"
            @click="choosePlan(plan)"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-lg font-semibold">{{ plan.name }}</p>
                <p class="mt-1 text-sm text-slate-400">{{ plan.limit }}</p>
              </div>
              <p class="text-xl font-bold">{{ plan.price }}<span class="text-xs font-medium text-slate-400">/mo</span></p>
            </div>
            <p class="mt-4 text-sm leading-6 text-slate-300">{{ plan.description }}</p>
            <ul class="mt-4 space-y-2 text-sm text-slate-300">
              <li v-for="feature in plan.features" :key="feature" class="flex gap-2">
                <span class="text-cyan-300">✓</span>
                <span>{{ feature }}</span>
              </li>
            </ul>
          </button>
        </div>
      </div>

      <form class="self-start rounded-3xl border border-white/10 bg-white p-6 text-hris-ink shadow-2xl lg:sticky lg:top-8" @submit.prevent="submitSignup">
        <p class="text-sm font-semibold uppercase tracking-wide text-hris-primary">Create workspace</p>
        <h2 class="mt-2 text-2xl font-bold">Start {{ selectedPlanDetails.name }} trial</h2>
        <p class="mt-2 text-sm text-hris-muted">
          Your first admin account and company workspace are created together.
        </p>

        <div class="mt-6 grid gap-4">
          <label class="block">
            <span class="text-sm font-medium">Company name</span>
            <input v-model="form.company_name" required class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" placeholder="Acme People Ops" />
            <span v-if="validationErrors.company_name" class="mt-1 block text-xs text-red-600">{{ validationErrors.company_name[0] }}</span>
          </label>

          <label class="block">
            <span class="text-sm font-medium">Workspace code</span>
            <input v-model="form.company_code" required class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm uppercase" placeholder="ACME" />
            <span v-if="validationErrors.company_code" class="mt-1 block text-xs text-red-600">{{ validationErrors.company_code[0] }}</span>
          </label>

          <label class="block">
            <span class="text-sm font-medium">Admin name</span>
            <input v-model="form.admin_name" required class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" placeholder="Jane Doe" />
            <span v-if="validationErrors.admin_name" class="mt-1 block text-xs text-red-600">{{ validationErrors.admin_name[0] }}</span>
          </label>

          <label class="block">
            <span class="text-sm font-medium">Billing email</span>
            <input v-model="form.billing_email" type="email" required class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" placeholder="admin@company.com" />
            <span v-if="validationErrors.billing_email" class="mt-1 block text-xs text-red-600">{{ validationErrors.billing_email[0] }}</span>
          </label>

          <div class="grid gap-4 sm:grid-cols-2">
            <label class="block">
              <span class="text-sm font-medium">Password</span>
              <input v-model="form.password" type="password" required class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" placeholder="••••••••" />
              <span v-if="validationErrors.password" class="mt-1 block text-xs text-red-600">{{ validationErrors.password[0] }}</span>
            </label>

            <label class="block">
              <span class="text-sm font-medium">Confirm password</span>
              <input v-model="form.password_confirmation" type="password" required class="mt-1 w-full rounded-md border border-hris-border px-3 py-2 text-sm" placeholder="••••••••" />
            </label>
          </div>
        </div>

        <p v-if="auth.error" class="mt-4 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{{ auth.error }}</p>

        <button
          type="submit"
          class="mt-6 w-full rounded-md bg-hris-primary px-4 py-3 text-sm font-semibold text-white hover:bg-hris-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="auth.loading"
        >
          {{ auth.loading ? 'Creating workspace...' : 'Create SaaS workspace' }}
        </button>

        <p class="mt-4 text-center text-xs text-hris-muted">
          Already have a workspace?
          <RouterLink to="/login" class="font-semibold text-hris-primary hover:underline">Sign in</RouterLink>
        </p>
      </form>
    </section>
  </main>
</template>
