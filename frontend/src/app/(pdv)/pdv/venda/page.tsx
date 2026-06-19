'use client'

import { useState } from 'react'
import { usePdvSession }     from '@/features/pdv/hooks/usePdvSession'
import { usePdvCartStore }   from '@/features/pdv/stores/pdvCartStore'
import { PdvTopbar }         from '@/features/pdv/components/layout/PdvTopbar'
import { PdvKeybar }         from '@/features/pdv/components/layout/PdvKeybar'
import { ProductGrid }       from '@/features/pdv/components/product/ProductGrid'
import { CartPanel }         from '@/features/pdv/components/cart/CartPanel'
import { PaymentModal }      from '@/features/pdv/components/payment/PaymentModal'
import { ReceiptModal }      from '@/features/pdv/components/receipt/ReceiptModal'
import type { Sale }         from '@/types/shared-types'
import type { PendingPayment, ReceiptData } from '@/features/pdv/types'

export default function VendaPage() {
  const { session }   = usePdvSession()
  const items         = usePdvCartStore((s) => s.items)
  const clearCart     = usePdvCartStore((s) => s.clear)

  const [checkoutOpen, setCheckoutOpen] = useState(false)
  const [receiptData,  setReceiptData]  = useState<ReceiptData | null>(null)

  if (!session) return null

  function handleSaleSuccess(sale: Sale, payments: PendingPayment[]) {
    setCheckoutOpen(false)
    // Captura itens do carrinho antes de limpar
    setReceiptData({ sale, items: [...items], payments })
  }

  function handleReceiptClose() {
    setReceiptData(null)
    clearCart()
  }

  return (
    <div className="flex flex-col h-screen bg-background overflow-hidden">
      <PdvTopbar />

      <div className="flex flex-1 overflow-hidden">
        <div className="flex flex-col flex-1 overflow-hidden">
          <ProductGrid />
        </div>
        <CartPanel onCheckout={() => setCheckoutOpen(true)} />
      </div>

      <PdvKeybar />

      {checkoutOpen && (
        <PaymentModal
          onClose={() => setCheckoutOpen(false)}
          onSuccess={handleSaleSuccess}
        />
      )}

      {receiptData && (
        <ReceiptModal
          data={receiptData}
          onClose={handleReceiptClose}
        />
      )}
    </div>
  )
}
