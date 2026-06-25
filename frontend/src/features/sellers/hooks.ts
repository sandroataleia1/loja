'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import {
  sellersService,
  type SellerFilters,
  type CreateSellerPayload,
} from '@/services/sellers.service'

const KEYS = {
  all:    ['sellers']                              as const,
  list:   (f?: SellerFilters) => ['sellers', 'list', f] as const,
  detail: (uuid: string)      => ['sellers', uuid]      as const,
}

export function useSellers(filters?: SellerFilters) {
  return useQuery({
    queryKey: KEYS.list(filters),
    queryFn:  () => sellersService.getSellers(filters),
    staleTime: 60_000,
  })
}

export function useAllSellers() {
  return useQuery({
    queryKey: KEYS.list({ active: true, per_page: 200 }),
    queryFn:  () => sellersService.getSellers({ active: true, per_page: 200 }),
    staleTime: 5 * 60_000,
    select:   (res) => res.data,
  })
}

export function useSeller(uuid: string) {
  return useQuery({
    queryKey: KEYS.detail(uuid),
    queryFn:  () => sellersService.getSeller(uuid),
    enabled:  !!uuid,
    staleTime: 30_000,
  })
}

export function useCreateSeller() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateSellerPayload) => sellersService.createSeller(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEYS.all })
      toast.success('Vendedor habilitado.')
    },
    onError: (err: Error) => toast.error(`Erro ao habilitar vendedor: ${err.message}`),
  })
}

export function useUpdateSeller() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Partial<CreateSellerPayload> }) =>
      sellersService.updateSeller(uuid, data),
    onSuccess: (seller) => {
      qc.invalidateQueries({ queryKey: KEYS.detail(seller.uuid) })
      qc.invalidateQueries({ queryKey: KEYS.all })
      toast.success('Vendedor atualizado.')
    },
    onError: (err: Error) => toast.error(`Erro ao atualizar vendedor: ${err.message}`),
  })
}
