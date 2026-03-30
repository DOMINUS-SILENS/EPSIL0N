import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export interface Company {
  id: string
  name: string
  logo?: string
  currency: string
  tax_id?: string
}

interface CompanyState {
  activeCompanyId: string | null
  activeCompany: Company | null
  availableCompanies: Company[]
  setCompany: (id: string) => void
  setAvailableCompanies: (companies: Company[]) => void
  addCompany: (company: Company) => void
}

/**
 * Multi-Tenant / Multi-Company Context Store
 * Tracks the currently active operating company for the ERP user session
 */
export const useCompanyStore = create<CompanyState>()(
  persist(
    (set, get) => ({
      activeCompanyId: null,
      activeCompany: null,
      availableCompanies: [
        // Mock default until backend loads
        { id: '1', name: 'Epsilon HQ', currency: 'USD' },
        { id: '2', name: 'Epsilon EU Division', currency: 'EUR' }
      ],
      setCompany: (id: string) => {
        const { availableCompanies } = get()
        const company = availableCompanies.find(c => c.id === id) || null
        set({ activeCompanyId: id, activeCompany: company })
        
        // When changing companies, we typically invalidate all queries
        // This should trigger a top-level event or queryClient reset
        window.dispatchEvent(new CustomEvent('company-changed', { detail: { id } }))
      },
      setAvailableCompanies: (companies) => {
        set({ availableCompanies: companies })
        const { activeCompanyId } = get()
        if (!activeCompanyId && companies.length > 0) {
          set({ activeCompanyId: companies[0].id, activeCompany: companies[0] })
        }
      },
      addCompany: (company) => set((state) => ({ 
        availableCompanies: [...state.availableCompanies, company]
      }))
    }),
    {
      name: 'erp-company-context',
    }
  )
)

export const useCompany = () => {
  return useCompanyStore()
}
