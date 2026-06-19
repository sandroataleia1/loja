import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { salesService } from '@/services/sales.service'

export const PDV_KEYS = {
  REGISTERS: ['pdv', 'registers'] as const,
  SESSIONS:  ['pdv', 'sessions']  as const,
}

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
