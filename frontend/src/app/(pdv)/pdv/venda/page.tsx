'use client'

import { useState } from 'react'
import { usePdvSession }     from '@/features/pdv/hooks/usePdvSession'
import { usePdvCartStore }   from '@/features/pdv/stores/pdvCartStore'
import { usePdvCategories, useProductSearch } from '@/features/pdv/hooks'
import { useFullscreen, usePdvF11 } from '@/features/pdv/hooks/usePdvShortcuts'
import { PdvTopbar }         from '@/features/pdv/components/layout/PdvTopbar'
import { PdvKeybar }         from '@/features/pdv/components/layout/PdvKeybar'
import { ProductGrid }       from '@/features/pdv/components/product/ProductGrid'
import { CartPanel }         from '@/features/pdv/components/cart/CartPanel'
import { PaymentModal }      from '@/features/pdv/components/payment/PaymentModal'
import { ReceiptModal }      from '@/features/pdv/components/receipt/ReceiptModal'
import { OrderSearchModal }  from '@/features/pdv/components/cart/OrderSearchModal'
import type { Sale }         from '@/types/shared-types'
import type { PendingPayment, ReceiptData } from '@/features/pdv/types'

// Prefetch: warm up categories and initial product page when the PDV loads
function useCatalogPrefetch() {
  usePdvCategories()               // staleTime 5min — cached after first call
  useProductSearch('', undefined)  // loads first page without query (featured/all products)
}

export default function VendaPage() {
  const { session }   = usePdvSession()
  const items         = usePdvCartStore((s) => s.items)
  const clearCart     = usePdvCartStore((s) => s.clear)

  const [checkoutOpen,    setCheckoutOpen]    = useState(false)
  const [receiptData,     setReceiptData]     = useState<ReceiptData | null>(null)
  const [orderSearchOpen, setOrderSearchOpen] = useState(false)

  // 7.4 — auto-fullscreen when PDV opens; 7.1 — F11 toggle
  useFullscreen()
  usePdvF11()

  // 7.5 — prefetch catálogo assim que a página monta
  useCatalogPrefetch()

  if (!session) return null

  function handleSaleSuccess(sale: Sale, payments: PendingPayment[]) {
    setCheckoutOpen(false)
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
        <CartPanel
          onCheckout={() => setCheckoutOpen(true)}
          onSearchOrder={() => setOrderSearchOpen(true)}
        />
      </div>

      <PdvKeybar onSearchOrder={() => setOrderSearchOpen(true)} />

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

      <OrderSearchModal
        open={orderSearchOpen}
        onClose={() => setOrderSearchOpen(false)}
      />
    </div>
  )
}
