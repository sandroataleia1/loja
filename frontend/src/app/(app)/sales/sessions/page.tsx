'use client'

import { useState } from 'react'
import Link from 'next/link'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { Skeleton } from '@/components/ui/skeleton'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { SessionStatusBadge } from '@/features/sales/components/session-status-badge'
import { useCashSessions } from '@/features/sales/hooks'
import { ROUTES } from '@/constants'

function fmt(cents: number | null) {
  if (cents == null) return '—'
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

function fmtDate(iso: string | null) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })
}

export default function CashSessionsPage() {
  const [status, setStatus] = useState('')
  const [page, setPage]     = useState(1)

  const { data, isLoading } = useCashSessions({ status: status || undefined, page, per_page: 20 })
  const sessions = data?.data ?? []
  const meta     = data?.meta

  return (
    <div className="space-y-6">
      <AppPageHeader title="Sessões de Caixa" description="Histórico de aberturas e fechamentos de caixa." />

      <div className="flex items-center gap-3">
        <select
          value={status}
          onChange={(e) => { setStatus(e.target.value); setPage(1) }}
          className="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm"
        >
          <option value="">Todos os status</option>
          <option value="open">Aberta</option>
          <option value="closed">Fechada</option>
          <option value="cancelled">Cancelada</option>
        </select>
      </div>

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Caixa</TableHead>
              <TableHead>Abertura</TableHead>
              <TableHead>Fechamento</TableHead>
              <TableHead>Saldo Inicial</TableHead>
              <TableHead>Saldo Final</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="w-20">Ações</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              Array.from({ length: 4 }).map((_, i) => (
                <TableRow key={i}>
                  {Array.from({ length: 7 }).map((_, j) => (
                    <TableCell key={j}><Skeleton className="h-4 w-full" /></TableCell>
                  ))}
                </TableRow>
              ))
            ) : sessions.length === 0 ? (
              <TableRow>
                <TableCell colSpan={7} className="text-center text-muted-foreground py-8">
                  Nenhuma sessão encontrada.
                </TableCell>
              </TableRow>
            ) : sessions.map((s) => (
              <TableRow key={s.uuid}>
                <TableCell className="font-medium">{s.cash_register?.name ?? '—'}</TableCell>
                <TableCell className="text-sm">{fmtDate(s.opened_at)}</TableCell>
                <TableCell className="text-sm">{fmtDate(s.closed_at)}</TableCell>
                <TableCell>{fmt(s.opening_amount_cents)}</TableCell>
                <TableCell>{fmt(s.closing_amount_cents)}</TableCell>
                <TableCell><SessionStatusBadge status={s.status} /></TableCell>
                <TableCell>
                  <Button variant="ghost" size="sm" asChild>
                    <Link href={`${ROUTES.CASH_SESSIONS}/${s.uuid}`}>Ver</Link>
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>{sessions.length} de {meta.total} sessões</span>
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
