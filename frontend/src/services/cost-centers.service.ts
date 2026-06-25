import { apiGet, apiPost, apiPut, apiDelete } from '@/lib/api-client'

export type CostCenterType = 'ADMINISTRATIVE' | 'COMMERCIAL' | 'LOGISTICS' | 'PURCHASING' | 'FINANCIAL'

export const COST_CENTER_TYPE_LABELS: Record<CostCenterType, string> = {
  ADMINISTRATIVE: 'Administrativo',
  COMMERCIAL:     'Comercial',
  LOGISTICS:      'Logística',
  PURCHASING:     'Compras',
  FINANCIAL:      'Financeiro',
}

export interface CostCenter {
  uuid:        string
  parent_id?:  string
  code:        string
  name:        string
  type:        CostCenterType
  type_label:  string
  is_active:   boolean
  children?:   CostCenter[]
  created_at:  string
  updated_at:  string
}

export interface CostCenterFilters {
  active?: boolean
  type?:   CostCenterType
}

export interface CreateCostCenterPayload {
  parent_id?: string
  code:       string
  name:       string
  type:       CostCenterType
  is_active?: boolean
}

export const costCentersService = {
  getCostCenters(filters?: CostCenterFilters): Promise<CostCenter[]> {
    const p = new URLSearchParams()
    if (filters?.active !== undefined) p.set('active', filters.active ? '1' : '0')
    if (filters?.type)                 p.set('type', filters.type)
    const qs = p.toString()
    return apiGet<CostCenter[]>(`/cost-centers${qs ? `?${qs}` : ''}`)
  },

  getCostCenterTree(): Promise<CostCenter[]> {
    return apiGet<CostCenter[]>('/cost-centers/tree')
  },

  getCostCenter(uuid: string): Promise<CostCenter> {
    return apiGet<CostCenter>(`/cost-centers/${uuid}`)
  },

  createCostCenter(data: CreateCostCenterPayload): Promise<CostCenter> {
    return apiPost<CostCenter, CreateCostCenterPayload>('/cost-centers', data)
  },

  updateCostCenter(uuid: string, data: Partial<CreateCostCenterPayload>): Promise<CostCenter> {
    return apiPut<CostCenter, Partial<CreateCostCenterPayload>>(`/cost-centers/${uuid}`, data)
  },

  deleteCostCenter(uuid: string): Promise<void> {
    return apiDelete(`/cost-centers/${uuid}`)
  },
}
