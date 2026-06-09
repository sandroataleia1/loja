import type { User, Tenant, Store, Channel, Membership } from '@store/shared-types'

export type { User, Tenant, Store, Channel, Membership }

export interface AuthState {
  user:            User | null
  tenant:          Tenant | null
  store:           Store | null
  channel:         Channel | null
  memberships:     Membership[]
  permissions:     string[]
  isAuthenticated: boolean
  isLoading:       boolean
}

export interface AuthContextValue extends AuthState {
  login:         (token: string, user: User, tenant: Tenant, store: Store | null, channel: Channel | null) => void
  logout:        () => Promise<void>
  refresh:       () => Promise<void>
  hasPermission: (slug: string) => boolean
}
