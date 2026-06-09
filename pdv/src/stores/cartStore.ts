import { create } from 'zustand'
import type { CartItem } from '@/types/sale'

/** Interface mínima que addItem precisa — compatível com Product e CatalogProduct */
export interface CartableProduct {
  uuid:  string
  name:  string
  sku:   string
  price: number
  cost?: number  // opcional — usado para cost_snapshot no histórico de vendas
}

interface CartStore {
  // State
  items:        CartItem[]
  discount:     number       // desconto global em centavos
  customerUuid: string | null

  // Computed (called as functions)
  subtotal:  () => number
  total:     () => number
  itemCount: () => number

  // Actions
  /** Adiciona produto ao carrinho. variantUuid e attributes opcionais para variantes. */
  addItem:        (product: CartableProduct, variantUuid?: string | null, attributes?: Record<string, string>) => void
  removeItem:     (productUuid: string, variantUuid?: string | null) => void
  updateQuantity: (productUuid: string, quantity: number, variantUuid?: string | null) => void
  setDiscount:    (cents: number) => void
  setCustomer:    (uuid: string | null) => void
  clear:          () => void
}

export const useCartStore = create<CartStore>((set, get) => ({
  items:        [],
  discount:     0,
  customerUuid: null,

  subtotal: () =>
    get().items.reduce((sum, item) => sum + item.total, 0),

  total: () => {
    const { items, discount } = get()
    return Math.max(0, items.reduce((s, i) => s + i.total, 0) - discount)
  },

  itemCount: () =>
    get().items.reduce((sum, item) => sum + item.quantity, 0),

  addItem: (product, variantUuid, attributes) =>
    set((state) => {
      const match = (i: CartItem) =>
        i.productUuid === product.uuid &&
        (variantUuid ? i.variantUuid === variantUuid : !i.variantUuid)

      const existing = state.items.find(match)
      if (existing) {
        return {
          items: state.items.map((i) =>
            match(i)
              ? { ...i, quantity: i.quantity + 1, total: (i.quantity + 1) * i.unitPrice - i.discount }
              : i,
          ),
        }
      }
      const newItem: CartItem = {
        productUuid:     product.uuid,
        variantUuid:     variantUuid ?? null,
        productName:     product.name,
        productSku:      product.sku,
        attributesJson:  attributes ? JSON.stringify(attributes) : undefined,
        cost:            product.cost,
        quantity:        1,
        unitPrice:       product.price,
        discount:        0,
        total:           product.price,
      }
      return { items: [...state.items, newItem] }
    }),

  removeItem: (productUuid, variantUuid) =>
    set((state) => ({
      items: state.items.filter((i) =>
        !(i.productUuid === productUuid &&
          (variantUuid ? i.variantUuid === variantUuid : !i.variantUuid))
      ),
    })),

  updateQuantity: (productUuid, quantity, variantUuid) =>
    set((state) => {
      const match = (i: CartItem) =>
        i.productUuid === productUuid &&
        (variantUuid ? i.variantUuid === variantUuid : !i.variantUuid)
      return {
        items:
          quantity <= 0
            ? state.items.filter((i) => !match(i))
            : state.items.map((i) =>
                match(i)
                  ? { ...i, quantity, total: quantity * i.unitPrice - i.discount }
                  : i,
              ),
      }
    }),

  setDiscount: (cents) => set({ discount: cents }),

  setCustomer: (uuid) => set({ customerUuid: uuid }),

  clear: () => set({ items: [], discount: 0, customerUuid: null }),
}))

// Selector helpers — evitam renders desnecessários
export const selectSubtotal  = (s: CartStore) => s.subtotal()
export const selectTotal     = (s: CartStore) => s.total()
export const selectItemCount = (s: CartStore) => s.itemCount()
