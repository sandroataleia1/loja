'use client'

import { useEffect, useState } from 'react'
import { ShoppingCart, Trash2 } from 'lucide-react'
import { usePdvCartStore } from '@/features/pdv/stores/pdvCartStore'
import { CartItemRow } from './CartItemRow'
import { CartTotals } from './CartTotals'
import { CustomerSearchBar } from './CustomerSearchBar'
import { DiscountDialog } from './DiscountDialog'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

interface Props {
  onCheckout: () => void
}

export function CartPanel({ onCheckout }: Props) {
  const items     = usePdvCartStore((s) => s.items)
  const total     = usePdvCartStore((s) => s.totalCents())
  const itemCount = usePdvCartStore((s) => s.itemCount())
  const clear     = usePdvCartStore((s) => s.clear)

  const [discountOpen, setDiscountOpen] = useState(false)

  // F4 → checkout; F9 → cancelar; F10 → desconto
  useEffect(() => {
    function onKey(e: KeyboardEvent) {
      if (e.key === 'F4')  { e.preventDefault(); if (itemCount > 0) onCheckout() }
      if (e.key === 'F9')  { e.preventDefault(); if (itemCount > 0 && confirm('Cancelar a venda?')) clear() }
      if (e.key === 'F10') { e.preventDefault(); if (itemCount > 0) setDiscountOpen(true) }
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [itemCount, onCheckout, clear])

  return (
    <div className="flex flex-col w-96 shrink-0 bg-card border-l overflow-hidden">
      {/* Cliente */}
      <CustomerSearchBar />

      {/* Lista de itens */}
      <div className="flex-1 overflow-y-auto px-4 py-2">
        {items.length === 0 ? (
          <div className="flex flex-col items-center justify-center h-full text-muted-foreground gap-2 py-12 opacity-60">
            <ShoppingCart className="w-8 h-8" />
            <p className="text-xs">Carrinho vazio</p>
            <p className="text-[10px]">F1 para buscar produto</p>
          </div>
        ) : (
          <div>
            {items.map((item, i) => (
              <CartItemRow key={item.id} item={item} index={i} />
            ))}
          </div>
        )}
      </div>

      {/* Totais */}
      <CartTotals onOpenDiscount={() => setDiscountOpen(true)} />

      {/* Ações */}
      <div className="px-4 pb-3 flex gap-2">
        {items.length > 0 && (
          <button
            onClick={() => { if (confirm('Cancelar a venda?')) clear() }}
            className="flex items-center justify-center w-11 h-11 rounded-xl border border-border hover:border-destructive/50 hover:text-destructive transition-colors shrink-0"
            title="F9 — Cancelar venda"
          >
            <Trash2 className="w-4 h-4" />
          </button>
        )}
        <button
          onClick={onCheckout}
          disabled={itemCount === 0}
          className="flex-1 h-11 rounded-xl bg-primary text-primary-foreground font-bold text-sm disabled:opacity-40 disabled:cursor-not-allowed transition-opacity hover:bg-primary/90 active:scale-[0.98]"
        >
          F4 — Cobrar {fmtBRL(total)}
        </button>
      </div>

      <DiscountDialog open={discountOpen} onClose={() => setDiscountOpen(false)} />
    </div>
  )
}
