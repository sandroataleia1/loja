import { apiClient, apiGet, apiPost } from '@/lib/api-client'
import type {
  InventoryBalance,
  StockMovement,
  InventoryAdjustment,
  StockTransfer,
  Store,
  PaginatedResponse,
  PaginationMeta,
} from '@store/shared-types'
import type { ApiSuccess } from '@store/shared-types'
import type {
  CreateAdjustmentRequest,
  CreateTransferRequest,
  DispatchTransferRequest,
  ReceiveTransferRequest,
} from '@store/contracts'

// ── Filters ───────────────────────────────────────────────────────────────────

export interface InventoryFilters {
  store_id?:   string
  variant_id?: string
  low_stock?:  boolean
  q?:          string
  per_page?:   number
  page?:       number
}

export interface MovementFilters {
  store_id?:   string
  variant_id?: string
  type?:       string
  date_from?:  string
  date_to?:    string
  per_page?:   number
  page?:       number
}

export interface TransferFilters {
  status?:   string
  store_id?: string
}

// ── Service ───────────────────────────────────────────────────────────────────

export const inventoryService = {
  async getBalances(filters?: InventoryFilters): Promise<PaginatedResponse<InventoryBalance>> {
    const params = new URLSearchParams()
    if (filters?.store_id)   params.set('store_id', filters.store_id)
    if (filters?.variant_id) params.set('variant_id', filters.variant_id)
    if (filters?.low_stock)  params.set('low_stock', '1')
    if (filters?.q)          params.set('q', filters.q)
    if (filters?.per_page)   params.set('per_page', String(filters.per_page))
    if (filters?.page)       params.set('page', String(filters.page))
    const qs = params.toString()
    const res = await apiClient.get<ApiSuccess<InventoryBalance[]> & { meta?: PaginationMeta }>(
      `/inventory/balances${qs ? `?${qs}` : ''}`,
    )
    const body = res.data
    if (!body.success) throw new Error((body as unknown as { message: string }).message)
    return {
      data: body.data,
      meta: (body.meta as unknown as PaginationMeta) ?? {
        current_page: 1,
        per_page:     20,
        total:        0,
        last_page:    1,
      },
    }
  },

  async getMovements(filters?: MovementFilters): Promise<PaginatedResponse<StockMovement>> {
    const params = new URLSearchParams()
    if (filters?.store_id)   params.set('store_id', filters.store_id)
    if (filters?.variant_id) params.set('variant_id', filters.variant_id)
    if (filters?.type)       params.set('type', filters.type)
    if (filters?.date_from)  params.set('date_from', filters.date_from)
    if (filters?.date_to)    params.set('date_to', filters.date_to)
    if (filters?.per_page)   params.set('per_page', String(filters.per_page))
    if (filters?.page)       params.set('page', String(filters.page))
    const qs = params.toString()
    const res = await apiClient.get<ApiSuccess<StockMovement[]> & { meta?: PaginationMeta }>(
      `/inventory/movements${qs ? `?${qs}` : ''}`,
    )
    const body = res.data
    if (!body.success) throw new Error((body as unknown as { message: string }).message)
    return {
      data: body.data,
      meta: (body.meta as unknown as PaginationMeta) ?? {
        current_page: 1,
        per_page:     20,
        total:        0,
        last_page:    1,
      },
    }
  },

  getAdjustments(filters?: { store_id?: string; variant_id?: string }): Promise<InventoryAdjustment[]> {
    const p = new URLSearchParams()
    if (filters?.store_id)   p.set('store_id', filters.store_id)
    if (filters?.variant_id) p.set('variant_id', filters.variant_id)
    const qs = p.toString()
    return apiGet<InventoryAdjustment[]>(`/inventory/adjustments${qs ? `?${qs}` : ''}`)
  },

  createAdjustment(data: CreateAdjustmentRequest): Promise<InventoryAdjustment> {
    return apiPost<InventoryAdjustment, CreateAdjustmentRequest>('/inventory/adjustments', data)
  },

  getTransfers(filters?: TransferFilters): Promise<StockTransfer[]> {
    const p = new URLSearchParams()
    if (filters?.status)   p.set('status', filters.status)
    if (filters?.store_id) p.set('store_id', filters.store_id)
    const qs = p.toString()
    return apiGet<StockTransfer[]>(`/inventory/transfers${qs ? `?${qs}` : ''}`)
  },

  getTransfer(uuid: string): Promise<StockTransfer> {
    return apiGet<StockTransfer>(`/inventory/transfers/${uuid}`)
  },

  createTransfer(data: CreateTransferRequest): Promise<StockTransfer> {
    return apiPost<StockTransfer, CreateTransferRequest>('/inventory/transfers', data)
  },

  dispatchTransfer(uuid: string, data: DispatchTransferRequest): Promise<StockTransfer> {
    return apiPost<StockTransfer, DispatchTransferRequest>(`/inventory/transfers/${uuid}/dispatch`, data)
  },

  receiveTransfer(uuid: string, data: ReceiveTransferRequest): Promise<StockTransfer> {
    return apiPost<StockTransfer, ReceiveTransferRequest>(`/inventory/transfers/${uuid}/receive`, data)
  },

  cancelTransfer(uuid: string): Promise<StockTransfer> {
    return apiPost<StockTransfer, object>(`/inventory/transfers/${uuid}/cancel`, {})
  },

  getStores(): Promise<Store[]> {
    return apiGet<Store[]>('/inventory/stores')
  },
}
