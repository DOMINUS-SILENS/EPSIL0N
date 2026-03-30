// /home/badji/god-sfa-crm-frontend/src/core/state/stores/authStore.ts
import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface User {
  id: string
  name: string
  email: string
  company_id: string
  company_name: string
}

interface AuthState {
  user: User | null
  permissions: string[]
  isAuthenticated: boolean
  setUser: (user: User | null) => void
  setPermissions: (permissions: string[]) => void
  clearAuth: () => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      permissions: [],
      isAuthenticated: false,
      setUser: (user) => set({ user, isAuthenticated: !!user }),
      setPermissions: (permissions) => set({ permissions }),
      clearAuth: () => set({ user: null, permissions: [], isAuthenticated: false }),
    }),
    {
      name: 'auth-storage',
    }
  )
)

// UI Store
interface UIState {
  sidebarCollapsed: boolean
  sidebarWidth: number
  commandPaletteOpen: boolean
  theme: 'light' | 'dark' | 'system'
  toggleSidebar: () => void
  setSidebarWidth: (width: number) => void
  setCommandPaletteOpen: (open: boolean) => void
  setTheme: (theme: 'light' | 'dark' | 'system') => void
}

export const useUIStore = create<UIState>()((set) => ({
  sidebarCollapsed: false,
  sidebarWidth: 240,
  commandPaletteOpen: false,
  theme: 'system',
  toggleSidebar: () => set((state) => ({ sidebarCollapsed: !state.sidebarCollapsed })),
  setSidebarWidth: (width) => set({ sidebarWidth: width }),
  setCommandPaletteOpen: (open) => set({ commandPaletteOpen: open }),
  setTheme: (theme) => set({ theme }),
}))
