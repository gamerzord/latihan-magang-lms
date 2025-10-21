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

  const fetchUser = async (): Promise<User | null> => {
    state.pending = true
    try {
      const { data, error } = await useFetch<User>(`${config.public.apiBase}/user`, {
        credentials: 'include'
      })
      
      if (error.value) {
        console.error('Auth fetch error:', error.value)
        state.user = null
        state.authenticated = false
        return null
      }

      state.user = data.value || null
      state.authenticated = !! state.user
      return state.user
    } catch (error) {
      console.error('Auth error:', error)
      state.user = null
      state.authenticated = false
      return null
    } finally {
      state.pending = false
    }
  }

  const login = async (credentials: { email: string; password: string }): Promise<boolean> => {
    try {
      await $fetch('https://localhost:8000/sanctum/csrf-cookie', {
    })

      await $fetch(`${config.public.apiBase}/login`, {
        method: 'POST',
        body: credentials,
        credentials: 'include'
      })

      await fetchUser()
      return true
    } catch (error: any) {
      console.error('Login error:', error)
      return false
    }
  }

  const logout = async (): Promise<boolean> => {
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

  const hasRole = (role: User['role']): boolean => state.user?.role === role
  const hasAnyRole = (roles: User['role'][]): boolean => roles.includes(state.user?.role as User['role'])

  return {
    ...toRefs(state),
    fetchUser,
    login,
    logout,
    hasRole,
    hasAnyRole
  }
}
