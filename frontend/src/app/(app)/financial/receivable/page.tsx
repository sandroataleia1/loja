'use client'

import { useState, useMemo } from 'react'
import Link from 'next/link'
import { Plus, TrendingUp, AlertTriangle, CheckCircle2 } from 'lucide-react'
import { AppPageHeader }    from '@/components/shared/app-page-header'
import { Button }           from '@/components/ui/button'
import { Skeleton }         from '@/components/ui/skeleton'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { EntryStatusBadge } from '@/features/financial/components'
import { useFinancialEntries } from '@/features/financial/hooks'
import { ROUTES }           from '@/constants'
import type { FinancialEntryStatus } from '@/types/financial'

function fmt(cents: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

function fmtDate(iso: string | null) {
  if (!iso) return '—'
  return new Date(iso + 'T00:00:00').toLocaleDateString('pt-BR')
}

function KpiCard({ label, value, icon: Icon, colorClass, isLoading }: {
  label:      string
  value:      string
  icon:       React.ComponentType<{ className?: string }>
  colorClass: string
  isLoading:  boolean
}) {
  return (
    <div className="rounded-lg border bg-card p-5 flex items-center gap-4">
      <div className={`flex h-11 w-11 items-center justify-center rounded-full ${colorClass}`}>
        <Icon className="h-5 w-5" />
      </div>
      <div>
        <p className="text-sm text-muted-foreground">{label}</p>
        {isLoading
          ? <Skeleton className="mt-1 h-6 w-28" />
          : <p className="text-xl font-bold">{value}</p>
        }
      </div>
    </div>
  )
}

// ── Page ─────────────────────────────────────────────────────────────────────

export default function ReceivablePage() {
  const [status,  setStatus]  = useState<FinancialEntryStatus | ''>('')
  const [dueFrom, setDueFrom] = useState('')
  const [dueTo,   setDueTo]   = useState('')
  const [page,    setPage]    = useState(1)

  const monthRange = useMemo(() => {
    const now  = new Date()
    const y    = now.getFullYear()
    const m    = String(now.getMonth() + 1).padStart(2, '0')
    const last = new Date(y, now.getMonth() + 1, 0).getDate()
    return { from: `${y}-${m}-01`, to: `${y}-${m}-${last}` }
  }, [])

  const { data, isLoading } = useFinancialEntries({
    type: 'income', status: status || undefined,
    due_from: dueFrom || undefined, due_to: dueTo || undefined,
    per_page: 20, page,
  })

  const { data: kpiPending,  isLoading: kpiPendingLoading  } = useFinancialEntries({ type: 'income', status: 'pending',  per_page: 100 })
  const { data: kpiOverdue,  isLoading: kpiOverdueLoading  } = useFinancialEntries({ type: 'income', status: 'overdue',  per_page: 100 })
  const { data: kpiRecvdMth, isLoading: kpiRecvdMthLoading } = useFinancialEntries({
    type: 'income', status: 'paid',
    due_from: monthRange.from, due_to: monthRange.to, per_page: 100,
  })

  const totalPending = (kpiPending?.data  ?? []).reduce((s, e) => s + e.amount_cents, 0)
  const totalOverdue = (kpiOverdue?.data  ?? []).reduce((s, e) => s + e.amount_cents, 0)
  const totalRecvdMth = (kpiRecvdMth?.data ?? []).reduce((s, e) => s + e.amount_cents, 0)

  const entries = data?.data ?? []
  const meta    = data?.meta

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Contas a Receber"
        description="Acompanhe suas receitas e valores a receber."
        actions={
          <Button asChild>
            <Link href={ROUTES.FINANCIAL_RECEIVABLE + '/create'}>
              <Plus className="h-4 w-4 mr-1.5" />
              Novo lançamento
            </Link>
          </Button>
        }
      />

      {/* KPIs */}
      <div className="grid gap-4 sm:grid-cols-3">
        <KpiCard label="Total a Receber"      value={fmt(totalPending)} icon={TrendingUp}    colorClass="bg-green-100 text-green-600"   isLoading={kpiPendingLoading}  />
        <KpiCard label="Vencidas"             value={fmt(totalOverdue)} icon={AlertTriangle}  colorClass="bg-orange-100 text-orange-600" isLoading={kpiOverdueLoading}  />
        <KpiCard label="Recebidas este mês"   value={fmt(totalRecvdMth)} icon={CheckCircle2}  colorClass="bg-blue-100 text-blue-600"    isLoading={kpiRecvdMthLoading} />
      </div>

      {/* Filtros */}
      <div className="flex flex-wrap items-center gap-3">
        <select value={status}
          onChange={(e) => { setStatus(e.target.value as FinancialEntryStatus | ''); setPage(1) }}
          className="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm">
          <option value="">Todos os status</option>
          <option value="pending">Pendente</option>
          <option value="overdue">Vencido</option>
          <option value="paid">Recebido</option>
          <option value="partially_paid">Parcialmente recebido</option>
          <option value="cancelled">Cancelado</option>
        </select>
        <div className="flex items-center gap-2">
          <span className="text-sm text-muted-foreground">Venc. de</span>
          <input type="date" value={dueFrom}
            onChange={(e) => { setDueFrom(e.target.value); setPage(1) }}
            className="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm" />
          <span className="text-sm text-muted-foreground">até</span>
          <input type="date" value={dueTo}
            onChange={(e) => { setDueTo(e.target.value); setPage(1) }}
            className="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm" />
        </div>
        {(status || dueFrom || dueTo) && (
          <Button variant="ghost" size="sm"
            onClick={() => { setStatus(''); setDueFrom(''); setDueTo(''); setPage(1) }}>
            Limpar filtros
          </Button>
        )}
      </div>

      {/* Tabela */}
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Descrição</TableHead>
              <TableHead>Categoria</TableHead>
              <TableHead>Conta</TableHead>
              <TableHead>Vencimento</TableHead>
              <TableHead className="text-right">Valor</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="w-16">Ações</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <TableRow key={i}>
                  {Array.from({ length: 7 }).map((_, j) => (
                    <TableCell key={j}><Skeleton className="h-4 w-full" /></TableCell>
                  ))}
                </TableRow>
              ))
            ) : entries.length === 0 ? (
              <TableRow>
                <TableCell colSpan={7} className="text-center text-muted-foreground py-8">
                  Nenhuma conta a receber encontrada.
                </TableCell>
              </TableRow>
            ) : entries.map((entry) => (
              <TableRow key={entry.uuid}>
                <TableCell className="font-medium max-w-48 truncate">
                  {entry.description ?? <span className="text-muted-foreground">—</span>}
                </TableCell>
                <TableCell className="text-muted-foreground text-sm">{entry.category?.name ?? '—'}</TableCell>
                <TableCell className="text-muted-foreground text-sm">{entry.account?.name ?? '—'}</TableCell>
                <TableCell className={`text-sm ${entry.status === 'overdue' ? 'text-red-600 font-medium' : ''}`}>
                  {fmtDate(entry.due_date)}
                </TableCell>
                <TableCell className="text-right font-medium text-green-600">{fmt(entry.amount_cents)}</TableCell>
                <TableCell><EntryStatusBadge status={entry.status} /></TableCell>
                <TableCell>
                  <Button variant="ghost" size="sm" asChild>
                    <Link href={`/financial/entries/${entry.uuid}`}>Ver</Link>
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>{entries.length} de {meta.total} lançamentos</span>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Anterior</Button>
            <span className="px-2 py-1">{page} / {meta.last_page}</span>
            <Button variant="outline" size="sm" disabled={page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>Próximo</Button>
          </div>
        </div>
      )}
    </div>
  )
}
