import { create } from 'zustand'

export interface PdvCartItem {
  id: string
  productUuid: string
  variantUuid: string | null
  name: string
  sku: string
  barcode: string | null
  unitPriceCents: number
  quantity: number
  discountCents: number
}

interface PdvCartStore {
  items: PdvCartItem[]
  customerUuid: string | null
  customerName: string | null
  discountCents: number
  notes: string

  addItem:        (item: Omit<PdvCartItem, 'id'>) => void
  removeItem:     (id: string) => void
  updateQty:      (id: string, qty: number) => void
  setItemDiscount:(id: string, cents: number) => void
  setDiscount:    (cents: number) => void
  setCustomer:    (uuid: string | null, name: string | null) => void
  setNotes:       (notes: string) => void
  clear:          () => void

  subtotalCents: () => number
  totalCents:    () => number
  itemCount:     () => number
}

export const usePdvCartStore = create<PdvCartStore>()((set, get) => ({
  items:        [],
  customerUuid: null,
  customerName: null,
  discountCents: 0,
  notes:        '',

  addItem: (item) => {
    const items    = get().items
    const existing = items.findIndex(
      (i) => i.productUuid === item.productUuid && i.variantUuid === item.variantUuid,
    )
    if (existing >= 0) {
      set({
        items: items.map((i, idx) =>
          idx === existing ? { ...i, quantity: i.quantity + item.quantity } : i,
        ),
      })
    } else {
      set({ items: [...items, { ...item, id: crypto.randomUUID() }] })
    }
  },

  removeItem: (id) => set({ items: get().items.filter((i) => i.id !== id) }),

  updateQty: (id, qty) => {
    if (qty <= 0) {
      set({ items: get().items.filter((i) => i.id !== id) })
    } else {
      set({ items: get().items.map((i) => (i.id === id ? { ...i, quantity: qty } : i)) })
    }
  },

  setItemDiscount: (id, cents) =>
    set({ items: get().items.map((i) => (i.id === id ? { ...i, discountCents: cents } : i)) }),

  setDiscount: (cents) => set({ discountCents: cents }),

  setCustomer: (uuid, name) => set({ customerUuid: uuid, customerName: name }),

  setNotes: (notes) => set({ notes }),

  clear: () =>
    set({ items: [], customerUuid: null, customerName: null, discountCents: 0, notes: '' }),

  subtotalCents: () =>
    get().items.reduce(
      (sum, item) => sum + item.unitPriceCents * item.quantity - item.discountCents,
      0,
    ),

  totalCents: () => Math.max(0, get().subtotalCents() - get().discountCents),

  itemCount: () => get().items.reduce((sum, item) => sum + item.quantity, 0),
}))
