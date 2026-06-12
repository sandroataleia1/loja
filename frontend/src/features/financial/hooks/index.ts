'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { financialService } from '@/services/financial.service'
import type {
  FinancialEntryFilters, FinancialTransferFilters,
  CreateFinancialEntryRequest, PayFinancialEntryRequest,
  CreateFinancialAccountRequest, UpdateFinancialAccountRequest,
  CreateFinancialCategoryRequest, UpdateFinancialCategoryRequest,
  CreateFinancialTransferRequest,
} from '@/types/financial'

// ── Query Keys ────────────────────────────────────────────────────────────────

export const FINANCIAL_KEYS = {
  ENTRIES:    (f?: FinancialEntryFilters) => ['financial-entries', f]   as const,
  ENTRY:      (uuid: string)              => ['financial-entries', uuid] as const,
  ACCOUNTS:   ['financial-accounts']                                     as const,
  ACCOUNT:    (uuid: string)              => ['financial-accounts', uuid] as const,
  CATEGORIES: ['financial-categories']                                   as const,
  TRANSFERS:  (f?: FinancialTransferFilters) => ['financial-transfers', f] as const,
}

// ── Entries ───────────────────────────────────────────────────────────────────

export function useFinancialEntries(filters?: FinancialEntryFilters) {
  return useQuery({
    queryKey: FINANCIAL_KEYS.ENTRIES(filters),
    queryFn:  () => financialService.getEntries(filters),
    staleTime: 30_000,
  })
}

export function useFinancialEntry(uuid: string) {
  return useQuery({
    queryKey: FINANCIAL_KEYS.ENTRY(uuid),
    queryFn:  () => financialService.getEntry(uuid),
    enabled:  Boolean(uuid),
  })
}

export function useCreateEntry() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateFinancialEntryRequest) => financialService.createEntry(data),
    onSuccess:  () => qc.invalidateQueries({ queryKey: ['financial-entries'] }),
  })
}

export function usePayEntry(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: PayFinancialEntryRequest) => financialService.payEntry(uuid, data),
    onSuccess:  () => {
      qc.invalidateQueries({ queryKey: ['financial-entries'] })
      qc.invalidateQueries({ queryKey: FINANCIAL_KEYS.ENTRY(uuid) })
      qc.invalidateQueries({ queryKey: FINANCIAL_KEYS.ACCOUNTS })
    },
  })
}

export function useCancelEntry(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (reason?: string) => financialService.cancelEntry(uuid, reason),
    onSuccess:  () => {
      qc.invalidateQueries({ queryKey: ['financial-entries'] })
      qc.invalidateQueries({ queryKey: FINANCIAL_KEYS.ENTRY(uuid) })
    },
  })
}

// ── Accounts ──────────────────────────────────────────────────────────────────

export function useFinancialAccounts() {
  return useQuery({
    queryKey: FINANCIAL_KEYS.ACCOUNTS,
    queryFn:  () => financialService.getAccounts(),
    staleTime: 5 * 60_000,
  })
}

export function useCreateAccount() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateFinancialAccountRequest) => financialService.createAccount(data),
    onSuccess:  () => qc.invalidateQueries({ queryKey: FINANCIAL_KEYS.ACCOUNTS }),
  })
}

export function useUpdateAccount() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: UpdateFinancialAccountRequest }) => financialService.updateAccount(uuid, data),
    onSuccess:  (_, { uuid }) => {
      qc.invalidateQueries({ queryKey: FINANCIAL_KEYS.ACCOUNTS })
      qc.invalidateQueries({ queryKey: FINANCIAL_KEYS.ACCOUNT(uuid) })
    },
  })
}

export function useDeleteAccount() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => financialService.deleteAccount(uuid),
    onSuccess:  () => qc.invalidateQueries({ queryKey: FINANCIAL_KEYS.ACCOUNTS }),
  })
}

// ── Categories ────────────────────────────────────────────────────────────────

export function useFinancialCategories() {
  return useQuery({
    queryKey: FINANCIAL_KEYS.CATEGORIES,
    queryFn:  () => financialService.getCategories(),
    staleTime: 5 * 60_000,
  })
}

export function useCreateCategory() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateFinancialCategoryRequest) => financialService.createCategory(data),
    onSuccess:  () => qc.invalidateQueries({ queryKey: FINANCIAL_KEYS.CATEGORIES }),
  })
}

export function useUpdateCategory() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: UpdateFinancialCategoryRequest }) => financialService.updateCategory(uuid, data),
    onSuccess:  () => qc.invalidateQueries({ queryKey: FINANCIAL_KEYS.CATEGORIES }),
  })
}

export function useDeleteCategory() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => financialService.deleteCategory(uuid),
    onSuccess:  () => qc.invalidateQueries({ queryKey: FINANCIAL_KEYS.CATEGORIES }),
  })
}

// ── Transfers ─────────────────────────────────────────────────────────────────

export function useFinancialTransfers(filters?: FinancialTransferFilters) {
  return useQuery({
    queryKey: FINANCIAL_KEYS.TRANSFERS(filters),
    queryFn:  () => financialService.getTransfers(filters),
    staleTime: 30_000,
  })
}

export function useCreateTransfer() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateFinancialTransferRequest) => financialService.createTransfer(data),
    onSuccess:  () => {
      qc.invalidateQueries({ queryKey: ['financial-transfers'] })
      qc.invalidateQueries({ queryKey: FINANCIAL_KEYS.ACCOUNTS })
    },
  })
}
