import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { LoginResult } from '@/services/auth.service'
import { apiLogout } from '@/services/auth.service'

interface SessionStore {
  isLoggedIn:     boolean
  apiToken:       string | null
  userUuid:       string
  userName:       string
  userEmail:      string
  tenantUuid:     string
  storeUuid:      string
  channelUuid:    string
  isOpen:         boolean
  openedAt:       string | null
  openingBalance: number   // centavos — fundo de troca

  login:         (result: LoginResult) => void
  logout:        () => void
  openRegister:  (balance: number) => void
  closeRegister: () => void
}

export const useSessionStore = create<SessionStore>()(
  persist(
    (set) => ({
      isLoggedIn:     false,
      apiToken:       null,
      userUuid:       '',
      userName:       '',
      userEmail:      '',
      tenantUuid:     '',
      storeUuid:      '',
      channelUuid:    '',
      isOpen:         false,
      openedAt:       null,
      openingBalance: 0,

      login: (result) => set({
        isLoggedIn:  true,
        apiToken:    result.token,
        userUuid:    result.user.uuid,
        userName:    result.user.name,
        userEmail:   result.user.email,
        tenantUuid:  result.tenant?.uuid ?? '',
        storeUuid:   result.store?.uuid   ?? '',
        channelUuid: result.channel?.uuid ?? '',
      }),

      logout: () => {
        const token = useSessionStore.getState().apiToken
        if (token) apiLogout(token).catch(() => {})
        set({
          isLoggedIn:     false,
          apiToken:       null,
          userUuid:       '',
          userName:       '',
          userEmail:      '',
          tenantUuid:     '',
          storeUuid:      '',
          channelUuid:    '',
          isOpen:         false,
          openedAt:       null,
          openingBalance: 0,
        })
      },

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
