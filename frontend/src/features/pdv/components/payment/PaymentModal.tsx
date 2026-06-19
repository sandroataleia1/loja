'use client'

import { useState, useEffect } from 'react'
import { X, CheckCircle2, Loader2, Banknote, CreditCard, Smartphone, AlertCircle } from 'lucide-react'
import { cn } from '@/lib/utils'
import { usePdvCartStore } from '../../stores/pdvCartStore'
import { usePayment } from '../../hooks/usePayment'
import { CashPaymentForm } from './CashPaymentForm'
import { CardPaymentForm } from './CardPaymentForm'
import { PixPaymentForm } from './PixPaymentForm'
import { PaymentSummaryList } from './PaymentSummaryList'
import { ChangeDisplay } from './ChangeDisplay'
import type { PendingPayment, PaymentTabKey } from '../../types'
import type { Sale } from '@/types/shared-types'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

const TABS: { key: PaymentTabKey; label: string; icon: React.ElementType }[] = [
  { key: 'cash',   label: 'Dinheiro', icon: Banknote  },
  { key: 'debit',  label: 'Débito',   icon: CreditCard },
  { key: 'credit', label: 'Crédito',  icon: CreditCard },
  { key: 'pix',    label: 'PIX',      icon: Smartphone },
]

interface Props {
  onClose:   () => void
  onSuccess: (sale: Sale, payments: PendingPayment[]) => void
}

export function PaymentModal({ onClose, onSuccess }: Props) {
  const total   = usePdvCartStore((s) => s.totalCents())
  const payment = usePayment()

  const [tab,      setTab]      = useState<PaymentTabKey>('cash')
  const [payments, setPayments] = useState<PendingPayment[]>([])
  const [error,    setError]    = useState<string | null>(null)

  const paidCents      = payments.reduce((sum, p) => sum + p.amountCents, 0)
  const remainingCents = Math.max(0, total - paidCents)
  const changeCents    = Math.max(0, paidCents - total)
  const canConfirm     = paidCents >= total && payments.length > 0

  // Esc → fechar | Enter → confirmar
  useEffect(() => {
    function onKey(e: KeyboardEvent) {
      if (e.key === 'Escape') { e.preventDefault(); onClose() }
      if (e.key === 'Enter'  && canConfirm && !payment.isPending) {
        e.preventDefault()
        handleConfirm()
      }
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [canConfirm, payment.isPending])

  function addPayment(p: Omit<PendingPayment, 'id'>) {
    setPayments((prev) => [...prev, { ...p, id: crypto.randomUUID() }])
    setError(null)
  }

  function removePayment(id: string) {
    setPayments((prev) => prev.filter((p) => p.id !== id))
    setError(null)
  }

  async function handleConfirm() {
    if (!canConfirm || payment.isPending) return
    setError(null)
    try {
      const sale = await payment.mutateAsync(payments)
      onSuccess(sale, payments)
    } catch (err) {
      const msg = err instanceof Error ? err.message : 'Erro ao processar venda. Tente novamente.'
      setError(msg)
    }
  }

  function handleAddCash(amountCents: number) {
    addPayment({ tab: 'cash', method: 'cash', label: 'Dinheiro', amountCents })
  }

  function handleAddCard(data: {
    amountCents:   number
    installments?: number
    nsu:           string
    authCode:      string
    cardBrand:     string
  }) {
    const isCredit = tab === 'credit'
    addPayment({
      tab,
      method:      isCredit ? 'credit_card' : 'debit_card',
      label:       isCredit ? 'Crédito'     : 'Débito',
      amountCents: data.amountCents,
      installments:data.installments,
      nsu:         data.nsu,
      authCode:    data.authCode,
      cardBrand:   data.cardBrand,
    })
  }

  function handleAddPix(amountCents: number) {
    addPayment({ tab: 'pix', method: 'pix', label: 'PIX', amountCents })
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div className="bg-card border rounded-2xl shadow-2xl w-full max-w-md flex flex-col max-h-[92vh] overflow-hidden">

        {/* Header */}
        <div className="flex items-center justify-between px-5 py-4 border-b shrink-0">
          <div>
            <p className="text-xs text-muted-foreground font-medium uppercase tracking-wide">Pagamento</p>
            <p className="text-3xl font-bold tabular-nums">{fmtBRL(total)}</p>
          </div>
          <button
            onClick={onClose}
            className="text-muted-foreground hover:text-foreground transition-colors p-1 -mr-1"
            aria-label="Fechar"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Body */}
        <div className="flex-1 overflow-y-auto p-5 space-y-4">

          {/* Payments added so far */}
          <PaymentSummaryList payments={payments} onRemove={removePayment} />

          {/* Troco */}
          <ChangeDisplay changeCents={changeCents} />

          {/* Remaining indicator */}
          {remainingCents > 0 && payments.length > 0 && (
            <div className="flex items-center justify-between text-sm border rounded-xl px-3 py-2 bg-muted/30">
              <span className="text-muted-foreground">Restante</span>
              <span className="font-bold tabular-nums">{fmtBRL(remainingCents)}</span>
            </div>
          )}

          {/* Payment form — only show when there's still remaining */}
          {remainingCents > 0 && (
            <>
              {/* Tabs */}
              <div className="flex rounded-xl border overflow-hidden">
                {TABS.map(({ key, label, icon: Icon }) => (
                  <button
                    key={key}
                    onClick={() => setTab(key)}
                    className={cn(
                      'flex-1 flex flex-col items-center gap-0.5 py-2.5 text-[11px] font-medium transition-colors',
                      tab === key
                        ? 'bg-primary text-primary-foreground'
                        : 'hover:bg-accent text-muted-foreground',
                    )}
                  >
                    <Icon className="w-4 h-4" />
                    {label}
                  </button>
                ))}
              </div>

              {/* Active form — key resets form after each payment added */}
              <div key={`${tab}-${payments.length}`}>
                {tab === 'cash'   && <CashPaymentForm remainingCents={remainingCents} onAdd={handleAddCash} />}
                {tab === 'debit'  && <CardPaymentForm type="debit"  remainingCents={remainingCents} onAdd={handleAddCard} />}
                {tab === 'credit' && <CardPaymentForm type="credit" remainingCents={remainingCents} onAdd={handleAddCard} />}
                {tab === 'pix'    && <PixPaymentForm  remainingCents={remainingCents} onAdd={handleAddPix} />}
              </div>
            </>
          )}

          {/* Error */}
          {error && (
            <div className="flex items-start gap-2 rounded-xl bg-destructive/10 border border-destructive/30 px-3 py-2.5 text-destructive text-sm">
              <AlertCircle className="w-4 h-4 shrink-0 mt-0.5" />
              <p>{error}</p>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="px-5 pb-5 pt-3 border-t shrink-0">
          <button
            onClick={handleConfirm}
            disabled={!canConfirm || payment.isPending}
            className="w-full h-14 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold text-base disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98] transition-all flex items-center justify-center gap-2"
          >
            {payment.isPending ? (
              <>
                <Loader2 className="w-5 h-5 animate-spin" />
                Processando…
              </>
            ) : (
              <>
                <CheckCircle2 className="w-5 h-5" />
                Confirmar Venda
                {changeCents > 0 && (
                  <span className="text-sm font-normal opacity-80">
                    · Troco {fmtBRL(changeCents)}
                  </span>
                )}
              </>
            )}
          </button>
          <p className="text-center text-[10px] text-muted-foreground/60 mt-2">Enter para confirmar · Esc para fechar</p>
        </div>
      </div>
    </div>
  )
}
