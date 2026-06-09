'use client'

import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react'
import { authService } from '@/services/auth.service'
import { getToken, setToken, clearToken } from '@/lib/api-client'
import type { AuthContextValue, AuthState, User, Tenant, Store, Channel } from '@/types'
import type { Membership } from '@store/shared-types'

function extractPermissions(memberships: Membership[]): string[] {
  const slugs = new Set<string>()
  for (const m of memberships) {
    if (m.role?.permissions) {
      for (const p of m.role.permissions) {
        slugs.add(p.slug)
      }
    }
  }
  return Array.from(slugs)
}

const initialState: AuthState = {
  user:            null,
  tenant:          null,
  store:           null,
  channel:         null,
  memberships:     [],
  permissions:     [],
  isAuthenticated: false,
  isLoading:       true,
}

const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [state, setState] = useState<AuthState>(initialState)

  // ── Bootstrap: fetch /me if a token is present ─────────────────────────
  useEffect(() => {
    const token = getToken()
    if (!token) {
      setState((s) => ({ ...s, isLoading: false }))
      return
    }

    authService
      .me()
      .then(({ user, tenant, store, channel, memberships }) => {
        const resolvedMemberships = memberships ?? []
        setState({
          user,
          tenant:          tenant ?? null,
          store:           store  ?? null,
          channel:         channel ?? null,
          memberships:     resolvedMemberships,
          permissions:     extractPermissions(resolvedMemberships),
          isAuthenticated: true,
          isLoading:       false,
        })
      })
      .catch(() => {
        clearToken()
        setState({ ...initialState, isLoading: false })
      })
  }, [])

  // ── login: called by useLoginMutation after API success ────────────────
  const login = useCallback(
    (token: string, user: User, tenant: Tenant, store: Store | null, channel: Channel | null) => {
      setToken(token)
      setState({
        user,
        tenant,
        store,
        channel,
        memberships:     [],
        permissions:     [],
        isAuthenticated: true,
        isLoading:       false,
      })
    },
    []
  )

  // ── logout: revoke token then clear state ──────────────────────────────
  const logout = useCallback(async () => {
    try {
      await authService.logout()
    } catch {
      // ignore network errors on logout
    } finally {
      clearToken()
      setState({ ...initialState, isLoading: false })
    }
  }, [])

  // ── refresh: re-fetch /me ──────────────────────────────────────────────
  const refresh = useCallback(async () => {
    const token = getToken()
    if (!token) return
    try {
      const { user, tenant, store, channel, memberships } = await authService.me()
      const resolvedMemberships = memberships ?? []
      setState((s) => ({
        ...s,
        user,
        tenant:      tenant  ?? null,
        store:       store   ?? null,
        channel:     channel ?? null,
        memberships: resolvedMemberships,
        permissions: extractPermissions(resolvedMemberships),
      }))
    } catch {
      /* no-op */
    }
  }, [])

  // ── hasPermission: check if user has a given permission slug ──────────
  const hasPermission = useCallback(
    (slug: string): boolean => {
      return state.permissions.includes(slug)
    },
    [state.permissions]
  )

  return (
    <AuthContext.Provider value={{ ...state, login, logout, refresh, hasPermission }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuthContext(): AuthContextValue {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuthContext must be used inside <AuthProvider>')
  return ctx
}
