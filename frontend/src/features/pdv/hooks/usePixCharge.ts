'use client'

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { pixService, type CreatePixChargeParams, type PixCharge } from '@/services/pix.service'

const PIX_KEYS = {
  charge: (uuid: string) => ['pix', 'charge', uuid] as const,
  publicInfo: ['pix', 'public-info'] as const,
}

/**
 * Create a PIX charge and start polling its status.
 * Returns the created charge UUID so polling can begin.
 */
export function useCreatePixCharge() {
  const qc = useQueryClient()
  return useMutation<PixCharge, Error, CreatePixChargeParams>({
    mutationFn: pixService.createCharge,
    onSuccess: (charge) => {
      qc.setQueryData(PIX_KEYS.charge(charge.uuid), charge)
    },
    onError: (err) => toast.error(`Erro ao gerar QR Code PIX: ${err.message}`),
  })
}

/**
 * Poll a PIX charge status every 2.5s.
 * Stops polling when status is 'paid', 'expired', or 'refunded'.
 * Call `onPaid` callback when payment is confirmed.
 */
export function usePixChargeStatus(
  chargeUuid: string | null,
  options?: { onPaid?: (charge: PixCharge) => void },
) {
  const qc = useQueryClient()

  return useQuery<PixCharge>({
    queryKey: PIX_KEYS.charge(chargeUuid ?? ''),
    queryFn:  () => pixService.getCharge(chargeUuid!),
    enabled:  !!chargeUuid,
    refetchInterval: (query) => {
      const status = query.state.data?.status
      if (status && status !== 'pending') return false
      return 2500
    },
    select: (charge) => {
      if (charge.status === 'paid' && options?.onPaid) {
        options.onPaid(charge)
      }
      return charge
    },
  })
}

/**
 * Simulate a payment (sandbox only) — marks the charge as paid.
 */
export function useSimulatePixPayment() {
  const qc = useQueryClient()
  return useMutation<PixCharge, Error, string>({
    mutationFn: pixService.simulatePayment,
    onSuccess: (charge) => {
      qc.setQueryData(PIX_KEYS.charge(charge.uuid), charge)
    },
  })
}

/**
 * Fetch the store's PIX public info (pix_key, has_qrcode_enabled).
 * Used by PixPaymentForm to auto-fill the static PIX key.
 */
export function usePixPublicInfo() {
  return useQuery({
    queryKey: PIX_KEYS.publicInfo,
    queryFn:  pixService.getPublicInfo,
    staleTime: 5 * 60 * 1000,
  })
}
