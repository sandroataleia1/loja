import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export interface PdvSessionData {
  sessionUuid:         string
  registerUuid:        string
  registerName:        string
  storeUuid:           string
  openedAt:            string
  openingBalanceCents: number
}

interface PdvSessionStore {
  session:          PdvSessionData | null
  setSession:       (data: PdvSessionData) => void
  clearSession:     () => void
  hasActiveSession: () => boolean
}

export const usePdvSessionStore = create<PdvSessionStore>()(
  persist(
    (set, get) => ({
      session:          null,
      setSession:       (data) => set({ session: data }),
      clearSession:     () => set({ session: null }),
      hasActiveSession: () => get().session !== null,
    }),
    { name: 'pdv-web-session' },
  ),
)
