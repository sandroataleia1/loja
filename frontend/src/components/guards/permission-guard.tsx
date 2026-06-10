'use client'

import type { ReactNode } from 'react'
import { useAuth } from '@/hooks/use-auth'

interface PermissionGuardProps {
  /** Single permission slug required */
  permission?:  string
  /** Multiple permission slugs — any match by default, all required when all=true */
  permissions?: string[]
  /** Require ALL listed permissions (default: any match) */
  all?:         boolean
  /** Rendered when the check fails */
  fallback?:    ReactNode
  children:     ReactNode
}

/**
 * Renders children only when the authenticated user has the required
 * permission(s). During loading it shows children (fail-open) so the
 * UI does not flash away on page load.
 */
export function PermissionGuard({
  permission,
  permissions,
  all = false,
  fallback = null,
  children,
}: PermissionGuardProps) {
  const { hasPermission, isLoading, permissions: userPermissions } = useAuth()

  // Fail-open during loading so items don't flicker away
  if (isLoading) return <>{children}</>

  // No permission constraint → always show
  if (!permission && (!permissions || permissions.length === 0)) {
    return <>{children}</>
  }

  // If permissions array is empty (user has no known permissions yet) show
  if (userPermissions.length === 0) return <>{children}</>

  let allowed = false

  if (permission) {
    allowed = hasPermission(permission)
  } else if (permissions && permissions.length > 0) {
    allowed = all
      ? permissions.every((slug) => hasPermission(slug))
      : permissions.some((slug) => hasPermission(slug))
  }

  return allowed ? <>{children}</> : <>{fallback}</>
}
