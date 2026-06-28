'use client'

import { useState } from 'react'
import { Calculator, AlertTriangle } from 'lucide-react'
import { AppCard } from '@/components/shared/app-card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { cn } from '@/lib/utils'

// ── Types ────────────────────────────────────────────────────────────────────

interface DiscountResult {
  originalPriceCents: number
  discountPercent:    number
  discountCents:      number
  finalPriceCents:    number
}

interface DiscountCalculatorProps {
  maxDiscountPercent?: number
  initialPriceCents?:  number
  priceListUuid?:      string
  onApply?:            (result: DiscountResult) => void
}

// ── Helpers ──────────────────────────────────────────────────────────────────

const brl = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

function parseCurrencyInput(raw: string): number {
  // Remove everything except digits and comma/dot, then convert to cents
  const digits = raw.replace(/\D/g, '')
  return parseInt(digits || '0', 10)
}

// ── Component ────────────────────────────────────────────────────────────────

export function DiscountCalculator({
  maxDiscountPercent,
  initialPriceCents = 0,
  onApply,
}: DiscountCalculatorProps) {
  const [priceCents, setPriceCents]           = useState<number>(initialPriceCents)
  const [priceInput, setPriceInput]           = useState<string>(
    initialPriceCents > 0 ? (initialPriceCents / 100).toFixed(2) : ''
  )
  const [discountPercent, setDiscountPercent] = useState<number>(0)

  // ── Calculations ────────────────────────────────────────────────────────
  const discountCents  = Math.round(priceCents * discountPercent / 100)
  const finalPriceCents = priceCents - discountCents

  const exceedsLimit =
    maxDiscountPercent !== undefined && discountPercent > maxDiscountPercent

  const canApply = !exceedsLimit && priceCents > 0

  // ── Handlers ─────────────────────────────────────────────────────────────

  function handlePriceChange(e: React.ChangeEvent<HTMLInputElement>) {
    const raw = e.target.value
    setPriceInput(raw)
    // Parse as BRL value typed by user (e.g. "12,50" → 1250 cents)
    const normalized = raw.replace(',', '.')
    const float = parseFloat(normalized)
    setPriceCents(isNaN(float) ? 0 : Math.round(float * 100))
  }

  function handlePriceBlur() {
    if (priceCents > 0) {
      setPriceInput((priceCents / 100).toFixed(2))
    }
  }

  function handleDiscountChange(e: React.ChangeEvent<HTMLInputElement>) {
    const val = parseFloat(e.target.value)
    setDiscountPercent(isNaN(val) ? 0 : Math.min(100, Math.max(0, val)))
  }

  function handleApply() {
    if (!canApply || !onApply) return
    onApply({
      originalPriceCents: priceCents,
      discountPercent,
      discountCents,
      finalPriceCents,
    })
  }

  // ── Render ────────────────────────────────────────────────────────────────

  return (
    <AppCard
      title="Calculadora de Desconto"
      actions={<Calculator className="h-4 w-4 text-muted-foreground" />}
    >
      <div className="space-y-4">
        {/* Inputs */}
        <div className="grid grid-cols-2 gap-4">
          <div className="space-y-1.5">
            <Label htmlFor="dc-price">Preço original (R$)</Label>
            <div className="relative">
              <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                R$
              </span>
              <Input
                id="dc-price"
                type="text"
                inputMode="decimal"
                placeholder="0,00"
                value={priceInput}
                onChange={handlePriceChange}
                onBlur={handlePriceBlur}
                className="pl-9"
              />
            </div>
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="dc-discount">% de desconto</Label>
            <Input
              id="dc-discount"
              type="number"
              min={0}
              max={100}
              step={0.5}
              placeholder="0"
              value={discountPercent === 0 ? '' : discountPercent}
              onChange={handleDiscountChange}
            />
          </div>
        </div>

        {/* Divider */}
        <hr className="border-dashed" />

        {/* Result */}
        <div className="space-y-2">
          <div className="flex justify-between text-sm text-muted-foreground">
            <span>Valor do desconto</span>
            <span className="font-medium text-foreground">{brl(discountCents)}</span>
          </div>
          <div className="flex justify-between items-center">
            <span className="text-sm font-medium">Preço final</span>
            <span className={cn(
              'text-xl font-bold',
              priceCents > 0 ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'
            )}>
              {brl(finalPriceCents)}
            </span>
          </div>
        </div>

        {/* Warning */}
        {exceedsLimit && (
          <div className="flex items-start gap-2 rounded-md bg-destructive/10 border border-destructive/30 p-3 text-sm text-destructive">
            <AlertTriangle className="h-4 w-4 mt-0.5 shrink-0" />
            <span>
              Desconto máximo permitido: <strong>{maxDiscountPercent}%</strong> (tabela de preços).
              Reduza o percentual para continuar.
            </span>
          </div>
        )}

        {/* Apply button */}
        {onApply && (
          <Button
            className="w-full"
            disabled={!canApply}
            onClick={handleApply}
          >
            Aplicar desconto
          </Button>
        )}
      </div>
    </AppCard>
  )
}
