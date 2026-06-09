import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface SessionStore {
  isLoggedIn:     boolean
  userUuid:       string
  userName:       string
  tenantUuid:     string
  storeUuid:      string
  isOpen:         boolean
  openedAt:       string | null
  openingBalance: number   // centavos — fundo de troca

  login:         (userName: string) => void
  logout:        () => void
  openRegister:  (balance: number) => void
  closeRegister: () => void
}

const DEFAULT_USER_UUID   = '00000000-0000-4000-8000-000000000003'
const DEFAULT_TENANT_UUID = '00000000-0000-4000-8000-000000000001'
const DEFAULT_STORE_UUID  = '00000000-0000-4000-8000-000000000002'

export const useSessionStore = create<SessionStore>()(
  persist(
    (set) => ({
      isLoggedIn:     false,
      userUuid:       DEFAULT_USER_UUID,
      userName:       'Administrador',
      tenantUuid:     DEFAULT_TENANT_UUID,
      storeUuid:      DEFAULT_STORE_UUID,
      isOpen:         false,
      openedAt:       null,
      openingBalance: 0,

      login: (userName) => set({ isLoggedIn: true, userName }),

      logout: () => set({
        isLoggedIn:     false,
        isOpen:         false,
        openedAt:       null,
        openingBalance: 0,
      }),

      openRegister: (balance) => set({
        isOpen:         true,
        openedAt:       new Date().toISOString(),
        openingBalance: balance,
      }),

      closeRegister: () => set({
        isOpen:   false,
        openedAt: null,
      }),
    }),
    { name: 'pdv-session' },
  ),
)
