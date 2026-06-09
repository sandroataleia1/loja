'use client'

import { use } from 'react'
import { useCashSession, useSessionMovements, useCloseSession } from '@/features/sales/hooks'
import { SessionStatusBadge } from '@/features/sales/components/session-status-badge'
import { Skeleton } from '@/components/ui/skeleton'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { toast } from 'sonner'
import { useState } from 'react'

function fmt(cents: number | null) {
  if (cents == null) return '—'
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

function fmtDate(iso: string | null) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })
}

const MOVEMENT_LABELS: Record<string, string> = {
  opening: 'Abertura', sale: 'Venda', withdrawal: 'Sangria',
  supply: 'Suprimento', refund: 'Estorno', adjustment: 'Ajuste', closing: 'Fechamento',
}

export default function SessionDetailPage({ params }: { params: Promise<{ uuid: string }> }) {
  const { uuid } = use(params)
  const { data: session, isLoading } = useCashSession(uuid)
  const { data: movements = [] }     = useSessionMovements(uuid)
  const { mutate: close, isPending } = useCloseSession(uuid)
  const [closingAmount, setClosingAmount] = useState('')

  if (isLoading) return <div className="space-y-4">{Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-12 w-full" />)}</div>
  if (!session) return <p className="text-muted-foreground">Sessão não encontrada.</p>

  const diff = session.difference_amount_cents
  const diffColor = diff === null ? '' : diff === 0 ? 'text-green-600' : diff > 0 ? 'text-blue-600' : 'text-red-600'

  function handleClose() {
    const normalized = closingAmount.trim().replace(',', '.')
    const parsed = parseFloat(normalized)
    if (!normalized || isNaN(parsed) || parsed < 0) {
      toast.error('Informe um valor válido para o fechamento')
      return
    }
    const cents = Math.round(parsed * 100)
    close({ closing_amount_cents: cents }, {
      onSuccess: () => toast.success('Sessão fechada.'),
      onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao fechar sessão.'),
    })
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">{session.cash_register?.name ?? 'Sessão de Caixa'}</h1>
          <p className="text-muted-foreground text-sm">{session.store?.name}</p>
        </div>
        <SessionStatusBadge status={session.status} />
      </div>

      {/* Summary */}
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        {[
          { label: 'Abertura',  value: fmtDate(session.opened_at) },
          { label: 'Fechamento', value: fmtDate(session.closed_at) },
          { label: 'Saldo Inicial', value: fmt(session.opening_amount_cents) },
          { label: 'Saldo Final', value: fmt(session.closing_amount_cents) },
        ].map(({ label, value }) => (
          <div key={label} className="rounded-lg border p-4">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="font-semibold">{value}</p>
          </div>
        ))}
      </div>

      {session.expected_balance_cents != null && (
        <div className="rounded-lg border p-4 space-y-1">
          <p className="text-sm font-medium">Reconciliação</p>
          <p className="text-sm">Esperado: {fmt(session.expected_balance_cents)}</p>
          {diff !== null && (
            <p className={`text-sm font-medium ${diffColor}`}>
              Diferença: {diff > 0 ? '+' : ''}{fmt(diff)}
            </p>
          )}
        </div>
      )}

      {/* Close form */}
      {session.status === 'open' && (
        <div className="rounded-lg border p-4 space-y-3">
          <p className="font-medium">Fechar Sessão</p>
          <div className="flex items-center gap-3">
            <div className="relative w-48">
              <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">R$</span>
              <input
                type="text"
                inputMode="decimal"
                value={closingAmount}
                onChange={(e) => {
                  // aceita apenas dígitos e vírgula/ponto
                  const v = e.target.value.replace(/[^\d,\.]/g, '')
                  setClosingAmount(v)
                }}
                placeholder="0,00"
                aria-label="Valor contado no fechamento"
                className="flex h-9 w-full rounded-md border border-input bg-background pl-9 pr-3 text-sm"
              />
            </div>
            <Button onClick={handleClose} disabled={isPending}>
              {isPending ? 'Fechando...' : 'Fechar Caixa'}
            </Button>
          </div>
        </div>
      )}

      {/* Movements */}
      <div>
        <h2 className="text-lg font-semibold mb-3">Movimentações</h2>
        <div className="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Tipo</TableHead>
                <TableHead>Descrição</TableHead>
                <TableHead className="text-right">Valor</TableHead>
                <TableHead>Data</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {movements.length === 0 ? (
                <TableRow><TableCell colSpan={4} className="text-center text-muted-foreground py-6">Sem movimentações.</TableCell></TableRow>
              ) : movements.map((m) => (
                <TableRow key={m.uuid}>
                  <TableCell className="font-medium">{MOVEMENT_LABELS[m.type] ?? m.type}</TableCell>
                  <TableCell className="text-muted-foreground text-sm">{m.description}</TableCell>
                  <TableCell className={`text-right font-mono ${m.amount_cents < 0 ? 'text-red-600' : 'text-green-600'}`}>
                    {m.amount_cents >= 0 ? '+' : ''}{fmt(m.amount_cents)}
                  </TableCell>
                  <TableCell className="text-sm text-muted-foreground">{fmtDate(m.created_at)}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      </div>
    </div>
  )
}
