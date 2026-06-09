/**
 * src/services/saleDb.service.ts
 *
 * Offline-first sale persistence service.
 *
 * Delegates to the existing Rust `sale_create` Tauri command which atomically:
 *   1. INSERTs the sale + items + payments into SQLite
 *   2. Decrements variant stock
 *   3. Enqueues a sync_queue entry for later API sync
 *
 * This service maps from the Zustand CartStore shape (CartItem / CartPayment)
 * to the Rust CreateSaleInput shape, so the POS page never has to deal with
 * the conversion manually.
 */

import type { CartItem, CartPayment, Sale, SaleDetail } from '@/types/sale'
import { saleService } from './sale.service'

export interface CreateLocalSaleParams {
  tenantUuid:    string
  storeUuid:     string
  userUuid:      string
  sessionUuid?:  string | null
  customerUuid?: string | null
  items:         CartItem[]
  payments:      CartPayment[]
  discountCents: number
  notes?:        string
}

/**
 * Creates a sale in the local SQLite database via Rust `sale_create`.
 *
 * The operation is fully offline-capable: it persists immediately and the
 * SyncEngine will upload it to the backend API when connectivity is restored.
 *
 * Returns the created Sale row as returned by Rust.
 */
export async function createLocalSale(params: CreateLocalSaleParams): Promise<Sale> {
  return saleService.create({
    customer_uuid: params.customerUuid ?? undefined,
    user_uuid:     params.userUuid,
    discount:      params.discountCents > 0 ? params.discountCents : undefined,
    notes:         params.notes,
    items: params.items.map((item) => ({
      product_uuid:         item.productUuid,
      variant_uuid:         item.variantUuid ?? null,
      product_name:         item.productName,
      product_sku:          item.productSku,
      name_snapshot:        item.productName,
      sku_snapshot:         item.productSku,
      attributes_snapshot:  item.attributesJson ?? '{}',
      cost_snapshot:        item.cost ?? null,
      quantity:             item.quantity,
      unit_price:           item.unitPrice,
      discount:             item.discount > 0 ? item.discount : undefined,
    })),
    payments: params.payments.map((p) => ({
      method:    p.method,
      amount:    p.amount,
      reference: p.reference || undefined,
    })),
  })
}

/**
 * Fetch a paginated list of local sales (most recent first).
 */
export async function listLocalSales(limit = 50, offset = 0): Promise<Sale[]> {
  return saleService.list({ limit, offset })
}

/**
 * Fetch full detail (sale + items + payments) for a single sale.
 */
export async function getLocalSale(uuid: string): Promise<SaleDetail | null> {
  return saleService.get(uuid)
}
