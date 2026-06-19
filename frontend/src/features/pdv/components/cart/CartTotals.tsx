'use client'

import { usePdvCartStore } from '@/features/pdv/stores/pdvCartStore'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

interface Props {
  onOpenDiscount?: () => void
}

export function CartTotals({ onOpenDiscount }: Props) {
  const subtotal = usePdvCartStore((s) => s.subtotalCents())
  const discount = usePdvCartStore((s) => s.discountCents)
  const total    = usePdvCartStore((s) => s.totalCents())

  return (
    <div className="px-4 py-3 space-y-1 text-sm border-t">
      {/* Subtotal */}
      <div className="flex justify-between text-muted-foreground text-xs">
        <span>Subtotal</span>
        <span className="tabular-nums">{fmtBRL(subtotal)}</span>
      </div>

      {/* Desconto */}
      <div className="flex justify-between text-xs">
        <button
          onClick={onOpenDiscount}
          className="text-muted-foreground hover:text-primary transition-colors"
          title="F10 — Aplicar desconto"
        >
          Desconto {discount > 0 ? '✎' : '+ Add'}
        </button>
        <span className={`tabular-nums ${discount > 0 ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'}`}>
          {discount > 0 ? `- ${fmtBRL(discount)}` : fmtBRL(0)}
        </span>
      </div>

      {/* Total */}
      <div className="flex justify-between font-bold text-base pt-1 border-t">
        <span>Total</span>
        <span className="text-primary tabular-nums">{fmtBRL(total)}</span>
      </div>
    </div>
  )
}
