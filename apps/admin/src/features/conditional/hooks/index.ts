'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { conditionalService, type ConditionalFilters } from '@/services/conditional.service'
import type {
  CreateConditionalRequest,
  ReturnConditionalRequest,
  ConvertConditionalRequest,
} from '@store/contracts'

// ── Query Keys ────────────────────────────────────────────────────────────────

export const CONDITIONAL_QUERY_KEYS = {
  CONDITIONALS: (f?: ConditionalFilters) => ['conditionals', f] as const,
  CONDITIONAL:  (uuid: string)           => ['conditionals', uuid] as const,
}

// ── Queries ───────────────────────────────────────────────────────────────────

export function useConditionals(filters?: ConditionalFilters) {
  return useQuery({
    queryKey: CONDITIONAL_QUERY_KEYS.CONDITIONALS(filters),
    queryFn:  () => conditionalService.getConditionals(filters),
    staleTime: 2 * 60 * 1000,
  })
}

export function useConditional(uuid: string) {
  return useQuery({
    queryKey: CONDITIONAL_QUERY_KEYS.CONDITIONAL(uuid),
    queryFn:  () => conditionalService.getConditional(uuid),
    enabled:  Boolean(uuid),
    staleTime: 2 * 60 * 1000,
  })
}

// ── Mutations ─────────────────────────────────────────────────────────────────

export function useCreateConditional() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateConditionalRequest) => conditionalService.createConditional(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['conditionals'] })
    },
  })
}

export function useReturnConditional(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: ReturnConditionalRequest) => conditionalService.returnItems(uuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CONDITIONAL_QUERY_KEYS.CONDITIONAL(uuid) })
      qc.invalidateQueries({ queryKey: ['conditionals'] })
    },
  })
}

export function useConvertConditional(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: ConvertConditionalRequest) => conditionalService.convertItems(uuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CONDITIONAL_QUERY_KEYS.CONDITIONAL(uuid) })
      qc.invalidateQueries({ queryKey: ['conditionals'] })
    },
  })
}

export function useCancelConditional(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: () => conditionalService.cancelConditional(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CONDITIONAL_QUERY_KEYS.CONDITIONAL(uuid) })
      qc.invalidateQueries({ queryKey: ['conditionals'] })
    },
  })
}
