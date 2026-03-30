import { Link } from '@tanstack/react-router'
import { cn } from '@/lib/utils'
import { modulesRegistry } from './moduleRegistry'
import { useActiveModule } from './useActiveModule'
import { usePermissions } from '@/core/auth/useAuth'

export function HeadMenu() {
  const activeModule = useActiveModule()
  const { canAny } = usePermissions()

  // Filter modules based on whether the user has ANY permission to view its routes
  const visibleModules = modulesRegistry.filter(module => {
    if (!module.permission) return true

    const requiredPerms = [
      module.permission,
      ...module.routes.map(r => r.permission).filter(Boolean) as string[]
    ]
    return canAny(requiredPerms)
  })

  return (
    <nav className="flex items-center gap-1 px-2 bg-white/50 dark:bg-neutral-900/50 backdrop-blur-sm border-b border-neutral-200 dark:border-neutral-800">
      <div className="flex items-center gap-1 min-w-max px-2">
        {visibleModules.map((module) => {
          const isActive = activeModule.key === module.key

          return (
            <Link
              key={module.key}
              to={module.routes[0]?.path || '/'}
              className={cn(
                'relative flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-full transition-all duration-200 whitespace-nowrap group',
                isActive
                  ? 'bg-primary text-primary-foreground shadow-md shadow-primary/20 scale-105'
                  : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 dark:hover:text-neutral-100'
              )}
            >
              <module.icon className={cn(
                "h-4 w-4 transition-transform duration-200 group-hover:scale-110",
                isActive ? "text-primary-foreground" : "text-neutral-500"
              )} />
              <span>{module.label}</span>
              {isActive && (
                <div className="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1 h-3 bg-primary rounded-full hidden" />
              )}
            </Link>
          )
        })}
      </div>
    </nav>
  )
}


