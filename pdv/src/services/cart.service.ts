import { db } from './database'
import type { Cart, CartWithItems, SaveCartInput } from '@/types/cart'

export const cartService = {
  list: (): Promise<Cart[]> =>
    db('cart_list', {}),

  get: (uuid: string): Promise<CartWithItems | null> =>
    db('cart_get', { uuid }),

  save: (input: SaveCartInput): Promise<CartWithItems> =>
    db('cart_save', { input }),

  delete: (uuid: string): Promise<boolean> =>
    db('cart_delete', { uuid }),
}
