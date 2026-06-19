import { X, Banknote, CreditCard, Smartphone } from 'lucide-react'
import type { PendingPayment, PaymentTabKey } from '../../types'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

const METHOD_ICON: Record<PaymentTabKey, React.ElementType> = {
  cash:   Banknote,
  debit:  CreditCard,
  credit: CreditCard,
  pix:    Smartphone,
}

interface Props {
  payments: PendingPayment[]
  onRemove: (id: string) => void
}

export function PaymentSummaryList({ payments, onRemove }: Props) {
  if (payments.length === 0) return null

  return (
    <div className="space-y-1.5">
      <p className="text-xs text-muted-foreground font-medium">Pagamentos adicionados</p>
      {payments.map((p) => {
        const Icon = METHOD_ICON[p.tab]
        return (
          <div key={p.id} className="flex items-center gap-2 bg-muted/40 rounded-xl px-3 py-2">
            <Icon className="w-4 h-4 text-muted-foreground shrink-0" />
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium">
                {p.label}
                {p.installments && p.installments > 1 && (
                  <span className="text-xs text-muted-foreground ml-1.5">({p.installments}x)</span>
                )}
              </p>
              {p.nsu && (
                <p className="text-[10px] text-muted-foreground">NSU {p.nsu}</p>
              )}
            </div>
            <span className="font-bold text-sm tabular-nums shrink-0">{fmtBRL(p.amountCents)}</span>
            <button
              onClick={() => onRemove(p.id)}
              className="ml-1 text-muted-foreground hover:text-destructive transition-colors shrink-0"
              aria-label="Remover pagamento"
            >
              <X className="w-3.5 h-3.5" />
            </button>
          </div>
        )
      })}
    </div>
  )
}
