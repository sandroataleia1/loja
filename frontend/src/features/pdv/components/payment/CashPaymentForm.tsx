'use client'

import { useState } from 'react'
import { Delete } from 'lucide-react'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

const QUICK_AMOUNTS = [5000, 10000, 20000, 50000]

interface Props {
  remainingCents: number
  onAdd:          (amountCents: number) => void
}

export function CashPaymentForm({ remainingCents, onAdd }: Props) {
  const [rawCents, setRawCents] = useState(0)

  function pressDigit(d: number) {
    setRawCents((prev) => {
      const next = prev * 10 + d
      return next > 99_999_999 ? prev : next
    })
  }

  function pressDouble0() {
    setRawCents((prev) => {
      const next = prev * 100
      return next > 99_999_999 ? prev : next
    })
  }

  function pressBackspace() {
    setRawCents((prev) => Math.floor(prev / 10))
  }

  const displayCents = rawCents > 0 ? rawCents : remainingCents
  const changeCents  = Math.max(0, displayCents - remainingCents)

  function handleAdd() {
    const amt = rawCents > 0 ? rawCents : remainingCents
    if (amt <= 0) return
    onAdd(amt)
    setRawCents(0)
  }

  return (
    <div className="space-y-4">
      {/* Display */}
      <div className="bg-muted/40 rounded-2xl p-4 text-center">
        <p className="text-xs text-muted-foreground mb-1">Valor recebido</p>
        <p className="text-4xl font-bold tabular-nums">{fmtBRL(displayCents)}</p>
        {changeCents > 0 && (
          <div className="mt-2.5 py-1.5 px-3 bg-green-100 dark:bg-green-900/30 rounded-xl inline-flex items-center gap-2 text-green-700 dark:text-green-400">
            <span className="text-xs">Troco:</span>
            <span className="font-bold text-sm tabular-nums">{fmtBRL(changeCents)}</span>
          </div>
        )}
      </div>

      {/* Quick fill */}
      <div className="flex gap-2">
        {QUICK_AMOUNTS.map((v) => (
          <button
            key={v}
            onClick={() => setRawCents(v)}
            className="flex-1 h-8 rounded-lg border text-xs font-medium hover:bg-accent active:scale-95 transition-all"
          >
            {fmtBRL(v)}
          </button>
        ))}
      </div>

      {/* Numpad */}
      <div className="grid grid-cols-3 gap-2">
        {[7, 8, 9, 4, 5, 6, 1, 2, 3].map((d) => (
          <button
            key={d}
            onClick={() => pressDigit(d)}
            className="h-14 rounded-xl border font-bold text-xl hover:bg-accent active:scale-95 transition-all"
          >
            {d}
          </button>
        ))}
        <button
          onClick={pressDouble0}
          className="h-14 rounded-xl border font-bold text-lg hover:bg-accent active:scale-95 transition-all"
        >
          00
        </button>
        <button
          onClick={() => pressDigit(0)}
          className="h-14 rounded-xl border font-bold text-xl hover:bg-accent active:scale-95 transition-all"
        >
          0
        </button>
        <button
          onClick={pressBackspace}
          className="h-14 rounded-xl border hover:bg-accent active:scale-95 transition-all flex items-center justify-center"
        >
          <Delete className="w-5 h-5" />
        </button>
      </div>

      <button
        onClick={handleAdd}
        className="w-full h-12 rounded-xl bg-primary text-primary-foreground font-bold hover:bg-primary/90 active:scale-[0.98] transition-all"
      >
        Adicionar Pagamento
      </button>
    </div>
  )
}
