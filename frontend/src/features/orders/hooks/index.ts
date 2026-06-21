'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  quotesService,
  ordersService,
  type CreateQuoteRequest,
  type CreateOrderRequest,
} from '@/services/orders.service'
import type { Quote, Order } from '@store/shared-types'

// ── Query Keys ───────────────────────────────────────────────────────────────

export const ORDERS_QUERY_KEYS = {
  QUOTES:          ['quotes']                         as const,
  QUOTE:           (uuid: string) => ['quotes', uuid] as const,
  QUOTE_BY_NUMBER: (num: string)  => ['quotes', 'by-number', num] as const,
  ORDERS:          ['orders']                         as const,
  ORDER:           (uuid: string) => ['orders', uuid] as const,
  ORDER_BY_NUMBER: (num: string)  => ['orders', 'by-number', num] as const,
}

// ── Quotes ───────────────────────────────────────────────────────────────────

export function useQuotes(params?: Record<string, string | number>) {
  return useQuery({
    queryKey: [...ORDERS_QUERY_KEYS.QUOTES, params],
    queryFn:  () => quotesService.list(params),
    staleTime: 60 * 1000,
  })
}

export function useQuote(uuid: string) {
  return useQuery({
    queryKey: ORDERS_QUERY_KEYS.QUOTE(uuid),
    queryFn:  () => quotesService.get(uuid),
    enabled:  Boolean(uuid),
    staleTime: 60 * 1000,
  })
}

export function useQuoteByNumber(number: string) {
  return useQuery({
    queryKey: ORDERS_QUERY_KEYS.QUOTE_BY_NUMBER(number),
    queryFn:  () => quotesService.getByNumber(number),
    enabled:  Boolean(number),
    staleTime: 30 * 1000,
  })
}

export function useCreateQuote() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateQuoteRequest) => quotesService.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ORDERS_QUERY_KEYS.QUOTES })
    },
  })
}

export function useMarkQuoteSent() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => quotesService.markSent(uuid),
    onSuccess: (_data: Quote, uuid: string) => {
      qc.invalidateQueries({ queryKey: ORDERS_QUERY_KEYS.QUOTES })
      qc.invalidateQueries({ queryKey: ORDERS_QUERY_KEYS.QUOTE(uuid) })
    },
  })
}

export function useConvertQuote() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ uuid, expectedAt }: { uuid: string; expectedAt?: string }) =>
      quotesService.convert(uuid, expectedAt),
    onSuccess: (_data: Order, { uuid }: { uuid: string; expectedAt?: string }) => {
      qc.invalidateQueries({ queryKey: ORDERS_QUERY_KEYS.QUOTES })
      qc.invalidateQueries({ queryKey: ORDERS_QUERY_KEYS.QUOTE(uuid) })
      qc.invalidateQueries({ queryKey: ORDERS_QUERY_KEYS.ORDERS })
    },
  })
}

export function useDeleteQuote() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => quotesService.delete(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ORDERS_QUERY_KEYS.QUOTES })
    },
  })
}

// ── Orders ───────────────────────────────────────────────────────────────────

export function useOrders(params?: Record<string, string | number>) {
  return useQuery({
    queryKey: [...ORDERS_QUERY_KEYS.ORDERS, params],
    queryFn:  () => ordersService.list(params),
    staleTime: 60 * 1000,
  })
}

export function useOrder(uuid: string) {
  return useQuery({
    queryKey: ORDERS_QUERY_KEYS.ORDER(uuid),
    queryFn:  () => ordersService.get(uuid),
    enabled:  Boolean(uuid),
    staleTime: 60 * 1000,
  })
}

export function useOrderByNumber(number: string) {
  return useQuery({
    queryKey: ORDERS_QUERY_KEYS.ORDER_BY_NUMBER(number),
    queryFn:  () => ordersService.getByNumber(number),
    enabled:  Boolean(number),
    staleTime: 30 * 1000,
  })
}

export function useCreateOrder() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateOrderRequest) => ordersService.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ORDERS_QUERY_KEYS.ORDERS })
    },
  })
}

export function useUpdateOrderStatus() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({
      uuid,
      status,
      cancellationReason,
    }: {
      uuid: string
      status: string
      cancellationReason?: string
    }) => ordersService.updateStatus(uuid, status, cancellationReason),
    onSuccess: (_data: Order, { uuid }: { uuid: string; status: string; cancellationReason?: string }) => {
      qc.invalidateQueries({ queryKey: ORDERS_QUERY_KEYS.ORDERS })
      qc.invalidateQueries({ queryKey: ORDERS_QUERY_KEYS.ORDER(uuid) })
    },
  })
}

export function useDeleteOrder() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => ordersService.delete(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ORDERS_QUERY_KEYS.ORDERS })
    },
  })
}
