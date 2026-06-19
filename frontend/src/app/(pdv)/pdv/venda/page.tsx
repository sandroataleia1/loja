'use client'

import { useState } from 'react'
import { usePdvSession } from '@/features/pdv/hooks/usePdvSession'
import { PdvTopbar }    from '@/features/pdv/components/layout/PdvTopbar'
import { PdvKeybar }    from '@/features/pdv/components/layout/PdvKeybar'
import { ProductGrid }  from '@/features/pdv/components/product/ProductGrid'
import { CartPanel }    from '@/features/pdv/components/cart/CartPanel'

export default function VendaPage() {
  const { session } = usePdvSession()
  const [checkoutOpen, setCheckoutOpen] = useState(false)

  if (!session) return null

  return (
    <div className="flex flex-col h-screen bg-background overflow-hidden">
      <PdvTopbar />

      <div className="flex flex-1 overflow-hidden">
        {/* Painel esquerdo: busca e grade de produtos */}
        <div className="flex flex-col flex-1 overflow-hidden">
          <ProductGrid />
        </div>

        {/* Painel direito: carrinho */}
        <CartPanel onCheckout={() => setCheckoutOpen(true)} />
      </div>

      <PdvKeybar />

      {/* Modal de pagamento virá na Etapa 3 */}
      {checkoutOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="bg-card border rounded-2xl p-6 shadow-2xl text-center space-y-3">
            <p className="font-semibold">Modal de Pagamento</p>
            <p className="text-sm text-muted-foreground">Em construção — Etapa 3</p>
            <button
              onClick={() => setCheckoutOpen(false)}
              className="px-4 py-2 rounded-lg bg-primary text-primary-foreground text-sm"
            >
              Fechar
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
