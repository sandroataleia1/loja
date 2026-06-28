'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { pricingService, type PriceListFilters, type PriceFilters } from '@/services/pricing.service'
import type { CreatePriceListRequest, UpdatePriceListRequest, UpsertPricesRequest } from '@store/contracts'

// ── Query Keys ────────────────────────────────────────────────────────────────

export const PRICING_KEYS = {
  PRICE_LISTS: (filters?: PriceListFilters) => ['price-lists', filters] as const,
  PRICE_LIST:  (uuid: string) => ['price-lists', uuid] as const,
  PRICES:      (uuid: string, filters?: PriceFilters) => ['prices', uuid, filters] as const,
  HISTORY:     (uuid: string) => ['price-history-list', uuid] as const,
}

// ── Price Lists ───────────────────────────────────────────────────────────────

export function usePriceLists(filters?: PriceListFilters) {
  return useQuery({
    queryKey: PRICING_KEYS.PRICE_LISTS(filters),
    queryFn:  () => pricingService.getPriceLists(filters),
    staleTime: 2 * 60 * 1000,
  })
}

export function usePriceList(uuid: string) {
  return useQuery({
    queryKey: PRICING_KEYS.PRICE_LIST(uuid),
    queryFn:  () => pricingService.getPriceList(uuid),
    enabled:  Boolean(uuid),
    staleTime: 2 * 60 * 1000,
  })
}

export function useCreatePriceList() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreatePriceListRequest) => pricingService.createPriceList(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['price-lists'] })
    },
  })
}

export function useUpdatePriceList(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: UpdatePriceListRequest) => pricingService.updatePriceList(uuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['price-lists'] })
      qc.invalidateQueries({ queryKey: PRICING_KEYS.PRICE_LIST(uuid) })
    },
  })
}

export function useDeletePriceList() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => pricingService.deletePriceList(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['price-lists'] })
    },
  })
}

// ── Prices ────────────────────────────────────────────────────────────────────

export function usePrices(priceListUuid: string, filters?: PriceFilters) {
  return useQuery({
    queryKey: PRICING_KEYS.PRICES(priceListUuid, filters),
    queryFn:  () => pricingService.getPrices(priceListUuid, filters),
    enabled:  Boolean(priceListUuid),
    staleTime: 1 * 60 * 1000,
  })
}

export function useUpsertPrices(priceListUuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: UpsertPricesRequest) => pricingService.upsertPrices(priceListUuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['prices', priceListUuid] })
    },
  })
}

// ── History ───────────────────────────────────────────────────────────────────

export function usePriceListHistory(uuid: string) {
  return useQuery({
    queryKey: PRICING_KEYS.HISTORY(uuid),
    queryFn:  () => pricingService.getHistory(uuid),
    enabled:  Boolean(uuid),
    staleTime: 5 * 60 * 1000,
  })
}
