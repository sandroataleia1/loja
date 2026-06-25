import { apiGet } from '@/lib/api-client'

export interface AuditLogEntry {
  uuid:           string
  tenant_id:      string
  store_id:       string | null
  user_id:        string | null
  entity_type:    string | null
  entity_uuid:    string | null
  action:         string
  action_label:   string
  is_high_risk:   boolean
  old_values:     Record<string, unknown> | null
  new_values:     Record<string, unknown> | null
  metadata:       Record<string, unknown> | null
  ip:             string | null
  user_agent:     string | null
  correlation_id: string | null
  created_at:     string
}

export interface AuditLogFiltersData {
  actions: { value: string; label: string; is_high_risk: boolean }[]
  entity_types: { value: string; label: string }[]
}

export interface AuditLogParams {
  action?:      string
  entity_type?: string
  entity_uuid?: string
  user_id?:     string
  date_from?:   string
  date_to?:     string
  high_risk?:   boolean
  page?:        number
}

const BASE = '/audit-logs'

export const auditService = {
  getLogs(params: AuditLogParams = {}): Promise<{ data: AuditLogEntry[]; meta: { total: number; per_page: number; current_page: number; last_page: number } }> {
    const qs = new URLSearchParams()
    Object.entries(params).forEach(([k, v]) => {
      if (v !== undefined && v !== '' && v !== null) qs.set(k, String(v))
    })
    const query = qs.toString()
    return apiGet(`${BASE}${query ? `?${query}` : ''}`)
  },

  getFilters(): Promise<AuditLogFiltersData> {
    return apiGet(`${BASE}/filters`)
  },
}
