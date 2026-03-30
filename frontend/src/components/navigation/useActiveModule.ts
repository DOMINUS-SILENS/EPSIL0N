import { useLocation } from '@tanstack/react-router'
import { modulesRegistry, AppModule } from './moduleRegistry'
import { useMemo } from 'react'

export function useActiveModule(): AppModule {
  const location = useLocation()
  const pathname = location.pathname

  const activeModule = useMemo(() => {
    // Exact match for dashboard
    if (pathname === '/') {
      return modulesRegistry.find((m) => m.key === 'dashboard')!
    }

    // Extract the first segment of the path to determine the module
    const pathSegments = pathname.split('/').filter(Boolean)
    const firstSegment = pathSegments[0]

    // Map path prefixes to module keys
    const prefixToModule: Record<string, string> = {
      'core': 'core',
      'crm': 'crm',
      'sales': 'crm',
      'erp': 'inventory',
      'inventory': 'inventory',
      'purchasing': 'inventory',
      'commercial': 'commercial',
      'trade-marketing': 'commercial',
      'sfa': 'sfa',
      'delivery': 'delivery',
      'hr': 'hr',
      'commission': 'hr',
      'accounting': 'accounting',
      'payments': 'accounting',
      'reports': 'reports',
    }


    const moduleKey = prefixToModule[firstSegment]

    if (moduleKey) {
      const mod = modulesRegistry.find((m) => m.key === moduleKey)
      if (mod) return mod
    }

    // Fallback to dashboard
    return modulesRegistry.find((m) => m.key === 'dashboard')!
  }, [pathname])

  return activeModule
}
