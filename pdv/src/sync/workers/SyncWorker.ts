import type { SyncAttemptResult, SyncEntityType, SyncQueueEntry } from '../types'

/**
 * Dispatches a sync_queue entry to the appropriate entity handler.
 * Individual handlers are stubbed — they will call the Laravel API
 * once the API integration layer is implemented.
 */
export class SyncWorker {
  async process(entry: SyncQueueEntry): Promise<SyncAttemptResult> {
    switch (entry.entity_type as SyncEntityType) {
      case 'sale':             return this.processSale(entry)
      case 'product':          return this.processProduct(entry)
      case 'product_variant':  return this.processProductVariant(entry)
      case 'customer':         return this.processCustomer(entry)
      default:
        return {
          ok:        false,
          retryable: false,
          error:     `unknown entity_type: ${entry.entity_type}`,
        }
    }
  }

  private processSale(_entry: SyncQueueEntry): Promise<SyncAttemptResult> {
    return Promise.resolve({ ok: false, retryable: true, error: 'not_implemented' })
  }

  private processProduct(_entry: SyncQueueEntry): Promise<SyncAttemptResult> {
    return Promise.resolve({ ok: false, retryable: true, error: 'not_implemented' })
  }

  private processProductVariant(_entry: SyncQueueEntry): Promise<SyncAttemptResult> {
    return Promise.resolve({ ok: false, retryable: true, error: 'not_implemented' })
  }

  private processCustomer(_entry: SyncQueueEntry): Promise<SyncAttemptResult> {
    return Promise.resolve({ ok: false, retryable: true, error: 'not_implemented' })
  }
}
