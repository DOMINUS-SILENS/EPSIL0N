/* eslint-disable react-refresh/only-export-components */
import React from 'react'
import { usePermissions } from './useAuth'

interface PermissionGuardProps {
  permission?: string
  hasAny?: string[]
  hasAll?: string[]
  fallback?: React.ReactNode
  children: React.ReactNode
}

/**
 * Enterprise ABAC/RBAC Permission Wrapper
 * Conditionally renders children based on user permissions or context-aware rules.
 */
export function PermissionGuard({
  permission,
  hasAny,
  hasAll,
  fallback = null,
  children
}: PermissionGuardProps) {
  const { can, canAny, canAll } = usePermissions()

  // Handle single permission check
  if (permission && !can(permission)) {
    return <>{fallback}</>
  }

  // Handle ANY permission check (OR logic)
  if (hasAny && hasAny.length > 0 && !canAny(hasAny)) {
    return <>{fallback}</>
  }

  // Handle ALL permission check (AND logic)
  if (hasAll && hasAll.length > 0 && !canAll(hasAll)) {
    return <>{fallback}</>
  }

  return <>{children}</>
}

/**
 * Higher Order Component for Route/Page level protection
 */
export function withPermission(Component: React.ComponentType<any>, permission: string) {
  return function ProtectedRoute(props: any) {
    return (
      <PermissionGuard permission={permission} fallback={<UnauthorizedPage />}>
        <Component {...props} />
      </PermissionGuard>
    )
  }
}

// Simple internal fallback component for route protection
function UnauthorizedPage() {
  return (
    <div className="flex flex-col items-center justify-center p-12 text-center h-full min-h-[400px]">
      <div className="h-16 w-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mb-4">
        <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <h2 className="text-xl font-bold text-neutral-900 dark:text-neutral-100">Access Denied</h2>
      <p className="text-neutral-500 mt-2 max-w-sm">
        You do not have the required permissions to view this resource. 
        Contact your administrator to request access.
      </p>
    </div>
  )
}
