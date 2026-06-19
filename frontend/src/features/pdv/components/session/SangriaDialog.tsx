'use client'

import { useState } from 'react'
import { X, ArrowDownToLine, Loader2 } from 'lucide-react'
import { usePdvSessionStore } from '../../stores/pdvSessionStore'
import { useRecordWithdrawal } from '../../hooks'

function inputToCents(s: string): number {
  return Math.round(parseFloat(s.replace(',', '.')) * 100) || 0
}

interface Props {
  onClose: () => void
}

export function SangriaDialog({ onClose }: Props) {
  const session      = usePdvSessionStore((s) => s.session)
  const withdrawal   = useRecordWithdrawal()
  const [amount, setAmount]    = useState('')
  const [description, setDesc] = useState('')
  const [error, setError]      = useState<string | null>(null)

  const amountCents = inputToCents(amount)
  const isValid     = amountCents > 0 && description.trim().length >= 3

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!isValid || !session) return
    setError(null)
    try {
      await withdrawal.mutateAsync({
        sessionUuid: session.sessionUuid,
        data: { amount_cents: amountCents, description: description.trim() },
      })
      onClose()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao registrar sangria')
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
      <div className="bg-card border rounded-2xl shadow-2xl w-full max-w-xs overflow-hidden">

        <div className="flex items-center justify-between px-4 py-3 border-b">
          <div className="flex items-center gap-2">
            <ArrowDownToLine className="w-4 h-4 text-destructive" />
            <p className="font-semibold text-sm">Sangria de Caixa</p>
          </div>
          <button onClick={onClose} className="text-muted-foreground hover:text-foreground transition-colors">
            <X className="w-4 h-4" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-4 space-y-3">
          <div>
            <label className="text-xs text-muted-foreground">Valor a retirar</label>
            <div className="relative mt-1">
              <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm pointer-events-none">
                R$
              </span>
              <input
                type="text"
                inputMode="decimal"
                value={amount}
                onChange={(e) => setAmount(e.target.value)}
                placeholder="0,00"
                className="w-full h-10 rounded-xl border bg-background pl-9 pr-3 text-sm font-bold tabular-nums focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                autoFocus
              />
            </div>
          </div>

          <div>
            <label className="text-xs text-muted-foreground">Descrição</label>
            <input
              type="text"
              value={description}
              onChange={(e) => setDesc(e.target.value)}
              placeholder="Ex: Depósito bancário, pagamento fornecedor…"
              className="mt-1 w-full h-10 rounded-xl border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
            />
          </div>

          {error && (
            <p className="text-xs text-destructive">{error}</p>
          )}

          <button
            type="submit"
            disabled={!isValid || withdrawal.isPending}
            className="w-full h-11 rounded-xl bg-destructive hover:bg-destructive/90 text-destructive-foreground font-bold text-sm disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-colors"
          >
            {withdrawal.isPending
              ? <Loader2 className="w-4 h-4 animate-spin" />
              : <ArrowDownToLine className="w-4 h-4" />}
            Registrar Sangria
          </button>
        </form>
      </div>
    </div>
  )
}
