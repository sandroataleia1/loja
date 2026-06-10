'use client'

import { Skeleton } from '@/components/ui/skeleton'
import { cn } from '@/lib/utils'
import { StockBadge } from './stock-badge'
import type { InventoryBalance } from '@store/shared-types'

interface InventoryTableProps {
  balances:  InventoryBalance[]
  isLoading: boolean
}

function TableHead() {
  return (
    <tr className="border-b bg-muted/50 text-left">
      <th className="px-4 py-3 font-medium text-muted-foreground">Produto / Variante</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">SKU</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Loja</th>
      <th className="px-4 py-3 font-medium text-muted-foreground text-right">Atual</th>
      <th className="px-4 py-3 font-medium text-muted-foreground text-right">Reservado</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Disponível</th>
    </tr>
  )
}

export function InventoryTable({ balances, isLoading }: InventoryTableProps) {
  if (isLoading) {
    return (
      <div className="rounded-md border overflow-hidden">
        <table className="w-full text-sm">
          <thead><TableHead /></thead>
          <tbody>
            {Array.from({ length: 5 }).map((_, i) => (
              <tr key={i} className="border-b last:border-0">
                {Array.from({ length: 6 }).map((__, j) => (
                  <td key={j} className="px-4 py-3">
                    <Skeleton className="h-4 w-full" />
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    )
  }

  if (balances.length === 0) {
    return (
      <div className="rounded-md border">
        <table className="w-full text-sm">
          <thead><TableHead /></thead>
          <tbody>
            <tr>
              <td colSpan={6} className="px-4 py-10 text-center text-muted-foreground">
                Nenhum item encontrado.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    )
  }

  return (
    <div className="rounded-md border overflow-x-auto">
      <table className="w-full text-sm">
        <thead><TableHead /></thead>
        <tbody>
          {balances.map((balance) => {
            const available = balance.available_quantity
            const isLow = available <= 3

            return (
              <tr
                key={balance.uuid}
                className={cn(
                  'border-b last:border-0 hover:bg-muted/50 transition-colors',
                  isLow && 'bg-yellow-50/60 dark:bg-yellow-950/20',
                )}
              >
                <td className="px-4 py-3">
                  {balance.variant?.product ? (
                    <div>
                      <div className="font-medium">{balance.variant.product.name}</div>
                      {balance.variant.name && (
                        <div className="text-xs text-muted-foreground">{balance.variant.name}</div>
                      )}
                    </div>
                  ) : (
                    <span className="text-muted-foreground">—</span>
                  )}
                </td>
                <td className="px-4 py-3 font-mono text-xs text-muted-foreground">
                  {balance.variant?.sku ?? '—'}
                </td>
                <td className="px-4 py-3 text-muted-foreground">
                  {balance.store?.name ?? balance.store_id}
                </td>
                <td className="px-4 py-3 text-right tabular-nums">
                  {balance.quantity}
                </td>
                <td className="px-4 py-3 text-right tabular-nums text-muted-foreground">
                  {balance.reserved_quantity}
                </td>
                <td className="px-4 py-3">
                  <StockBadge
                    quantity={balance.quantity}
                    reserved={balance.reserved_quantity}
                  />
                </td>
              </tr>
            )
          })}
        </tbody>
      </table>
    </div>
  )
}
