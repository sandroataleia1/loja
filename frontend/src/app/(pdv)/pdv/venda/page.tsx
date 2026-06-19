'use client'

import { useState } from 'react'
import { usePdvSession } from '@/features/pdv/hooks/usePdvSession'
import { PdvTopbar }     from '@/features/pdv/components/layout/PdvTopbar'
import { PdvKeybar }     from '@/features/pdv/components/layout/PdvKeybar'
import { ProductGrid }   from '@/features/pdv/components/product/ProductGrid'
import { CartPanel }     from '@/features/pdv/components/cart/CartPanel'
import { PaymentModal }  from '@/features/pdv/components/payment/PaymentModal'
import type { Sale }     from '@/types/shared-types'

export default function VendaPage() {
  const { session }       = usePdvSession()
  const [checkoutOpen, setCheckoutOpen] = useState(false)

  if (!session) return null

  function handleSaleSuccess(_sale: Sale) {
    setCheckoutOpen(false)
    // TODO Etapa 4: abrir modal de cupom fiscal
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
    </div>
  )
}
