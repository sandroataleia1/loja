import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { salesService } from '@/services/sales.service'
import { catalogService } from '@/services/catalog.service'
import { customerService } from '@/services/customer.service'

export const PDV_KEYS = {
  REGISTERS:  ['pdv', 'registers']                              as const,
  SESSIONS:   ['pdv', 'sessions']                               as const,
  MOVEMENTS:  (uuid: string) => ['pdv', 'movements', uuid]      as const,
  SUMMARY:    (uuid: string) => ['pdv', 'summary', uuid]        as const,
  PRODUCTS:   (q: string, cat?: string) => ['pdv', 'products', q, cat] as const,
  CATEGORIES: ['pdv', 'categories']                             as const,
  CUSTOMERS:  (q: string) => ['pdv', 'customers', q]            as const,
}

// ── Caixa ─────────────────────────────────────────────────────────────────────

export function useCashRegisters() {
  return useQuery({
    queryKey: PDV_KEYS.REGISTERS,
    queryFn:  () => salesService.getCashRegisters(),
    staleTime: 5 * 60_000,
  })
}

export function useOpenSession() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: Parameters<typeof salesService.openSession>[0]) =>
      salesService.openSession(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: PDV_KEYS.SESSIONS }),
  })
}

export function useCloseSession() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Parameters<typeof salesService.closeSession>[1] }) =>
      salesService.closeSession(uuid, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: PDV_KEYS.SESSIONS }),
  })
}

// ── Catálogo ──────────────────────────────────────────────────────────────────

export function useProductSearch(q: string, categoryId?: string) {
  return useQuery({
    queryKey: PDV_KEYS.PRODUCTS(q, categoryId),
    queryFn:  () =>
      catalogService.getProducts({
        q:           q || undefined,
        category_id: categoryId || undefined,
        status:      'active',
        per_page:    30,
      }),
    enabled:   q.length > 0 || Boolean(categoryId),
    staleTime: 30_000,
  })
}

export function usePdvCategories() {
  return useQuery({
    queryKey: PDV_KEYS.CATEGORIES,
    queryFn:  () => catalogService.getCategories(),
    staleTime: 5 * 60_000,
  })
}

// ── Movimentos de Caixa ───────────────────────────────────────────────────────

export function useSessionMovements(sessionUuid: string | null) {
  return useQuery({
    queryKey: PDV_KEYS.MOVEMENTS(sessionUuid ?? ''),
    queryFn:  () => salesService.getMovements(sessionUuid!),
    enabled:  Boolean(sessionUuid),
    staleTime: 15_000,
    refetchInterval: 30_000,
  })
}

export function useSessionSummary(sessionUuid: string | null) {
  return useQuery({
    queryKey: PDV_KEYS.SUMMARY(sessionUuid ?? ''),
    queryFn:  () => salesService.getSessionSummary(sessionUuid!),
    enabled:  Boolean(sessionUuid),
    staleTime: 30_000,
  })
}

export function useRecordSupply() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ sessionUuid, data }: {
      sessionUuid: string
      data: { amount_cents: number; description: string }
    }) => salesService.recordSupply(sessionUuid, data),
    onSuccess: (_, { sessionUuid }) => {
      toast.success('Suprimento registrado.')
      qc.invalidateQueries({ queryKey: PDV_KEYS.MOVEMENTS(sessionUuid) })
    },
    onError: (err: Error) => toast.error(`Erro ao registrar suprimento: ${err.message}`),
  })
}

export function useRecordWithdrawal() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ sessionUuid, data }: {
      sessionUuid: string
      data: { amount_cents: number; description: string }
    }) => salesService.recordWithdrawal(sessionUuid, data),
    onSuccess: (_, { sessionUuid }) => {
      toast.success('Sangria registrada.')
      qc.invalidateQueries({ queryKey: PDV_KEYS.MOVEMENTS(sessionUuid) })
    },
    onError: (err: Error) => toast.error(`Erro ao registrar sangria: ${err.message}`),
  })
}

// ── Clientes ──────────────────────────────────────────────────────────────────

export function useCustomerSearch(q: string) {
  return useQuery({
    queryKey: PDV_KEYS.CUSTOMERS(q),
    queryFn:  () => customerService.getCustomers({ q, active: true, per_page: 8 }),
    enabled:   q.length >= 2,
    staleTime: 60_000,
  })
}
