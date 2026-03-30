import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '@/core/api/client'
import { useAuthStore } from '@/core/state/stores/authStore'
import { toast } from 'sonner'

interface LoginCredentials {
  email: string
  password: string
  remember?: boolean
}

interface User {
  id: string
  name: string
  email: string
  permissions: string[]
  roles: string[]
  company_id: string
  company_name: string
}

export const useAuth = () => {
  const queryClient = useQueryClient()
  const { setUser, setPermissions, clearAuth } = useAuthStore()

  const { data: user, isLoading } = useQuery({
    queryKey: ['auth', 'user'],
    queryFn: async () => {
      const { data } = await api.get<User>('/user')
      setUser(data)
      setPermissions(data.permissions)
      return data
    },
    retry: false,
    staleTime: 5 * 60 * 1000,
    enabled: !window.location.pathname.startsWith('/login')
  })

  const loginMutation = useMutation({
    mutationFn: async (credentials: LoginCredentials) => {
      const { data } = await api.post('/auth/login', credentials)
      return data
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['auth', 'user'] })
      toast.success('Login successful')
      window.location.href = '/dashboard'
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Login failed')
    },
  })

  const logoutMutation = useMutation({
    mutationFn: async () => {
      await api.post('/auth/logout')
    },
    onSuccess: () => {
      clearAuth()
      queryClient.clear()
      toast.success('Logout successful')
      window.location.href = '/login'
    },
  })

  return {
    user,
    isLoading,
    login: loginMutation.mutate,
    loginAsync: loginMutation.mutateAsync,
    logout: logoutMutation.mutate,
    isAuthenticated: !!user,
  }
}

export const usePermissions = () => {
  const { permissions } = useAuthStore()
  
  return {
    can: (permission: string) => permissions?.includes(permission) ?? false,
    canAny: (perms: string[]) => perms.some(p => permissions?.includes(p)) ?? false,
    canAll: (perms: string[]) => perms.every(p => permissions?.includes(p)) ?? false,
  }
}
