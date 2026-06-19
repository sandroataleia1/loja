'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import {
  purchasingService,
  type SupplierFilters,
  type PurchaseOrderFilters,
  type CreateSupplierPayload,
  type CreatePurchaseOrderPayload,
  type ReceivePayload,
} from '@/services/purchasing.service'

const KEYS = {
  suppliers:       (f?: SupplierFilters)        => ['purchasing', 'suppliers', f]          as const,
  suppliersAll:                                     ['purchasing', 'suppliers']             as const,
  orders:          (f?: PurchaseOrderFilters)   => ['purchasing', 'orders', f]             as const,
  ordersAll:                                        ['purchasing', 'orders']                as const,
  order:           (uuid: string)               => ['purchasing', 'orders', uuid]          as const,
}

// ── Suppliers ─────────────────────────────────────────────────────────────────

export function useSuppliers(filters?: SupplierFilters) {
  return useQuery({
    queryKey: KEYS.suppliers(filters),
    queryFn:  () => purchasingService.getSuppliers(filters),
    staleTime: 60_000,
  })
}

export function useAllSuppliers() {
  return useQuery({
    queryKey: KEYS.suppliers({ active: true, per_page: 200 }),
    queryFn:  () => purchasingService.getSuppliers({ active: true, per_page: 200 }),
    staleTime: 5 * 60_000,
    select:   (res) => res.data,
  })
}

export function useCreateSupplier() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateSupplierPayload) => purchasingService.createSupplier(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEYS.suppliersAll })
      toast.success('Fornecedor criado.')
    },
    onError: (err: Error) => toast.error(`Erro ao criar fornecedor: ${err.message}`),
  })
}

export function useUpdateSupplier() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Partial<CreateSupplierPayload> & { is_active?: boolean } }) =>
      purchasingService.updateSupplier(uuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEYS.suppliersAll })
      toast.success('Fornecedor atualizado.')
    },
    onError: (err: Error) => toast.error(`Erro ao atualizar fornecedor: ${err.message}`),
  })
}

export function useDeleteSupplier() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => purchasingService.deleteSupplier(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEYS.suppliersAll })
      toast.success('Fornecedor removido.')
    },
    onError: (err: Error) => toast.error(`Erro ao remover fornecedor: ${err.message}`),
  })
}

// ── Purchase Orders ───────────────────────────────────────────────────────────

export function usePurchaseOrders(filters?: PurchaseOrderFilters) {
  return useQuery({
    queryKey: KEYS.orders(filters),
    queryFn:  () => purchasingService.getPurchaseOrders(filters),
    staleTime: 30_000,
  })
}

export function usePurchaseOrder(uuid: string) {
  return useQuery({
    queryKey: KEYS.order(uuid),
    queryFn:  () => purchasingService.getPurchaseOrder(uuid),
    staleTime: 15_000,
  })
}

export function useCreatePurchaseOrder() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreatePurchaseOrderPayload) => purchasingService.createPurchaseOrder(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEYS.ordersAll })
      toast.success('Pedido de compra criado.')
    },
    onError: (err: Error) => toast.error(`Erro ao criar pedido: ${err.message}`),
  })
}

export function useSendPurchaseOrder() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => purchasingService.sendPurchaseOrder(uuid),
    onSuccess: (order) => {
      qc.invalidateQueries({ queryKey: KEYS.order(order.uuid) })
      qc.invalidateQueries({ queryKey: KEYS.ordersAll })
      toast.success('Pedido enviado ao fornecedor.')
    },
    onError: (err: Error) => toast.error(`Erro ao enviar pedido: ${err.message}`),
  })
}

export function useCancelPurchaseOrder() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => purchasingService.cancelPurchaseOrder(uuid),
    onSuccess: (order) => {
      qc.invalidateQueries({ queryKey: KEYS.order(order.uuid) })
      qc.invalidateQueries({ queryKey: KEYS.ordersAll })
      toast.success('Pedido cancelado.')
    },
    onError: (err: Error) => toast.error(`Erro ao cancelar pedido: ${err.message}`),
  })
}

export function useReceivePurchaseOrder() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: ReceivePayload }) =>
      purchasingService.receivePurchaseOrder(uuid, data),
    onSuccess: (order) => {
      qc.invalidateQueries({ queryKey: KEYS.order(order.uuid) })
      qc.invalidateQueries({ queryKey: KEYS.ordersAll })
      toast.success('Recebimento registrado. Estoque atualizado.')
    },
    onError: (err: Error) => toast.error(`Erro ao registrar recebimento: ${err.message}`),
  })
}
