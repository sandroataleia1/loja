import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface UiStore {
  sidebarExpanded: boolean
  toggleSidebar:   () => void
  setSidebar:      (expanded: boolean) => void
}

export const useUiStore = create<UiStore>()(
  persist(
    (set) => ({
      sidebarExpanded: false, // collapsed por padrão → máximo espaço para o PDV
      toggleSidebar:   () => set((s) => ({ sidebarExpanded: !s.sidebarExpanded })),
      setSidebar:      (expanded) => set({ sidebarExpanded: expanded }),
    }),
    { name: 'pdv-ui' },
  ),
)
