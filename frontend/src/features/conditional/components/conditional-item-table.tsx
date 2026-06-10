'use client'

import { useState } from 'react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Skeleton } from '@/components/ui/skeleton'
import type { ConditionalItem } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatBRL(cents: number): string {
  return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function getPendingQty(item: ConditionalItem): number {
  if (item.pending_quantity !== undefined) return item.pending_quantity
  return Math.max(0, item.quantity - item.returned_quantity - item.sold_quantity)
}

// ── Props ─────────────────────────────────────────────────────────────────────

interface ConditionalItemTableProps {
  items:      ConditionalItem[]
  isLoading?: boolean
  readonly?:  boolean
  mode?:      'return' | 'convert'
  onReturn?:  (itemUuid: string, qty: number) => void
  onConvert?: (itemUuid: string, qty: number) => void
}

// ── Skeleton rows ──────────────────────────────────────────────────────────────

function SkeletonRows() {
  return (
    <>
      {Array.from({ length: 3 }).map((_, i) => (
        <tr key={i} className="border-b last:border-0">
          {Array.from({ length: 8 }).map((__, j) => (
            <td key={j} className="px-4 py-3">
              <Skeleton className="h-4 w-full" />
            </td>
          ))}
        </tr>
      ))}
    </>
  )
}

// ── Table Head ────────────────────────────────────────────────────────────────

function TableHead({ showActions }: { showActions: boolean }) {
  return (
    <tr className="border-b bg-muted/50 text-left">
      <th className="px-4 py-3 font-medium text-muted-foreground">Produto / SKU</th>
      <th className="px-4 py-3 font-medium text-muted-foreground text-center">Qtd</th>
      <th className="px-4 py-3 font-medium text-muted-foreground text-center">Devolvido</th>
      <th className="px-4 py-3 font-medium text-muted-foreground text-center">Vendido</th>
      <th className="px-4 py-3 font-medium text-muted-foreground text-center">Pendente</th>
      <th className="px-4 py-3 font-medium text-muted-foreground text-right">Preço Unit.</th>
      <th className="px-4 py-3 font-medium text-muted-foreground text-right">Total</th>
      {showActions && (
        <th className="px-4 py-3 font-medium text-muted-foreground">Ações</th>
      )}
    </tr>
  )
}

// ── Row ───────────────────────────────────────────────────────────────────────

function ItemRow({
  item,
  readonly,
  mode,
  onReturn,
  onConvert,
}: {
  item:      ConditionalItem
  readonly:  boolean
  mode?:     'return' | 'convert'
  onReturn?: (itemUuid: string, qty: number) => void
  onConvert?: (itemUuid: string, qty: number) => void
}) {
  const pending = getPendingQty(item)
  const [qty, setQty] = useState<number>(pending)

  const productName = item.variant?.product?.name ?? item.variant?.name ?? '—'
  const sku         = item.variant?.sku ?? '—'
  const totalCents  = item.quantity * item.unit_price_cents

  const showActions = !readonly && pending > 0

  return (
    <tr className="border-b last:border-0 hover:bg-muted/50 transition-colors">
      {/* Produto / SKU */}
      <td className="px-4 py-3">
        <div className="font-medium text-sm">{productName}</div>
        <div className="font-mono text-xs text-muted-foreground">{sku}</div>
      </td>

      {/* Quantidade */}
      <td className="px-4 py-3 text-center text-sm">{item.quantity}</td>

      {/* Devolvido */}
      <td className="px-4 py-3 text-center text-sm">{item.returned_quantity}</td>

      {/* Vendido */}
      <td className="px-4 py-3 text-center text-sm">{item.sold_quantity}</td>

      {/* Pendente */}
      <td className="px-4 py-3 text-center text-sm">
        <span
          className={cn(
            'font-semibold',
            pending > 0 ? 'text-red-600' : 'text-green-600',
          )}
        >
          {pending}
        </span>
      </td>

      {/* Preço Unit. */}
      <td className="px-4 py-3 text-right text-sm tabular-nums">
        {formatBRL(item.unit_price_cents)}
      </td>

      {/* Total */}
      <td className="px-4 py-3 text-right text-sm tabular-nums font-medium">
        {formatBRL(totalCents)}
      </td>

      {/* Ações */}
      {!readonly && (
        <td className="px-4 py-3">
          {showActions ? (
            <div className="flex items-center gap-2">
              <Input
                type="number"
                min={1}
                max={pending}
                value={qty}
                onChange={(e) => setQty(Math.min(pending, Math.max(1, Number(e.target.value))))}
                className="h-8 w-20 text-sm"
              />
              {(mode === 'return' || !mode) && onReturn && (
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => onReturn(item.uuid, qty)}
                  className="h-8 text-xs"
                >
                  Devolver
                </Button>
              )}
              {(mode === 'convert' || !mode) && onConvert && (
                <Button
                  size="sm"
                  variant="default"
                  onClick={() => onConvert(item.uuid, qty)}
                  className="h-8 text-xs"
                >
                  Converter
                </Button>
              )}
            </div>
          ) : (
            <span className="text-xs text-muted-foreground">—</span>
          )}
        </td>
      )}
    </tr>
  )
}

// ── Main component ─────────────────────────────────────────────────────────────

export function ConditionalItemTable({
  items,
  isLoading = false,
  readonly  = false,
  mode,
  onReturn,
  onConvert,
}: ConditionalItemTableProps) {
  const showActions = !readonly

  return (
    <div className="rounded-md border overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <TableHead showActions={showActions} />
        </thead>
        <tbody>
          {isLoading ? (
            <SkeletonRows />
          ) : items.length === 0 ? (
            <tr>
              <td
                colSpan={showActions ? 8 : 7}
                className="px-4 py-10 text-center text-muted-foreground"
              >
                Nenhum item encontrado.
              </td>
            </tr>
          ) : (
            items.map((item) => (
              <ItemRow
                key={item.uuid}
                item={item}
                readonly={readonly}
                mode={mode}
                onReturn={onReturn}
                onConvert={onConvert}
              />
            ))
          )}
        </tbody>
      </table>
    </div>
  )
}
