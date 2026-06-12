import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { LoginResult } from '@/services/auth.service'
import { apiLogout } from '@/services/auth.service'
import { hashPin }   from '@/lib/pin-crypto'

interface SessionStore {
  // ── Sessão online ──────────────────────────────────────────────────────────
  isLoggedIn:     boolean
  apiToken:       string | null
  userUuid:       string
  userName:       string
  userEmail:      string
  tenantUuid:     string
  storeUuid:      string
  channelUuid:    string

  // ── Caixa ──────────────────────────────────────────────────────────────────
  isOpen:         boolean
  openedAt:       string | null
  openingBalance: number

  // ── PIN offline ────────────────────────────────────────────────────────────
  offlinePinHash: string | null  // SHA-256(pin:userUuid)
  hasOfflinePin:  boolean

  // ── Actions ────────────────────────────────────────────────────────────────
  login:          (result: LoginResult) => void
  logout:         () => void
  openRegister:   (balance: number) => void
  closeRegister:  () => void
  setOfflinePin:  (pin: string) => Promise<void>
  verifyOfflinePin: (pin: string) => Promise<boolean>
  clearOfflinePin: () => void
}

export const useSessionStore = create<SessionStore>()(
  persist(
    (set, get) => ({
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
      offlinePinHash: null,
      hasOfflinePin:  false,

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
        const token = get().apiToken
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
          // Mantém PIN offline para próximo login sem internet
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

      setOfflinePin: async (pin) => {
        const salt = get().userUuid || 'pdv-salt'
        const hash = await hashPin(pin, salt)
        set({ offlinePinHash: hash, hasOfflinePin: true })
      },

      verifyOfflinePin: async (pin) => {
        const { offlinePinHash, userUuid } = get()
        if (!offlinePinHash) return false
        const salt = userUuid || 'pdv-salt'
        const hash = await hashPin(pin, salt)
        return hash === offlinePinHash
      },

      clearOfflinePin: () => set({ offlinePinHash: null, hasOfflinePin: false }),
    }),
    { name: 'pdv-session' },
  ),
)
