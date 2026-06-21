'use client'

import { useState, useCallback } from 'react'
import { Plus, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import type { DocumentItemInput } from '@/services/orders.service'

// ── Helpers ───────────────────────────────────────────────────────────────────

export function formatBRL(cents: number): string {
  return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function parseCents(value: string): number {
  const n = parseFloat(value.replace(',', '.'))
  return isNaN(n) ? 0 : Math.round(n * 100)
}

function centsToDecimal(cents: number): string {
  return (cents / 100).toFixed(2)
}

// ── Types ─────────────────────────────────────────────────────────────────────

export interface EditorItem extends DocumentItemInput {
  _key: string
}

function newItem(): EditorItem {
  return {
    _key:             crypto.randomUUID(),
    name_snapshot:    '',
    sku_snapshot:     null,
    quantity:         1,
    unit_price_cents: 0,
    discount_cents:   0,
    notes:            null,
  }
}

function itemSubtotal(item: EditorItem): number {
  return Math.max(0, Math.round(item.quantity * item.unit_price_cents) - (item.discount_cents ?? 0))
}

// ── Component ─────────────────────────────────────────────────────────────────

interface Props {
  items:    EditorItem[]
  onChange: (items: EditorItem[]) => void
  disabled?: boolean
}

export function DocumentItemEditor({ items, onChange, disabled }: Props) {
  function addItem() {
    onChange([...items, newItem()])
  }

  function removeItem(key: string) {
    onChange(items.filter((i) => i._key !== key))
  }

  function updateItem(key: string, patch: Partial<EditorItem>) {
    onChange(items.map((i) => i._key === key ? { ...i, ...patch } : i))
  }

  const totalCents = items.reduce((sum, i) => sum + itemSubtotal(i), 0)

  return (
    <div className="space-y-3">
      <div className="overflow-x-auto rounded-md border">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-muted/50 text-left">
              <th className="px-3 py-2 font-medium text-muted-foreground w-[35%]">Produto / Descrição</th>
              <th className="px-3 py-2 font-medium text-muted-foreground w-[8%]">Qtd</th>
              <th className="px-3 py-2 font-medium text-muted-foreground w-[15%]">Preço Unit.</th>
              <th className="px-3 py-2 font-medium text-muted-foreground w-[13%]">Desconto</th>
              <th className="px-3 py-2 font-medium text-muted-foreground w-[14%] text-right">Subtotal</th>
              <th className="px-3 py-2 w-[8%]" />
            </tr>
          </thead>
          <tbody>
            {items.length === 0 && (
              <tr>
                <td colSpan={6} className="px-3 py-8 text-center text-muted-foreground">
                  Nenhum item adicionado.
                </td>
              </tr>
            )}
            {items.map((item) => (
              <tr key={item._key} className="border-b last:border-0">
                <td className="px-3 py-2">
                  <Input
                    value={item.name_snapshot}
                    onChange={(e) => updateItem(item._key, { name_snapshot: e.target.value })}
                    placeholder="Descrição do item"
                    disabled={disabled}
                    className="h-8 text-sm"
                  />
                </td>
                <td className="px-3 py-2">
                  <Input
                    type="number"
                    min={0.001}
                    step={0.001}
                    value={item.quantity}
                    onChange={(e) => updateItem(item._key, { quantity: parseFloat(e.target.value) || 1 })}
                    disabled={disabled}
                    className="h-8 text-sm w-20"
                  />
                </td>
                <td className="px-3 py-2">
                  <Input
                    type="number"
                    min={0}
                    step={0.01}
                    value={centsToDecimal(item.unit_price_cents)}
                    onChange={(e) => updateItem(item._key, { unit_price_cents: parseCents(e.target.value) })}
                    disabled={disabled}
                    className="h-8 text-sm"
                  />
                </td>
                <td className="px-3 py-2">
                  <Input
                    type="number"
                    min={0}
                    step={0.01}
                    value={centsToDecimal(item.discount_cents ?? 0)}
                    onChange={(e) => updateItem(item._key, { discount_cents: parseCents(e.target.value) })}
                    disabled={disabled}
                    className="h-8 text-sm"
                  />
                </td>
                <td className="px-3 py-2 text-right tabular-nums font-medium">
                  {formatBRL(itemSubtotal(item))}
                </td>
                <td className="px-3 py-2">
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8 text-destructive hover:text-destructive"
                    onClick={() => removeItem(item._key)}
                    disabled={disabled}
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </td>
              </tr>
            ))}
          </tbody>
          {items.length > 0 && (
            <tfoot>
              <tr className="border-t bg-muted/30">
                <td colSpan={4} className="px-3 py-2 text-right text-sm text-muted-foreground font-medium">
                  Total
                </td>
                <td className="px-3 py-2 text-right tabular-nums font-semibold">
                  {formatBRL(totalCents)}
                </td>
                <td />
              </tr>
            </tfoot>
          )}
        </table>
      </div>

      <Button type="button" variant="outline" size="sm" onClick={addItem} disabled={disabled}>
        <Plus className="mr-2 h-4 w-4" />
        Adicionar item
      </Button>
    </div>
  )
}
