'use client'

import { useEffect, useRef, useState } from 'react'
import { Minus, Plus, X } from 'lucide-react'
import { cn } from '@/lib/utils'
import { usePdvCartStore, type PdvCartItem } from '@/features/pdv/stores/pdvCartStore'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

interface Props {
  item:  PdvCartItem
  index: number
}

export function CartItemRow({ item, index }: Props) {
  const updateQty  = usePdvCartStore((s) => s.updateQty)
  const removeItem = usePdvCartStore((s) => s.removeItem)

  const [highlight, setHighlight] = useState(true)
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null)

  // Brief green highlight on mount (item just added)
  useEffect(() => {
    timerRef.current = setTimeout(() => setHighlight(false), 500)
    return () => { if (timerRef.current) clearTimeout(timerRef.current) }
  }, [])

  const lineTotal = item.unitPriceCents * item.quantity - item.discountCents

  return (
    <div className={cn(
      'flex items-start gap-2 py-2 border-b last:border-0 group rounded-lg px-1 -mx-1 transition-colors duration-300',
      highlight ? 'bg-green-50 dark:bg-green-950/30' : 'bg-transparent',
    )}>
      {/* Sequência */}
      <span className="text-[10px] text-muted-foreground/50 w-4 shrink-0 mt-1 tabular-nums">
        {index + 1}
      </span>

      {/* Info */}
      <div className="flex-1 min-w-0">
        <p className="text-xs font-medium leading-tight truncate">{item.name}</p>
        <p className="text-[10px] text-muted-foreground">{item.sku}</p>

        {/* Qty controls */}
        <div className="flex items-center gap-1.5 mt-1.5">
          <button
            onClick={() => updateQty(item.id, item.quantity - 1)}
            className="flex items-center justify-center w-5 h-5 rounded border border-border hover:bg-accent transition-colors"
          >
            <Minus className="w-2.5 h-2.5" />
          </button>
          <span className="text-xs font-semibold tabular-nums w-6 text-center">
            {item.quantity}
          </span>
          <button
            onClick={() => updateQty(item.id, item.quantity + 1)}
            className="flex items-center justify-center w-5 h-5 rounded border border-border hover:bg-accent transition-colors"
          >
            <Plus className="w-2.5 h-2.5" />
          </button>
          <span className="text-[10px] text-muted-foreground ml-1">
            × {fmtBRL(item.unitPriceCents)}
          </span>
        </div>
      </div>

      {/* Total + remove */}
      <div className="flex flex-col items-end gap-1 shrink-0">
        <button
          onClick={() => removeItem(item.id)}
          className="opacity-0 group-hover:opacity-100 text-muted-foreground hover:text-destructive transition-all"
        >
          <X className="w-3.5 h-3.5" />
        </button>
        <span className="text-xs font-bold tabular-nums">{fmtBRL(lineTotal)}</span>
      </div>
    </div>
  )
}
