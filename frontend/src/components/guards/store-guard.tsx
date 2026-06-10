'use client'

import type { ReactNode } from 'react'
import { useAuth } from '@/hooks/use-auth'

interface StoreGuardProps {
  /** The store UUID to check access for */
  storeId:   string
  /** Rendered when the user cannot access this store */
  fallback?: ReactNode
  children:  ReactNode
}

/**
 * Renders children only when the authenticated user can access the given
 * store. A user is considered unrestricted (can access any store) when
 * their membership has an empty store_ids list.
 * During loading it shows children (fail-open).
 *
 * NOTE: TenantUserData is not stored in AuthState memberships (those are
 * the light Membership objects). This guard uses the store field from auth
 * state as a simple check; for full multi-store checks, use the server-side
 * authorization or fetch tenant-user data separately.
 */
export function StoreGuard({ storeId, fallback = null, children }: StoreGuardProps) {
  const { store, isLoading } = useAuth()

  // Fail-open during loading
  if (isLoading) return <>{children}</>

  // If no current store context, allow (server will enforce)
  if (!store) return <>{children}</>

  // Check if the active store matches the requested store
  const allowed = store.uuid === storeId

  return allowed ? <>{children}</> : <>{fallback}</>
}
