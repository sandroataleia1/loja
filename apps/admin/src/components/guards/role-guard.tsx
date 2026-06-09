'use client'

import type { ReactNode } from 'react'
import { useAuth } from '@/hooks/use-auth'

interface RoleGuardProps {
  /** Single role slug required */
  role?:     string
  /** Multiple role slugs — any match grants access */
  roles?:    string[]
  /** Rendered when the check fails */
  fallback?: ReactNode
  children:  ReactNode
}

/**
 * Renders children only when the authenticated user holds one of the
 * given role slugs. During loading it shows children (fail-open).
 */
export function RoleGuard({ role, roles, fallback = null, children }: RoleGuardProps) {
  const { memberships, isLoading } = useAuth()

  // Fail-open during loading
  if (isLoading) return <>{children}</>

  // No constraint → always show
  if (!role && (!roles || roles.length === 0)) return <>{children}</>

  const requiredRoles = role ? [role] : (roles ?? [])

  const userRoleSlugs = memberships
    .filter((m) => m.is_active)
    .map((m) => m.role?.slug)
    .filter((slug): slug is string => Boolean(slug))

  const allowed = requiredRoles.some((r) => userRoleSlugs.includes(r))

  return allowed ? <>{children}</> : <>{fallback}</>
}
