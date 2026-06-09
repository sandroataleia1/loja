import type { SyncQueueEntry } from '../types'

// ---- API payload shapes (mirrors the Laravel POST /api/v1/sales endpoint) ----

export interface SaleItemApiPayload {
  product_uuid: string
  variant_uuid: string | null
  quantity:     number
  unit_price:   number   // cents
  total:        number   // cents
}

export interface SalePaymentApiPayload {
  method: string
  amount: number         // cents
}

export interface SaleApiPayload {
  idempotency_key: string  // = sync_queue.uuid — prevents duplicate processing
  sale_uuid:       string
  store_uuid:      string
  cashier_uuid:    string | null
  subtotal:        number  // cents
  discount:        number  // cents
  total:           number  // cents
  occurred_at:     string  // ISO 8601 UTC
  items:           SaleItemApiPayload[]
  payments:        SalePaymentApiPayload[]
}

/**
 * Maps a raw SyncQueueEntry of type 'sale' into the API payload shape.
 * Requires the full sale detail to be fetched separately (payload only
 * contains `sale_uuid`).
 *
 * TODO: implement once the API integration layer is ready.
 */
export function serializeSaleEvent(_entry: SyncQueueEntry): SaleApiPayload {
  throw new Error('serializeSaleEvent: not implemented')
}
