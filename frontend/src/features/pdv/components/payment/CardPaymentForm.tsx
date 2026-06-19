'use client'

import { useState } from 'react'
import { InstallmentTable, calcInstallmentCents } from './InstallmentTable'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

function centsToInput(cents: number): string {
  return (cents / 100).toFixed(2).replace('.', ',')
}

function inputToCents(s: string): number {
  return Math.round(parseFloat(s.replace(',', '.')) * 100) || 0
}

const CARD_BRANDS = ['Visa', 'Mastercard', 'Elo', 'Hipercard', 'AmEx', 'Outros']

interface AddCardData {
  amountCents:   number
  installments?: number
  nsu:           string
  authCode:      string
  cardBrand:     string
}

interface Props {
  type:           'debit' | 'credit'
  remainingCents: number
  onAdd:          (data: AddCardData) => void
}

export function CardPaymentForm({ type, remainingCents, onAdd }: Props) {
  const [amountStr,    setAmountStr]    = useState(centsToInput(remainingCents))
  const [nsu,          setNsu]          = useState('')
  const [authCode,     setAuthCode]     = useState('')
  const [cardBrand,    setCardBrand]    = useState('Visa')
  const [installments, setInstallments] = useState(1)

  const amountCents   = inputToCents(amountStr)
  const installValue  = type === 'credit' ? calcInstallmentCents(amountCents, installments) : amountCents
  const isValid       = amountCents > 0 && nsu.length >= 3 && authCode.length >= 3

  function handleAdd() {
    if (!isValid) return
    onAdd({
      amountCents,
      ...(type === 'credit' && installments > 1 ? { installments } : {}),
      nsu,
      authCode,
      cardBrand,
    })
    setAmountStr(centsToInput(Math.max(0, remainingCents - amountCents)))
    setNsu('')
    setAuthCode('')
    setInstallments(1)
  }

  return (
    <div className="space-y-3">
      {/* Amount */}
      <div>
        <label className="text-xs text-muted-foreground">Valor</label>
        <div className="relative mt-1">
          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm pointer-events-none">
            R$
          </span>
          <input
            type="text"
            inputMode="decimal"
            value={amountStr}
            onChange={(e) => setAmountStr(e.target.value)}
            className="w-full h-10 rounded-xl border bg-background pl-9 pr-3 text-sm font-bold tabular-nums focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
          />
        </div>
      </div>

      <div className="grid grid-cols-2 gap-2">
        {/* Card brand */}
        <div>
          <label className="text-xs text-muted-foreground">Bandeira</label>
          <select
            value={cardBrand}
            onChange={(e) => setCardBrand(e.target.value)}
            className="mt-1 w-full h-10 rounded-xl border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
          >
            {CARD_BRANDS.map((b) => <option key={b}>{b}</option>)}
          </select>
        </div>

        {/* NSU */}
        <div>
          <label className="text-xs text-muted-foreground">NSU</label>
          <input
            type="text"
            inputMode="numeric"
            value={nsu}
            onChange={(e) => setNsu(e.target.value)}
            placeholder="000000"
            className="mt-1 w-full h-10 rounded-xl border bg-background px-3 text-sm tabular-nums focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
          />
        </div>
      </div>

      {/* Authorization code */}
      <div>
        <label className="text-xs text-muted-foreground">Código de Autorização</label>
        <input
          type="text"
          value={authCode}
          onChange={(e) => setAuthCode(e.target.value)}
          placeholder="Ex: 012345"
          className="mt-1 w-full h-10 rounded-xl border bg-background px-3 text-sm tabular-nums focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
        />
      </div>

      {/* Installments (credit only) */}
      {type === 'credit' && amountCents > 0 && (
        <div>
          <label className="text-xs text-muted-foreground">Parcelamento</label>
          <div className="mt-1">
            <InstallmentTable
              totalCents={amountCents}
              selected={installments}
              onSelect={setInstallments}
            />
          </div>
          {installments > 1 && (
            <p className="text-xs text-muted-foreground mt-1.5 text-right">
              {installments}x de {fmtBRL(installValue)}
            </p>
          )}
        </div>
      )}

      <button
        onClick={handleAdd}
        disabled={!isValid}
        className="w-full h-12 rounded-xl bg-primary text-primary-foreground font-bold hover:bg-primary/90 disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] transition-all"
      >
        Adicionar Pagamento
      </button>
    </div>
  )
}
