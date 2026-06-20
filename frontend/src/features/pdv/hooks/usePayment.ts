'use client'

import { useMutation } from '@tanstack/react-query'
import { toast } from 'sonner'
import { salesService } from '@/services/sales.service'
import { usePdvCartStore } from '../stores/pdvCartStore'
import { usePdvSessionStore } from '../stores/pdvSessionStore'
import type { PendingPayment } from '../types'
import type { Sale } from '@/types/shared-types'

export function usePayment() {
  const items         = usePdvCartStore((s) => s.items)
  const customerUuid  = usePdvCartStore((s) => s.customerUuid)
  const discountCents = usePdvCartStore((s) => s.discountCents)
  const notes         = usePdvCartStore((s) => s.notes)
  const session       = usePdvSessionStore((s) => s.session)

  return useMutation<Sale, Error, PendingPayment[]>({
    onError: (err) => toast.error(`Erro ao registrar venda: ${err.message}`),
    mutationFn: async (pendingPayments) => {
      const sale = await salesService.createSale({
        store_id:    session!.storeUuid,
        session_id:  session!.sessionUuid,
        customer_id: customerUuid,
        notes:       notes || null,
        items: items.map((item) => ({
          variant_id:            item.variantUuid,
          sku_snapshot:          item.sku,
          name_snapshot:         item.name,
          quantity:              item.quantity,
          unit_price_cents:      item.unitPriceCents,
          discount_amount_cents: item.discountCents,
        })),
        payments: pendingPayments.map((p) => ({
          method:       p.method,
          amount_cents: p.amountCents,
          ...(p.reference ? { external_reference: p.reference } : {}),
          ...((p.installments && p.installments > 1) || p.nsu || p.authCode || p.cardBrand ? {
            metadata: {
              ...(p.installments && p.installments > 1 ? { installments: p.installments } : {}),
              ...(p.nsu       ? { nsu:               p.nsu }       : {}),
              ...(p.authCode  ? { authorization_code: p.authCode }  : {}),
              ...(p.cardBrand ? { card_brand:         p.cardBrand } : {}),
            },
          } : {}),
        })),
      })
      const completed = await salesService.completeSale(sale.uuid)
      return completed
    },
  })
}
