import type { User } from '~/types/models'

interface AuthState {
  user: User | null
  pending: boolean
  authenticated: boolean
  adminUser: User | null
  adminAuthenticated: boolean
}

export const useAuth = () => {
  const config = useRuntimeConfig()

  const state = reactive<AuthState>({
    user: null,
    pending: true,
    authenticated: false,
    adminUser: null,
    adminAuthenticated: false
  })

  const fetchUser = async (): Promise<User | null> => {
    state.pending = true
    try {
      const user = await $fetch<User>(`${config.public.apiBase}/user`, {
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

  const setAdminAuth = (userData: User) => {
    state.adminUser = userData
    state.adminAuthenticated = true
  }

  const adminLogout = async () => {
    try {
      await $fetch(`${config.public.apiBase}/logout`, {
        method: 'POST',
      })
      state.adminUser = null
      state.adminAuthenticated = false
      await navigateTo('/admin/login')
      return true
    } catch (error) {
      console.error('Admin logout error:', error)
      return false
    }
  }

  const fetchAdminUser = async (): Promise<User | null> => {
    try {
      const user = await $fetch<User>(`${config.public.apiBase}/user`, {
      })

      // Only set as admin if user has admin role
      if (user && user.role === 'admin') {
        state.adminUser = user
        state.adminAuthenticated = true
        return user
      } else {
        state.adminUser = null
        state.adminAuthenticated = false
        return null
      }
    } catch (error: any) {
      console.warn('Admin auth fetch error:', error)
      state.adminUser = null
      state.adminAuthenticated = false
      return null
    }
  }

  onMounted(async () => {
    try {
      await $fetch('https://localhost:8000/sanctum/csrf-cookie', {
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
      })

      await $fetch(`${config.public.apiBase}/login`, {
        method: 'POST',
        body: credentials,
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
      })
      state.user = null
      state.authenticated = false
      state.adminUser = null
      state.adminAuthenticated = false
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
    hasAnyRole,
    setAdminAuth,
    adminLogout,
    fetchAdminUser
  }
}