'use client'

import { X, ShoppingCart } from 'lucide-react'
import { cn } from '@/lib/utils'
import type { Product, ProductVariant } from '@/types/shared-types'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

interface Props {
  product:   Product
  onSelect:  (variant: ProductVariant) => void
  onClose:   () => void
}

export function VariantPicker({ product, onSelect, onClose }: Props) {
  const active = (product.variants ?? []).filter((v) => v.is_active)

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
      <div className="bg-card border rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        {/* Header */}
        <div className="flex items-center justify-between px-4 py-3 border-b">
          <div>
            <p className="font-semibold text-sm">{product.name}</p>
            <p className="text-xs text-muted-foreground">Selecione uma variante</p>
          </div>
          <button
            onClick={onClose}
            className="text-muted-foreground hover:text-foreground transition-colors p-1"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Variants */}
        <div className="divide-y max-h-80 overflow-y-auto">
          {active.length === 0 ? (
            <p className="text-center text-muted-foreground text-sm py-8">
              Nenhuma variante disponível.
            </p>
          ) : active.map((v) => {
            const attrs = v.grid_combination
              ?.map((g) => g.attr_value)
              .join(' / ')

            return (
              <button
                key={v.uuid}
                onClick={() => { onSelect(v); onClose() }}
                className="w-full flex items-center justify-between px-4 py-3 hover:bg-accent transition-colors text-left group"
              >
                <div>
                  <p className="text-sm font-medium">
                    {attrs ?? v.name ?? v.sku}
                  </p>
                  <p className="text-xs text-muted-foreground">{v.sku}</p>
                </div>
                <div className="flex items-center gap-3">
                  <span className="text-sm font-bold text-primary">
                    {fmtBRL(v.price_cents)}
                  </span>
                  <ShoppingCart className="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
                </div>
              </button>
            )
          })}
        </div>
      </div>
    </div>
  )
}
