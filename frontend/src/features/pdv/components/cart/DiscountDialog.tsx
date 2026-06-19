'use client'

import { useState, useEffect, FormEvent } from 'react'
import { X, Tag } from 'lucide-react'
import { usePdvCartStore } from '@/features/pdv/stores/pdvCartStore'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

interface Props {
  open:    boolean
  onClose: () => void
}

export function DiscountDialog({ open, onClose }: Props) {
  const subtotal    = usePdvCartStore((s) => s.subtotalCents())
  const setDiscount = usePdvCartStore((s) => s.setDiscount)
  const current     = usePdvCartStore((s) => s.discountCents)

  const [mode,  setMode]  = useState<'percent' | 'value'>('percent')
  const [input, setInput] = useState('')

  useEffect(() => {
    if (open) {
      // Pré-preenche com o desconto atual
      if (current > 0) {
        setMode('value')
        setInput((current / 100).toFixed(2).replace('.', ','))
      } else {
        setInput('')
      }
    }
  }, [open, current])

  // F10 / Escape
  useEffect(() => {
    function onKey(e: KeyboardEvent) {
      if (!open) return
      if (e.key === 'Escape') onClose()
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [open, onClose])

  if (!open) return null

  const numericInput = parseFloat(input.replace(',', '.')) || 0
  const discountCents =
    mode === 'percent'
      ? Math.round((numericInput / 100) * subtotal)
      : Math.round(numericInput * 100)

  const isValid = discountCents >= 0 && discountCents <= subtotal
  const totalAfter = Math.max(0, subtotal - discountCents)

  function handleApply(e: FormEvent) {
    e.preventDefault()
    if (!isValid) return
    setDiscount(discountCents)
    onClose()
  }

  function handleRemove() {
    setDiscount(0)
    onClose()
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
      <div className="bg-card border rounded-2xl shadow-2xl w-full max-w-xs overflow-hidden">
        {/* Header */}
        <div className="flex items-center justify-between px-4 py-3 border-b">
          <div className="flex items-center gap-2">
            <Tag className="w-4 h-4 text-primary" />
            <p className="font-semibold text-sm">Aplicar Desconto</p>
          </div>
          <button onClick={onClose} className="text-muted-foreground hover:text-foreground transition-colors">
            <X className="w-4 h-4" />
          </button>
        </div>

        <form onSubmit={handleApply} className="p-4 space-y-4">
          {/* Toggle modo */}
          <div className="flex rounded-lg border overflow-hidden">
            <button
              type="button"
              onClick={() => { setMode('percent'); setInput('') }}
              className={cn(
                'flex-1 py-2 text-xs font-medium transition-colors',
                mode === 'percent' ? 'bg-primary text-primary-foreground' : 'hover:bg-accent',
              )}
            >
              Porcentagem (%)
            </button>
            <button
              type="button"
              onClick={() => { setMode('value'); setInput('') }}
              className={cn(
                'flex-1 py-2 text-xs font-medium transition-colors',
                mode === 'value' ? 'bg-primary text-primary-foreground' : 'hover:bg-accent',
              )}
            >
              Valor (R$)
            </button>
          </div>

          {/* Input */}
          <div className="relative">
            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">
              {mode === 'percent' ? '%' : 'R$'}
            </span>
            <input
              type="number"
              min="0"
              max={mode === 'percent' ? '100' : String(subtotal / 100)}
              step="0.01"
              value={input}
              onChange={(e) => setInput(e.target.value)}
              placeholder={mode === 'percent' ? '0' : '0,00'}
              className="w-full h-12 rounded-xl border bg-background pl-10 pr-4 text-lg font-bold tabular-nums focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
              autoFocus
            />
          </div>

          {/* Preview */}
          {numericInput > 0 && (
            <div className={cn(
              'rounded-xl p-3 text-xs space-y-1',
              isValid ? 'bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800' : 'bg-destructive/10 border border-destructive/30',
            )}>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Subtotal</span>
                <span>{fmtBRL(subtotal)}</span>
              </div>
              <div className="flex justify-between text-green-700 dark:text-green-400">
                <span>Desconto</span>
                <span>- {fmtBRL(discountCents)}</span>
              </div>
              <div className="flex justify-between font-bold text-sm border-t pt-1">
                <span>Total</span>
                <span>{fmtBRL(totalAfter)}</span>
              </div>
              {!isValid && (
                <p className="text-destructive text-[10px] pt-1">
                  Desconto maior que o subtotal.
                </p>
              )}
            </div>
          )}

          <div className="flex gap-2">
            {current > 0 && (
              <Button type="button" variant="outline" size="sm" onClick={handleRemove} className="shrink-0">
                Remover
              </Button>
            )}
            <Button type="submit" className="flex-1" disabled={!isValid || numericInput === 0}>
              Aplicar
            </Button>
          </div>
        </form>
      </div>
    </div>
  )
}
