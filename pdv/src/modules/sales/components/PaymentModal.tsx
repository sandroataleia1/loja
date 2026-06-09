import { useState, useRef, useEffect, useCallback } from 'react'
import { CheckCircle2, X, Banknote, CreditCard, Smartphone, Wallet } from 'lucide-react'
import {
  Dialog, DialogContent, DialogHeader, DialogTitle,
} from '@/components/ui/dialog'
import { Button }    from '@/components/ui/button'
import { Input }     from '@/components/ui/input'
import { Separator } from '@/components/ui/separator'
import { useCartStore }    from '@/stores/cartStore'
import { useSessionStore } from '@/stores/sessionStore'
import { useSaleCreate }   from '@/modules/sales/hooks/useSale'
import { formatCurrency, parseCurrency } from '@/utils/currency'
import type { CartPayment, PaymentMethod } from '@/types/sale'
import { cn } from '@/lib/utils'

const METHODS: {
  value:   PaymentMethod
  label:   string
  short:   string
  key:     string
  icon:    React.ElementType
}[] = [
  { value: 'cash',        label: 'Dinheiro', short: 'Din.',  key: '1', icon: Banknote },
  { value: 'card_debit',  label: 'Débito',   short: 'Déb.',  key: '2', icon: CreditCard },
  { value: 'card_credit', label: 'Crédito',  short: 'Cré.',  key: '3', icon: CreditCard },
  { value: 'pix',         label: 'Pix',      short: 'PIX',   key: '4', icon: Smartphone },
]

interface Props {
  open:         boolean
  onOpenChange: (open: boolean) => void
  total:        number
}

