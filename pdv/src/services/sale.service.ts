import { db } from './database'
import type { CreateSaleInput, Sale, SaleDetail } from '@/types/sale'

export const saleService = {
  create: (input: CreateSaleInput): Promise<Sale> =>
    db('sale_create', { input }),

  list: (params?: { limit?: number; offset?: number }): Promise<Sale[]> =>
    db('sale_list', { limit: params?.limit, offset: params?.offset }),

  get: (uuid: string): Promise<SaleDetail | null> =>
    db('sale_get', { uuid }),
}
