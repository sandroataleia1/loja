'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import {
  costCentersService,
  type CostCenterFilters,
  type CreateCostCenterPayload,
} from '@/services/cost-centers.service'

const KEYS = {
  all:    ['cost-centers']                                    as const,
  list:   (f?: CostCenterFilters) => ['cost-centers', 'list', f] as const,
  tree:                              ['cost-centers', 'tree']     as const,
  detail: (uuid: string)         => ['cost-centers', uuid]       as const,
}

export function useCostCenters(filters?: CostCenterFilters) {
  return useQuery({
    queryKey: KEYS.list(filters),
    queryFn:  () => costCentersService.getCostCenters(filters),
    staleTime: 2 * 60_000,
  })
}

export function useCostCenterTree() {
  return useQuery({
    queryKey: KEYS.tree,
    queryFn:  () => costCentersService.getCostCenterTree(),
    staleTime: 2 * 60_000,
  })
}

export function useCreateCostCenter() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateCostCenterPayload) => costCentersService.createCostCenter(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEYS.all })
      toast.success('Centro de custo criado.')
    },
    onError: (err: Error) => toast.error(`Erro ao criar centro de custo: ${err.message}`),
  })
}

export function useUpdateCostCenter() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Partial<CreateCostCenterPayload> }) =>
      costCentersService.updateCostCenter(uuid, data),
    onSuccess: (cc) => {
      qc.invalidateQueries({ queryKey: KEYS.detail(cc.uuid) })
      qc.invalidateQueries({ queryKey: KEYS.all })
      toast.success('Centro de custo atualizado.')
    },
    onError: (err: Error) => toast.error(`Erro ao atualizar centro de custo: ${err.message}`),
  })
}

export function useDeleteCostCenter() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => costCentersService.deleteCostCenter(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEYS.all })
      toast.success('Centro de custo removido.')
    },
    onError: (err: Error) => toast.error(`Erro ao remover centro de custo: ${err.message}`),
  })
}
