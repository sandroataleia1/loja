'use client'

import { useQuery } from '@tanstack/react-query'
import { auditService, type AuditLogParams } from '@/services/audit.service'

export const AUDIT_QUERY_KEYS = {
  LOGS:    (params: AuditLogParams) => ['audit', 'logs', params] as const,
  FILTERS: ['audit', 'filters'] as const,
}

export function useAuditLogs(params: AuditLogParams = {}) {
  return useQuery({
    queryKey: AUDIT_QUERY_KEYS.LOGS(params),
    queryFn:  () => auditService.getLogs(params),
    staleTime: 30 * 1000,
  })
}

export function useAuditFilters() {
  return useQuery({
    queryKey: AUDIT_QUERY_KEYS.FILTERS,
    queryFn:  () => auditService.getFilters(),
    staleTime: 10 * 60 * 1000,
  })
}
