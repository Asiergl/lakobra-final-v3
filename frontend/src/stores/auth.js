// src/stores/auth.js
import { defineStore } from 'pinia'

// URL base de la API — en producción apunta a /api (misma carpeta public)
// En desarrollo Vite hace proxy de /api → public/api (ver vite.config.js)
export const API_URL = '/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthenticated: false,
  }),
  actions: {
    async checkSession() {
      try {
        const res = await fetch(`${API_URL}/users/me`, { credentials: 'include' })
        if (res.ok) {
          const data = await res.json()
          this.user = data
          this.isAuthenticated = true
        } else {
          this.user = null
          this.isAuthenticated = false
        }
      } catch {
        this.user = null
        this.isAuthenticated = false
      }
    },

    async login(email, password) {
      const res = await fetch(`${API_URL}/auth/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ email, password })
      })
      const data = await res.json()
      if (!res.ok) throw new Error(data.error || 'Error al iniciar sesion')
      await this.checkSession()
    },

    async register(userData) {
      const res = await fetch(`${API_URL}/auth/register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(userData)
      })
      const data = await res.json()
      if (!res.ok) throw new Error(data.error || 'Error en el registro')
    },

    async logout() {
      await fetch(`${API_URL}/auth/logout`, { method: 'POST', credentials: 'include' })
      this.user = null
      this.isAuthenticated = false
    }
  }
})
