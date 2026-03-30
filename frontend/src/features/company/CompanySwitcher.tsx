import { useState, useRef, useEffect } from 'react'
import { Building2, Check, ChevronsUpDown } from 'lucide-react'
import { useCompany } from '@/core/state/useCompany'
import { cn } from '@/lib/utils'

export function CompanySwitcher() {
  const { activeCompany, availableCompanies, setCompany } = useCompany()
  const [open, setOpen] = useState(false)
  const dropdownRef = useRef<HTMLDivElement>(null)

  // Close dropdown when clicking outside
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setOpen(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  if (!activeCompany) return null

  return (
    <div className="relative" ref={dropdownRef}>
      <button
        onClick={() => setOpen(!open)}
        className="flex items-center justify-between gap-2 px-3 py-1.5 text-sm font-medium text-neutral-700 bg-neutral-100 hover:bg-neutral-200 dark:text-neutral-200 dark:bg-neutral-800 dark:hover:bg-neutral-700 rounded-md transition-colors min-w-[160px]"
      >
        <div className="flex items-center gap-2 truncate">
          <Building2 className="h-4 w-4 shrink-0 text-primary" />
          <span className="truncate">{activeCompany.name}</span>
        </div>
        <ChevronsUpDown className="h-3 w-3 shrink-0 opacity-50" />
      </button>

      {open && (
        <div className="absolute top-full right-0 mt-1 w-56 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-md shadow-lg z-50 overflow-hidden">
          <div className="px-3 py-2 text-xs font-semibold text-neutral-500 uppercase tracking-wider border-b border-neutral-100 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900/50">
            Switch Operating Company
          </div>
          <div className="py-1">
            {availableCompanies.map((company) => (
              <button
                key={company.id}
                onClick={() => {
                  setCompany(company.id)
                  setOpen(false)
                }}
                className={cn(
                  "w-full flex items-center justify-between px-3 py-2 text-sm text-left hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors",
                  activeCompany.id === company.id ? "text-primary font-medium" : "text-neutral-700 dark:text-neutral-300"
                )}
              >
                <div className="flex items-center gap-2 truncate">
                  <span className="truncate">{company.name}</span>
                </div>
                {activeCompany.id === company.id && (
                  <Check className="h-4 w-4 shrink-0" />
                )}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}
