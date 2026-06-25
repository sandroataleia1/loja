'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import {
  carriersService,
  type CarrierFilters,
  type CreateCarrierPayload,
  type CarrierAddressPayload,
  type CarrierContactPayload,
} from '@/services/carriers.service'

const KEYS = {
  all:     ['carriers']                             as const,
  list:    (f?: CarrierFilters) => ['carriers', 'list', f] as const,
  detail:  (uuid: string)       => ['carriers', uuid]      as const,
}

export function useCarriers(filters?: CarrierFilters) {
  return useQuery({
    queryKey: KEYS.list(filters),
    queryFn:  () => carriersService.getCarriers(filters),
    staleTime: 60_000,
  })
}

export function useAllCarriers() {
  return useQuery({
    queryKey: KEYS.list({ active: true, per_page: 200 }),
    queryFn:  () => carriersService.getCarriers({ active: true, per_page: 200 }),
    staleTime: 5 * 60_000,
    select:   (res) => res.data,
  })
}

export function useCarrier(uuid: string) {
  return useQuery({
    queryKey: KEYS.detail(uuid),
    queryFn:  () => carriersService.getCarrier(uuid),
    enabled:  !!uuid,
    staleTime: 30_000,
  })
}

export function useCreateCarrier() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (data: CreateCarrierPayload) => carriersService.createCarrier(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEYS.all })
      toast.success('Transportadora criada.')
    },
    onError: (err: Error) => toast.error(`Erro ao criar transportadora: ${err.message}`),
  })
}

export function useUpdateCarrier() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Partial<CreateCarrierPayload> }) =>
      carriersService.updateCarrier(uuid, data),
    onSuccess: (carrier) => {
      qc.invalidateQueries({ queryKey: KEYS.detail(carrier.uuid) })
      qc.invalidateQueries({ queryKey: KEYS.all })
      toast.success('Transportadora atualizada.')
    },
    onError: (err: Error) => toast.error(`Erro ao atualizar transportadora: ${err.message}`),
  })
}

export function useDeleteCarrier() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (uuid: string) => carriersService.deleteCarrier(uuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEYS.all })
      toast.success('Transportadora removida.')
    },
    onError: (err: Error) => toast.error(`Erro ao remover transportadora: ${err.message}`),
  })
}

export function useCreateCarrierAddress() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ carrierUuid, data }: { carrierUuid: string; data: CarrierAddressPayload }) =>
      carriersService.createCarrierAddress(carrierUuid, data),
    onSuccess: (_address, { carrierUuid }) => {
      qc.invalidateQueries({ queryKey: KEYS.detail(carrierUuid) })
      toast.success('Endereço adicionado.')
    },
    onError: (err: Error) => toast.error(`Erro ao adicionar endereço: ${err.message}`),
  })
}

export function useDeleteCarrierAddress() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ carrierUuid, addressUuid }: { carrierUuid: string; addressUuid: string }) =>
      carriersService.deleteCarrierAddress(carrierUuid, addressUuid),
    onSuccess: (_v, { carrierUuid }) => {
      qc.invalidateQueries({ queryKey: KEYS.detail(carrierUuid) })
      toast.success('Endereço removido.')
    },
    onError: (err: Error) => toast.error(`Erro ao remover endereço: ${err.message}`),
  })
}

export function useCreateCarrierContact() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ carrierUuid, data }: { carrierUuid: string; data: CarrierContactPayload }) =>
      carriersService.createCarrierContact(carrierUuid, data),
    onSuccess: (_contact, { carrierUuid }) => {
      qc.invalidateQueries({ queryKey: KEYS.detail(carrierUuid) })
      toast.success('Contato adicionado.')
    },
    onError: (err: Error) => toast.error(`Erro ao adicionar contato: ${err.message}`),
  })
}

export function useDeleteCarrierContact() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ carrierUuid, contactUuid }: { carrierUuid: string; contactUuid: string }) =>
      carriersService.deleteCarrierContact(carrierUuid, contactUuid),
    onSuccess: (_v, { carrierUuid }) => {
      qc.invalidateQueries({ queryKey: KEYS.detail(carrierUuid) })
      toast.success('Contato removido.')
    },
    onError: (err: Error) => toast.error(`Erro ao remover contato: ${err.message}`),
  })
}
