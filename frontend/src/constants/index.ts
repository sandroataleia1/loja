export { ROUTES } from './routes'

export const APP_NAME = 'Store Admin'

export const TOKEN_KEY   = 'store_token'
export const COOKIE_KEY  = 'store_session'

export const QUERY_KEYS = {
  ME:        ['auth', 'me']        as const,
  USER:      ['auth', 'user']      as const,
  DASHBOARD: ['dashboard', 'data'] as const,
} as const

export const RBAC_QUERY_KEYS = {
  ROLES:       ['rbac', 'roles']       as const,
  ROLE:        (uuid: string) => ['rbac', 'roles', uuid] as const,
  PERMISSIONS: ['rbac', 'permissions'] as const,
  USERS:       ['rbac', 'users']       as const,
  USER:        (uuid: string) => ['rbac', 'users', uuid] as const,
} as const
