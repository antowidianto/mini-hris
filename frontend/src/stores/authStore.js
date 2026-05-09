import { defineStore } from 'pinia'

import { canAccessRole } from '@/config/roles'
import api from '@/services/api'

const TOKEN_KEY = 'mini_hris_auth_token'
const USER_KEY = 'mini_hris_auth_user'

function storage() {
  return window.localStorage
}

function readStoredUser() {
  const value = storage().getItem(USER_KEY)

  if (!value) {
    return null
  }

  try {
    return JSON.parse(value)
  } catch {
    storage().removeItem(USER_KEY)
    return null
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: storage().getItem(TOKEN_KEY),
    user: readStoredUser(),
    loading: false,
    error: null,
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.token),
    role: (state) => state.user?.role ?? null,
    canAccess: (state) => (roles = []) => canAccessRole(state.user?.role, roles),
  },
  actions: {
    setSession({ token, user }) {
      this.token = token
      this.user = user
      this.error = null

      storage().setItem(TOKEN_KEY, token)
      storage().setItem(USER_KEY, JSON.stringify(user))
    },
    clearSession() {
      this.token = null
      this.user = null
      this.error = null

      storage().removeItem(TOKEN_KEY)
      storage().removeItem(USER_KEY)
    },
    async register(payload) {
      if (this.loading) {
        return null
      }

      this.loading = true
      this.error = null

      try {
        const response = await api.post('/auth/register', payload)
        const data = response.data.data

        this.setSession({
          token: data.token,
          user: data.user,
        })

        return data.user
      } catch (error) {
        this.error = error.response?.data?.message ?? 'Unable to create workspace'
        throw error
      } finally {
        this.loading = false
      }
    },
    async login(credentials) {
      if (this.loading) {
        return null
      }

      this.loading = true
      this.error = null

      try {
        const response = await api.post('/auth/login', credentials)
        const payload = response.data.data

        this.setSession({
          token: payload.token,
          user: payload.user,
        })

        return payload.user
      } catch (error) {
        this.error = error.response?.data?.message ?? 'Unable to sign in'
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchUser() {
      if (!this.token) {
        return null
      }

      const response = await api.get('/auth/me')
      const user = response.data.data.user

      this.user = user
      storage().setItem(USER_KEY, JSON.stringify(user))

      return user
    },
    async logout() {
      this.loading = true

      try {
        if (this.token) {
          await api.post('/auth/logout')
        }
      } finally {
        this.clearSession()
        this.loading = false
      }
    },
  },
})
