'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import {
  partnersService,
  type PartnerFilters,
  type CreatePartnerPayload,
  type PartnerContactPayload,
} from '@/services/partners.service'

const KEYS = {
  all:    ['partners']                               as const,
  list:   (f?: PartnerFilters) => ['partners', 'list', f] as const,
  detail: (uuid: string)       => ['partners', uuid]      as const,
}

export function usePartners(filters?: PartnerFilters) {
  return useQuery({
    queryKey: KEYS.list(filters),
    queryFn:  () => partnersService.getPartners(filters),
    staleTime: 60_000,
  })
}

export function usePartner(uuid: string) {
  return useQuery({
    queryKey: KEYS.detail(uuid),
    queryFn:  () => partnersService.getPartner(uuid),
    enabled:  !!uuid,
    staleTime: 30_000,
  })
}

export function useCreatePartner() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreatePartnerPayload) => partnersService.createPartner(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEYS.all })
      toast.success('Parceiro criado.')
    },
    onError: (err: Error) => toast.error(`Erro ao criar parceiro: ${err.message}`),
  })
}

export function useUpdatePartner() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Partial<CreatePartnerPayload> }) =>
      partnersService.updatePartner(uuid, data),
    onSuccess: (partner) => {
      qc.invalidateQueries({ queryKey: KEYS.detail(partner.uuid) })
      qc.invalidateQueries({ queryKey: KEYS.all })
      toast.success('Parceiro atualizado.')
    },
    onError: (err: Error) => toast.error(`Erro ao atualizar parceiro: ${err.message}`),
  })
}

export function useDeletePartner() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => partnersService.deletePartner(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEYS.all })
      toast.success('Parceiro removido.')
    },
    onError: (err: Error) => toast.error(`Erro ao remover parceiro: ${err.message}`),
  })
}

export function useCreatePartnerContact() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ partnerUuid, data }: { partnerUuid: string; data: PartnerContactPayload }) =>
      partnersService.createPartnerContact(partnerUuid, data),
    onSuccess: (_c, { partnerUuid }) => {
      qc.invalidateQueries({ queryKey: KEYS.detail(partnerUuid) })
      toast.success('Contato adicionado.')
    },
    onError: (err: Error) => toast.error(`Erro ao adicionar contato: ${err.message}`),
  })
}

export function useDeletePartnerContact() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ partnerUuid, contactUuid }: { partnerUuid: string; contactUuid: string }) =>
      partnersService.deletePartnerContact(partnerUuid, contactUuid),
    onSuccess: (_v, { partnerUuid }) => {
      qc.invalidateQueries({ queryKey: KEYS.detail(partnerUuid) })
      toast.success('Contato removido.')
    },
    onError: (err: Error) => toast.error(`Erro ao remover contato: ${err.message}`),
  })
}
