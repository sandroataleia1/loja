'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { customerService, type CustomerFilters } from '@/services/customer.service'
import type { CreateCustomerRequest, UpdateCustomerRequest, CreateCustomerTagRequest } from '@store/contracts'

// ── Query Keys ───────────────────────────────────────────────────────────────

export const CUSTOMER_QUERY_KEYS = {
  CUSTOMERS:     ['customers']                       as const,
  CUSTOMER:      (uuid: string) => ['customers', uuid] as const,
  CUSTOMER_TAGS: ['customer-tags']                   as const,
}

// ── Customers ────────────────────────────────────────────────────────────────

export function useCustomers(filters?: CustomerFilters) {
  return useQuery({
    queryKey: [...CUSTOMER_QUERY_KEYS.CUSTOMERS, filters],
    queryFn:  () => customerService.getCustomers(filters),
    staleTime: 2 * 60 * 1000,
  })
}

export function useCustomer(uuid: string) {
  return useQuery({
    queryKey: CUSTOMER_QUERY_KEYS.CUSTOMER(uuid),
    queryFn:  () => customerService.getCustomer(uuid),
    enabled:  Boolean(uuid),
    staleTime: 2 * 60 * 1000,
  })
}

export function useCreateCustomer() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateCustomerRequest) => customerService.createCustomer(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CUSTOMER_QUERY_KEYS.CUSTOMERS })
    },
  })
}

export function useUpdateCustomer(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: UpdateCustomerRequest) => customerService.updateCustomer(uuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CUSTOMER_QUERY_KEYS.CUSTOMERS })
      qc.invalidateQueries({ queryKey: CUSTOMER_QUERY_KEYS.CUSTOMER(uuid) })
    },
  })
}

export function useDeleteCustomer() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => customerService.deleteCustomer(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CUSTOMER_QUERY_KEYS.CUSTOMERS })
    },
  })
}

// ── Customer Tags ────────────────────────────────────────────────────────────

export function useCustomerTags() {
  return useQuery({
    queryKey: CUSTOMER_QUERY_KEYS.CUSTOMER_TAGS,
    queryFn:  () => customerService.getTags(),
    staleTime: 5 * 60 * 1000,
  })
}

export function useCreateTag() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateCustomerTagRequest) => customerService.createTag(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CUSTOMER_QUERY_KEYS.CUSTOMER_TAGS })
    },
  })
}

export function useAttachTag(customerUuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (tagId: string) => customerService.attachTag(customerUuid, tagId),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CUSTOMER_QUERY_KEYS.CUSTOMER(customerUuid) })
    },
  })
}

export function useDetachTag(customerUuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (tagUuid: string) => customerService.detachTag(customerUuid, tagUuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: CUSTOMER_QUERY_KEYS.CUSTOMER(customerUuid) })
    },
  })
}
