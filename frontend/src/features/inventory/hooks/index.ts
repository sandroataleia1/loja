'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  inventoryService,
  type InventoryFilters,
  type MovementFilters,
  type TransferFilters,
} from '@/services/inventory.service'
import type {
  CreateAdjustmentRequest,
  CreateTransferRequest,
  DispatchTransferRequest,
  ReceiveTransferRequest,
} from '@store/contracts'

// ── Query Keys ────────────────────────────────────────────────────────────────

export const INVENTORY_KEYS = {
  BALANCES:    (f?: InventoryFilters) => ['inventory-balances', f] as const,
  MOVEMENTS:   (f?: MovementFilters)  => ['inventory-movements', f] as const,
  ADJUSTMENTS: (f?: object)           => ['inventory-adjustments', f] as const,
  TRANSFERS:   (f?: TransferFilters)  => ['transfers', f] as const,
  TRANSFER:    (uuid: string)         => ['transfers', uuid] as const,
  STORES:      ['stores'] as const,
}

// ── Balances ──────────────────────────────────────────────────────────────────

export function useInventoryBalances(filters?: InventoryFilters) {
  return useQuery({
    queryKey: INVENTORY_KEYS.BALANCES(filters),
    queryFn:  () => inventoryService.getBalances(filters),
    staleTime: 60_000,
  })
}

// ── Movements ─────────────────────────────────────────────────────────────────

export function useStockMovements(filters?: MovementFilters) {
  return useQuery({
    queryKey: INVENTORY_KEYS.MOVEMENTS(filters),
    queryFn:  () => inventoryService.getMovements(filters),
    staleTime: 30_000,
  })
}

// ── Adjustments ───────────────────────────────────────────────────────────────

export function useInventoryAdjustments(filters?: { store_id?: string; variant_id?: string }) {
  return useQuery({
    queryKey: INVENTORY_KEYS.ADJUSTMENTS(filters),
    queryFn:  () => inventoryService.getAdjustments(filters),
    staleTime: 60_000,
  })
}

export function useCreateAdjustment() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateAdjustmentRequest) => inventoryService.createAdjustment(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['inventory-balances'] })
      qc.invalidateQueries({ queryKey: ['inventory-adjustments'] })
    },
  })
}

// ── Transfers ─────────────────────────────────────────────────────────────────

export function useTransfers(filters?: TransferFilters) {
  return useQuery({
    queryKey: INVENTORY_KEYS.TRANSFERS(filters),
    queryFn:  () => inventoryService.getTransfers(filters),
    staleTime: 30_000,
  })
}

export function useTransfer(uuid: string) {
  return useQuery({
    queryKey: INVENTORY_KEYS.TRANSFER(uuid),
    queryFn:  () => inventoryService.getTransfer(uuid),
    enabled:  Boolean(uuid),
  })
}

export function useCreateTransfer() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateTransferRequest) => inventoryService.createTransfer(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['transfers'] }),
  })
}

export function useDispatchTransfer(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: DispatchTransferRequest) => inventoryService.dispatchTransfer(uuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['transfers', uuid] })
      qc.invalidateQueries({ queryKey: ['inventory-balances'] })
    },
  })
}

export function useReceiveTransfer(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: ReceiveTransferRequest) => inventoryService.receiveTransfer(uuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['transfers', uuid] })
      qc.invalidateQueries({ queryKey: ['inventory-balances'] })
    },
  })
}

export function useCancelTransfer(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: () => inventoryService.cancelTransfer(uuid),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['transfers', uuid] }),
  })
}

// ── Stores ────────────────────────────────────────────────────────────────────

export function useStores() {
  return useQuery({
    queryKey: INVENTORY_KEYS.STORES,
    queryFn:  () => inventoryService.getStores(),
    staleTime: 10 * 60_000,
  })
}
