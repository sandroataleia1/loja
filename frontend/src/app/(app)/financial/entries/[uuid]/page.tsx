'use client'

import { useState } from 'react'
import { useParams } from 'next/navigation'
import Link from 'next/link'
import { ChevronLeft, CreditCard, XCircle } from 'lucide-react'
import { toast }            from 'sonner'
import { AppPageHeader }    from '@/components/shared/app-page-header'
import { AppCard }          from '@/components/shared/app-card'
import { Button }           from '@/components/ui/button'
import { Input }            from '@/components/ui/input'
import { Label }            from '@/components/ui/label'
import { Skeleton }         from '@/components/ui/skeleton'
import { EntryStatusBadge, EntryTypeBadge } from '@/features/financial/components'
import { useFinancialEntry, usePayEntry, useCancelEntry, useFinancialAccounts } from '@/features/financial/hooks'
import { ROUTES } from '@/constants'

function fmt(cents: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

function fmtDate(iso: string | null) {
  if (!iso) return '—'
  return new Date(iso + 'T00:00:00').toLocaleDateString('pt-BR')
}

function fmtDateTime(iso: string | null) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('pt-BR')
}

export default function EntryDetailPage() {
  const { uuid } = useParams<{ uuid: string }>()

  const { data: entry, isLoading } = useFinancialEntry(uuid)
  const { data: accounts = [] }    = useFinancialAccounts()
  const { mutate: payEntry,    isPending: paying    } = usePayEntry(uuid)
  const { mutate: cancelEntry, isPending: cancelling } = useCancelEntry(uuid)

  const [showPayDialog,    setShowPayDialog]    = useState(false)
  const [showCancelDialog, setShowCancelDialog] = useState(false)
  const [payAccountId,     setPayAccountId]     = useState('')
  const [payAmountStr,     setPayAmountStr]      = useState('')
  const [payDate,          setPayDate]          = useState(new Date().toISOString().split('T')[0])
  const [cancelReason,     setCancelReason]      = useState('')

  function handlePay(e: React.FormEvent) {
    e.preventDefault()
    const amountCents = payAmountStr ? Math.round(parseFloat(payAmountStr.replace(',', '.')) * 100) : undefined
    payEntry({
      financial_account_id: payAccountId || undefined,
      amount_cents:         amountCents,
      paid_at:              payDate || undefined,
    }, {
      onSuccess: () => {
        toast.success('Pagamento registrado com sucesso.')
        setShowPayDialog(false)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao registrar pagamento.')
      },
    })
  }

  function handleCancel(e: React.FormEvent) {
    e.preventDefault()
    cancelEntry(cancelReason || undefined, {
      onSuccess: () => {
        toast.success('Lançamento cancelado.')
        setShowCancelDialog(false)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao cancelar.')
      },
    })
  }

  const backHref = entry?.type === 'expense' ? ROUTES.FINANCIAL_PAYABLE : ROUTES.FINANCIAL_RECEIVABLE
  const canPay   = entry && ['pending', 'overdue', 'partially_paid'].includes(entry.status)
  const canCancel = entry && entry.status !== 'cancelled'

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-10 w-64" />
        <div className="grid gap-4 sm:grid-cols-2">
          <Skeleton className="h-40" />
          <Skeleton className="h-40" />
        </div>
      </div>
    )
  }

  if (!entry) {
    return (
      <div className="space-y-6">
        <AppPageHeader title="Lançamento não encontrado" />
        <Button variant="outline" asChild>
          <Link href={ROUTES.FINANCIAL}>Voltar ao financeiro</Link>
        </Button>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title={entry.description ?? 'Lançamento financeiro'}
        description={`Criado em ${fmtDateTime(entry.created_at)}`}
        actions={
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link href={backHref}>
                <ChevronLeft className="mr-1.5 h-4 w-4" />
                Voltar
              </Link>
            </Button>
            {canPay && (
              <Button onClick={() => setShowPayDialog(true)}>
                <CreditCard className="mr-1.5 h-4 w-4" />
                Registrar Pagamento
              </Button>
            )}
            {canCancel && (
              <Button variant="destructive" onClick={() => setShowCancelDialog(true)}>
                <XCircle className="mr-1.5 h-4 w-4" />
                Cancelar
              </Button>
            )}
          </div>
        }
      />

      <div className="grid gap-6 sm:grid-cols-2">
        {/* Dados principais */}
        <AppCard title="Informações">
          <dl className="space-y-3 text-sm">
            <div className="flex justify-between">
              <dt className="text-muted-foreground">Tipo</dt>
              <dd><EntryTypeBadge type={entry.type} /></dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-muted-foreground">Status</dt>
              <dd><EntryStatusBadge status={entry.status} /></dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-muted-foreground">Valor</dt>
              <dd className={`font-semibold ${entry.type === 'income' ? 'text-green-600' : 'text-red-600'}`}>
                {fmt(entry.amount_cents)}
              </dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-muted-foreground">Vencimento</dt>
              <dd className={entry.status === 'overdue' ? 'text-red-600 font-medium' : ''}>{fmtDate(entry.due_date)}</dd>
            </div>
            {entry.paid_at && (
              <div className="flex justify-between">
                <dt className="text-muted-foreground">Pago em</dt>
                <dd>{fmtDateTime(entry.paid_at)}</dd>
              </div>
            )}
            <div className="flex justify-between">
              <dt className="text-muted-foreground">Categoria</dt>
              <dd>{entry.category?.name ?? '—'}</dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-muted-foreground">Conta</dt>
              <dd>{entry.account?.name ?? '—'}</dd>
            </div>
            {entry.external_reference && (
              <div className="flex justify-between">
                <dt className="text-muted-foreground">Referência</dt>
                <dd className="font-mono text-xs">{entry.external_reference}</dd>
              </div>
            )}
          </dl>
        </AppCard>

        {/* Parcelas */}
        {entry.installments && entry.installments.length > 0 && (
          <AppCard title={`Parcelas (${entry.installments.length})`}>
            <div className="space-y-2">
              {entry.installments.map((inst) => (
                <div key={inst.uuid} className="flex items-center justify-between py-1.5 border-b last:border-0 text-sm">
                  <div>
                    <span className="font-medium">Parcela {inst.installment_number}</span>
                    <span className="text-muted-foreground ml-2">{fmtDate(inst.due_date)}</span>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className="font-medium">{fmt(inst.amount_cents)}</span>
                    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium border ${
                      inst.status === 'paid'     ? 'bg-green-100 text-green-700 border-green-200' :
                      inst.status === 'overdue'  ? 'bg-red-100 text-red-700 border-red-200'       :
                      inst.status === 'cancelled'? 'bg-gray-100 text-gray-600 border-gray-200'    :
                                                   'bg-yellow-100 text-yellow-700 border-yellow-200'
                    }`}>
                      {inst.status_label}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </AppCard>
        )}
      </div>

      {/* Dialog: Registrar Pagamento */}
      {showPayDialog && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="w-full max-w-md rounded-lg border bg-background p-6 shadow-lg">
            <h2 className="text-lg font-semibold mb-4">Registrar Pagamento</h2>
            <form onSubmit={handlePay} className="space-y-4">
              <div className="grid gap-1.5">
                <Label htmlFor="pay_date">Data do pagamento</Label>
                <Input id="pay_date" type="date" value={payDate} onChange={(e) => setPayDate(e.target.value)} />
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="pay_amount">Valor pago (R$) — deixe em branco para valor total</Label>
                <Input id="pay_amount" value={payAmountStr} onChange={(e) => setPayAmountStr(e.target.value)} placeholder={`${fmt(entry.amount_cents)}`} />
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="pay_account">Conta</Label>
                <select
                  id="pay_account"
                  value={payAccountId}
                  onChange={(e) => setPayAccountId(e.target.value)}
                  className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                >
                  <option value="">Selecionar conta...</option>
                  {accounts.filter((a) => a.is_active).map((a) => <option key={a.uuid} value={a.uuid}>{a.name}</option>)}
                </select>
              </div>
              <div className="flex gap-3 pt-2">
                <Button type="submit" disabled={paying}>{paying ? 'Salvando...' : 'Confirmar pagamento'}</Button>
                <Button type="button" variant="outline" onClick={() => setShowPayDialog(false)}>Cancelar</Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Dialog: Cancelar */}
      {showCancelDialog && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="w-full max-w-md rounded-lg border bg-background p-6 shadow-lg">
            <h2 className="text-lg font-semibold mb-2">Cancelar lançamento</h2>
            <p className="text-sm text-muted-foreground mb-4">Esta ação não pode ser desfeita.</p>
            <form onSubmit={handleCancel} className="space-y-4">
              <div className="grid gap-1.5">
                <Label htmlFor="cancel_reason">Motivo (opcional)</Label>
                <Input id="cancel_reason" value={cancelReason} onChange={(e) => setCancelReason(e.target.value)} placeholder="Informe o motivo do cancelamento..." />
              </div>
              <div className="flex gap-3">
                <Button type="submit" variant="destructive" disabled={cancelling}>{cancelling ? 'Cancelando...' : 'Confirmar cancelamento'}</Button>
                <Button type="button" variant="outline" onClick={() => setShowCancelDialog(false)}>Voltar</Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
