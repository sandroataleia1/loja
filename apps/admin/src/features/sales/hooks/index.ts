'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { salesService, type SessionFilters, type SaleFilters } from '@/services/sales.service'
import type { CreateCashRegisterRequest, UpdateCashRegisterRequest, OpenSessionRequest, CloseSessionRequest, UpdateFiscalSettingsRequest } from '@store/contracts'

// ── Query Keys ────────────────────────────────────────────────────────────────

export const SALES_KEYS = {
  REGISTERS:         ['cash-registers']                             as const,
  REGISTER:          (uuid: string) => ['cash-registers', uuid]    as const,
  SESSIONS:          (f?: SessionFilters) => ['cash-sessions', f]  as const,
  SESSION:           (uuid: string) => ['cash-sessions', uuid]     as const,
  SESSION_MOVEMENTS: (uuid: string) => ['session-movements', uuid] as const,
  SALES:             (f?: SaleFilters) => ['sales', f]             as const,
  SALE:              (uuid: string) => ['sales', uuid]             as const,
  FISCAL_SETTINGS:   ['fiscal-settings']                           as const,
  FISCAL_DOCS:       (f?: object) => ['fiscal-documents', f]       as const,
  TAX_PROFILES:      ['tax-profiles']                              as const,
}

// ── Cash Registers ────────────────────────────────────────────────────────────

export function useCashRegisters() {
  return useQuery({ queryKey: SALES_KEYS.REGISTERS, queryFn: () => salesService.getCashRegisters(), staleTime: 5 * 60_000 })
}

export function useCashRegister(uuid: string) {
  return useQuery({ queryKey: SALES_KEYS.REGISTER(uuid), queryFn: () => salesService.getCashRegister(uuid), enabled: Boolean(uuid) })
}

export function useCreateCashRegister() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateCashRegisterRequest) => salesService.createCashRegister(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: SALES_KEYS.REGISTERS }),
  })
}

export function useUpdateCashRegister(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: UpdateCashRegisterRequest) => salesService.updateCashRegister(uuid, data),
    onSuccess: () => { qc.invalidateQueries({ queryKey: SALES_KEYS.REGISTERS }); qc.invalidateQueries({ queryKey: SALES_KEYS.REGISTER(uuid) }) },
  })
}

export function useDeleteCashRegister() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => salesService.deleteCashRegister(uuid),
    onSuccess: () => qc.invalidateQueries({ queryKey: SALES_KEYS.REGISTERS }),
  })
}

// ── Sessions ──────────────────────────────────────────────────────────────────

export function useCashSessions(filters?: SessionFilters) {
  return useQuery({ queryKey: SALES_KEYS.SESSIONS(filters), queryFn: () => salesService.getSessions(filters), staleTime: 30_000 })
}

export function useCashSession(uuid: string) {
  return useQuery({ queryKey: SALES_KEYS.SESSION(uuid), queryFn: () => salesService.getSession(uuid), enabled: Boolean(uuid) })
}

export function useOpenSession() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: OpenSessionRequest) => salesService.openSession(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['cash-sessions'] }),
  })
}

export function useCloseSession(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CloseSessionRequest) => salesService.closeSession(uuid, data),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['cash-sessions'] }); qc.invalidateQueries({ queryKey: SALES_KEYS.SESSION(uuid) }) },
  })
}

export function useSessionMovements(uuid: string) {
  return useQuery({ queryKey: SALES_KEYS.SESSION_MOVEMENTS(uuid), queryFn: () => salesService.getMovements(uuid), enabled: Boolean(uuid), staleTime: 30_000 })
}

// ── Sales ─────────────────────────────────────────────────────────────────────

export function useSales(filters?: SaleFilters) {
  return useQuery({ queryKey: SALES_KEYS.SALES(filters), queryFn: () => salesService.getSales(filters), staleTime: 30_000 })
}

export function useSale(uuid: string) {
  return useQuery({ queryKey: SALES_KEYS.SALE(uuid), queryFn: () => salesService.getSale(uuid), enabled: Boolean(uuid) })
}

export function useCancelSale(uuid: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data?: { reason?: string }) => salesService.cancelSale(uuid, data),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['sales'] }); qc.invalidateQueries({ queryKey: SALES_KEYS.SALE(uuid) }) },
  })
}

// ── Fiscal ────────────────────────────────────────────────────────────────────

export function useFiscalSettings() {
  return useQuery({ queryKey: SALES_KEYS.FISCAL_SETTINGS, queryFn: () => salesService.getFiscalSettings(), staleTime: 10 * 60_000 })
}

export function useUpdateFiscalSettings() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: UpdateFiscalSettingsRequest) => salesService.updateFiscalSettings(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: SALES_KEYS.FISCAL_SETTINGS }),
  })
}

export function useFiscalDocuments(filters?: { status?: string; per_page?: number }) {
  return useQuery({ queryKey: SALES_KEYS.FISCAL_DOCS(filters), queryFn: () => salesService.getFiscalDocuments(filters), staleTime: 30_000 })
}

export function useTaxProfiles() {
  return useQuery({ queryKey: SALES_KEYS.TAX_PROFILES, queryFn: () => salesService.getTaxProfiles(), staleTime: 10 * 60_000 })
}
