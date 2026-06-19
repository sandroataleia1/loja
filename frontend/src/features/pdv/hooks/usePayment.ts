'use client'

import { useMutation } from '@tanstack/react-query'
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
    mutationFn: async (pendingPayments) => {
      const sale = await salesService.createSale({
        session_id:           session!.sessionUuid,
        customer_id:          customerUuid,
        discount_total_cents: discountCents,
        notes:                notes || null,
        items: items.map((item) => ({
          product_uuid:          item.productUuid,
          variant_uuid:          item.variantUuid,
          product_name:          item.name,
          product_sku:           item.sku,
          quantity:              item.quantity,
          unit_price_cents:      item.unitPriceCents,
          discount_amount_cents: item.discountCents,
        })),
        payments: pendingPayments.map((p) => ({
          method:       p.method,
          amount_cents: p.amountCents,
          ...(p.installments && p.installments > 1 ? { installments: p.installments } : {}),
          ...(p.reference ? { reference: p.reference } : {}),
          ...((p.nsu || p.authCode || p.cardBrand) ? {
            metadata: {
              ...(p.nsu      ? { nsu:               p.nsu }      : {}),
              ...(p.authCode ? { authorization_code: p.authCode } : {}),
              ...(p.cardBrand? { card_brand:         p.cardBrand }: {}),
            },
          } : {}),
        })),
      })
      const completed = await salesService.completeSale(sale.uuid)
      return completed
    },
  })
}
