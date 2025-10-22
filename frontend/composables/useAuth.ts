import type { User } from '~/types/models'

interface AuthState {
  user: User | null
  pending: boolean
  authenticated: boolean
}

export const useAuth = () => {
  const config = useRuntimeConfig()

  const state = reactive<AuthState>({
    user: null,
    pending: true,
    authenticated: false
  })

  const fetchUser = async (): Promise<User | null> => {
    state.pending = true
    try {
      const user = await $fetch<User>(`${config.public.apiBase}/user`, {
        credentials: 'include'
      })

      state.user = user || null
      state.authenticated = !!user
      return state.user
    } catch (error: any) {
      console.warn('Auth fetch error:', error)
      state.user = null
      state.authenticated = false
      return null
    } finally {
      state.pending = false
    }
  }

  onMounted(async () => {
    try {
      await $fetch('https://localhost:8000/sanctum/csrf-cookie', {
        credentials: 'include'
      })
      await fetchUser()
    } catch (err) {
      console.warn('Skipping fetchUser, Sanctum not ready yet')
      state.pending = false
    }
  })

  const login = async (credentials: { email: string; password: string }) => {
    try {
      await $fetch('https://localhost:8000/sanctum/csrf-cookie', {
        credentials: 'include'
      })

      await $fetch(`${config.public.apiBase}/login`, {
        method: 'POST',
        body: credentials,
        credentials: 'include'
      })

      await fetchUser()
      return true
    } catch (error) {
      console.error('Login error:', error)
      return false
    }
  }

  const logout = async () => {
    try {
      await $fetch(`${config.public.apiBase}/logout`, {
        method: 'POST',
        credentials: 'include'
      })
      state.user = null
      state.authenticated = false
      await navigateTo('/')
      return true
    } catch (error) {
      console.error('Logout error:', error)
      return false
    }
  }

  const hasRole = (role: User['role']) => state.user?.role === role
  const hasAnyRole = (roles: User['role'][]) => roles.includes(state.user?.role as User['role'])

  return {
    ...toRefs(state),
    fetchUser,
    login,
    logout,
    hasRole,
    hasAnyRole
  }
}