export function PaymentModal({ open, onOpenChange, total }: Props) {
  const [payments, setPayments]   = useState<CartPayment[]>([])
  const [method, setMethod]       = useState<PaymentMethod>('cash')
  const [amountStr, setAmountStr] = useState('')

  const amountRef  = useRef<HTMLInputElement>(null)
  const confirmRef = useRef<HTMLButtonElement>(null)

  const { items, discount, customerUuid } = useCartStore()
  const { userUuid }                      = useSessionStore()
  const { mutate: createSale, isPending } = useSaleCreate()

  const paidTotal  = payments.reduce((s, p) => s + p.amount, 0)
  const remaining  = Math.max(0, total - paidTotal)
  const change     = Math.max(0, paidTotal - total)
  const canConfirm = paidTotal >= total && items.length > 0

  // Reset on open
  useEffect(() => {
    if (open) {
      setPayments([])
      setAmountStr('')
      setMethod('cash')
      setTimeout(() => amountRef.current?.focus(), 50)
    }
  }, [open])

  // Auto-fill remaining for card/pix
  useEffect(() => {
    if (method !== 'cash' && open) {
      const rem = Math.max(0, total - paidTotal)
      setAmountStr(rem > 0 ? (rem / 100).toFixed(2).replace('.', ',') : '')
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [method])

  const addPayment = useCallback(() => {
    const amount = parseCurrency(amountStr)
    if (amount <= 0) return

    const next = [...payments, { method, amount, reference: '' }]
    const newPaid = next.reduce((s, p) => s + p.amount, 0)
    setPayments(next)
    setAmountStr('')

    if (newPaid >= total) {
      setTimeout(() => confirmRef.current?.focus(), 30)
    } else {
      amountRef.current?.focus()
    }
  }, [amountStr, payments, method, total])

  const handleConfirm = useCallback(() => {
    createSale(
      {
        customer_uuid: customerUuid ?? undefined,
        user_uuid:     userUuid,
        discount:      discount > 0 ? discount : undefined,
        items: items.map((i) => ({
          product_uuid:         i.productUuid,
          variant_uuid:         i.variantUuid ?? null,
          product_name:         i.productName,
          product_sku:          i.productSku,
          name_snapshot:        i.productName,
          sku_snapshot:         i.productSku,
          attributes_snapshot:  i.attributesJson ?? '{}',
          cost_snapshot:        i.cost ?? null,
          quantity:             i.quantity,
          unit_price:           i.unitPrice,
          discount:             i.discount > 0 ? i.discount : undefined,
        })),
        payments: payments.map((p) => ({
          method:    p.method,
          amount:    p.amount,
          reference: p.reference || undefined,
        })),
      },
      {
        onSuccess: () => {
          setPayments([])
          setAmountStr('')
          onOpenChange(false)
        },
      },
    )
  }, [createSale, customerUuid, userUuid, discount, items, payments, onOpenChange])

  const handleClose = useCallback(() => {
    if (!isPending) { setPayments([]); setAmountStr(''); onOpenChange(false) }
  }, [isPending, onOpenChange])

  // Global keyboard shortcuts when modal is open
  useEffect(() => {
    if (!open) return
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') { handleClose(); return }
      if (e.key === 'F1') { e.preventDefault(); setMethod('cash');        amountRef.current?.focus() }
      if (e.key === 'F2') { e.preventDefault(); setMethod('card_debit');  amountRef.current?.focus() }
      if (e.key === 'F3') { e.preventDefault(); setMethod('card_credit'); amountRef.current?.focus() }
      if (e.key === 'F4') { e.preventDefault(); setMethod('pix');         amountRef.current?.focus() }
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [open, handleClose])

  const handleAmountKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      e.preventDefault()
      if (amountStr) {
        addPayment()
      } else if (canConfirm) {
        handleConfirm()
      } else if (remaining > 0) {
        // Fill exact remaining and add
        setAmountStr((remaining / 100).toFixed(2).replace('.', ','))
        setTimeout(addPayment, 10)
      }
    }
  }

  const fillExact = () => {
    setAmountStr((remaining / 100).toFixed(2).replace('.', ','))
    amountRef.current?.focus()
  }

  const methodLabel = METHODS.find((m) => m.value === method)?.label ?? ''

  return (
    <Dialog open={open} onOpenChange={handleClose}>
      <DialogContent
        className="max-w-lg p-0 gap-0 overflow-hidden"
        onOpenAutoFocus={(e) => e.preventDefault()}
      >
        {/* Header with total */}
        <DialogHeader className="p-0">
          <div className="flex items-center justify-between px-6 pt-5 pb-4 border-b bg-primary/5">
            <div>
              <DialogTitle className="text-sm font-medium text-muted-foreground mb-0.5">
                Finalizar Venda
              </DialogTitle>
              <p className="text-3xl font-bold tabular-nums text-primary">
                {formatCurrency(total)}
              </p>
            </div>
            <button
              onClick={handleClose}
              className="text-muted-foreground hover:text-foreground transition-colors p-1"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </DialogHeader>

        <div className="p-5 space-y-5">
          {/* Payment method selection */}
          <div className="space-y-2">
            <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
              Forma de Pagamento
            </p>
            <div className="grid grid-cols-4 gap-2">
              {METHODS.map((m) => {
                const Icon = m.icon
                const active = method === m.value
                return (
                  <button
                    key={m.value}
                    onClick={() => { setMethod(m.value); amountRef.current?.focus() }}
                    className={cn(
                      'flex flex-col items-center gap-1.5 py-3 px-2 rounded-xl border text-xs font-medium transition-all',
                      active
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border hover:border-primary/50 hover:bg-accent',
                    )}
                  >
                    <Icon className="w-5 h-5" />
                    <span>{m.short}</span>
                    <kbd className={cn(
                      'text-[9px] font-mono px-1 py-0.5 rounded border',
                      active
                        ? 'border-primary-foreground/30 bg-primary-foreground/10 text-primary-foreground/80'
                        : 'kbd',
                    )}>
                      F{m.key}
                    </kbd>
                  </button>
                )
              })}
            </div>
          </div>

          {/* Amount input */}
          <div className="space-y-2">
            <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
              Valor — {methodLabel}
            </p>
            <div className="flex gap-2">
              <div className="relative flex-1">
                <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-muted-foreground text-base pointer-events-none">
                  R$
                </span>
                <Input
                  ref={amountRef}
                  value={amountStr}
                  onChange={(e) => setAmountStr(e.target.value)}
                  onKeyDown={handleAmountKeyDown}
                  placeholder="0,00"
                  className="pl-9 h-12 text-xl text-right tabular-nums font-semibold"
                  autoComplete="off"
                />
              </div>
              <Button
                variant="outline"
                className="h-12 shrink-0 text-xs"
                onClick={fillExact}
                tabIndex={-1}
              >
                Exato
              </Button>
              <Button
                className="h-12 shrink-0 gap-1"
                onClick={addPayment}
                disabled={!amountStr || parseCurrency(amountStr) <= 0}
                tabIndex={-1}
              >
                <span>Adicionar</span>
                <kbd className="kbd text-[9px] bg-primary-foreground/20 border-primary-foreground/20 text-primary-foreground">↵</kbd>
              </Button>
            </div>
          </div>

          {/* Payments list */}
          {payments.length > 0 && (
            <div className="rounded-xl border divide-y overflow-hidden">
              {payments.map((p, i) => {
                const m = METHODS.find((x) => x.value === p.method)
                const Icon = m?.icon ?? Wallet
                return (
                  <div key={i} className="flex items-center justify-between px-3 py-2.5 bg-card">
                    <div className="flex items-center gap-2 text-sm">
                      <Icon className="w-4 h-4 text-muted-foreground" />
                      <span>{m?.label}</span>
                    </div>
                    <div className="flex items-center gap-3">
                      <span className="tabular-nums font-semibold text-sm">{formatCurrency(p.amount)}</span>
                      <button
                        onClick={() => setPayments((prev) => prev.filter((_, j) => j !== i))}
                        className="text-muted-foreground/50 hover:text-destructive transition-colors"
                        tabIndex={-1}
                      >
                        <X className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </div>
                )
              })}
            </div>
          )}

          {/* Summary */}
          {paidTotal > 0 && (
            <div className="space-y-1.5">
              <Separator />
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Pago</span>
                <span className="tabular-nums font-medium">{formatCurrency(paidTotal)}</span>
              </div>
              {remaining > 0 && (
                <div className="flex justify-between text-sm text-red-400">
                  <span>Falta</span>
                  <span className="tabular-nums font-semibold">{formatCurrency(remaining)}</span>
                </div>
              )}
              {change > 0 && (
                <div className="flex justify-between items-center rounded-lg bg-emerald-500/10 px-3 py-2.5">
                  <span className="text-sm font-semibold text-emerald-400">Troco</span>
                  <span className="text-2xl font-bold tabular-nums text-emerald-400">
                    {formatCurrency(change)}
                  </span>
                </div>
              )}
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="flex gap-2.5 px-5 pb-5">
          <Button
            variant="outline"
            className="flex-1 h-12"
            onClick={handleClose}
            disabled={isPending}
          >
            <span>Cancelar</span>
            <kbd className="kbd ml-2">Esc</kbd>
          </Button>
          <Button
            ref={confirmRef}
            className="flex-[2] h-12 text-base font-bold gap-2"
            onClick={handleConfirm}
            disabled={!canConfirm || isPending}
          >
            <CheckCircle2 className="w-5 h-5" />
            {isPending ? 'Processando...' : 'Confirmar Venda'}
            {canConfirm && !isPending && (
              <kbd className="kbd ml-1 bg-primary-foreground/20 border-primary-foreground/20 text-primary-foreground">↵</kbd>
            )}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  )
}
