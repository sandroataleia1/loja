'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Plus } from 'lucide-react'
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

export default function PayablePage() {
  const [status,   setStatus]   = useState<FinancialEntryStatus | ''>('')
  const [dueFrom,  setDueFrom]  = useState('')
  const [dueTo,    setDueTo]    = useState('')
  const [page,     setPage]     = useState(1)

  const { data, isLoading } = useFinancialEntries({
    type:     'expense',
    status:   status   || undefined,
    due_from: dueFrom  || undefined,
    due_to:   dueTo    || undefined,
    per_page: 20,
    page,
  })

  const entries = data?.data ?? []
  const meta    = data?.meta

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Contas a Pagar"
        description="Gerencie suas despesas e compromissos financeiros."
        actions={
          <Button asChild>
            <Link href={ROUTES.FINANCIAL_PAYABLE + '/create'}>
              <Plus className="h-4 w-4 mr-1.5" />
              Novo lançamento
            </Link>
          </Button>
        }
      />

      {/* Filtros */}
      <div className="flex flex-wrap items-center gap-3">
        <select
          value={status}
          onChange={(e) => { setStatus(e.target.value as FinancialEntryStatus | ''); setPage(1) }}
          className="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm"
        >
          <option value="">Todos os status</option>
          <option value="pending">Pendente</option>
          <option value="overdue">Vencido</option>
          <option value="paid">Pago</option>
          <option value="partially_paid">Parcialmente pago</option>
          <option value="cancelled">Cancelado</option>
        </select>
        <div className="flex items-center gap-2">
          <span className="text-sm text-muted-foreground">Venc. de</span>
          <input
            type="date"
            value={dueFrom}
            onChange={(e) => { setDueFrom(e.target.value); setPage(1) }}
            className="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm"
          />
          <span className="text-sm text-muted-foreground">até</span>
          <input
            type="date"
            value={dueTo}
            onChange={(e) => { setDueTo(e.target.value); setPage(1) }}
            className="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm"
          />
        </div>
        {(status || dueFrom || dueTo) && (
          <Button variant="ghost" size="sm" onClick={() => { setStatus(''); setDueFrom(''); setDueTo(''); setPage(1) }}>
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
                  Nenhuma conta a pagar encontrada.
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
                <TableCell className="text-right font-medium text-red-600">{fmt(entry.amount_cents)}</TableCell>
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
