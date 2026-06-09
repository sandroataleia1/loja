'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Search } from 'lucide-react'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { Skeleton } from '@/components/ui/skeleton'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { SaleStatusBadge } from '@/features/sales/components/sale-status-badge'
import { useSales } from '@/features/sales/hooks'
import { ROUTES } from '@/constants'

function fmt(cents: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

export default function SalesPage() {
  const [status, setStatus] = useState('')
  const [page,   setPage]   = useState(1)

  const { data, isLoading } = useSales({ status: status || undefined, per_page: 20, page })
  const sales = data?.data ?? []
  const meta  = data?.meta

  return (
    <div className="space-y-6">
      <AppPageHeader title="Vendas" description="Histórico de vendas realizadas." />

      <div className="flex items-center gap-3">
        <select
          value={status}
          onChange={(e) => { setStatus(e.target.value); setPage(1) }}
          className="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm"
        >
          <option value="">Todos os status</option>
          <option value="draft">Rascunho</option>
          <option value="pending">Pendente</option>
          <option value="completed">Concluída</option>
          <option value="cancelled">Cancelada</option>
          <option value="refunded">Estornada</option>
        </select>
      </div>

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Código</TableHead>
              <TableHead>Cliente</TableHead>
              <TableHead>Loja</TableHead>
              <TableHead className="text-right">Total</TableHead>
              <TableHead>Fiscal</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Data</TableHead>
              <TableHead className="w-16">Ações</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <TableRow key={i}>{Array.from({ length: 8 }).map((_, j) => <TableCell key={j}><Skeleton className="h-4 w-full" /></TableCell>)}</TableRow>
              ))
            ) : sales.length === 0 ? (
              <TableRow><TableCell colSpan={8} className="text-center text-muted-foreground py-8">Nenhuma venda encontrada.</TableCell></TableRow>
            ) : sales.map((s) => (
              <TableRow key={s.uuid}>
                <TableCell className="font-mono text-sm">{s.code ?? s.uuid.slice(0, 8)}</TableCell>
                <TableCell>{s.customer?.name ?? 'Consumidor Final'}</TableCell>
                <TableCell className="text-muted-foreground text-sm">{s.store?.name ?? '—'}</TableCell>
                <TableCell className="text-right font-medium">{fmt(s.total_cents)}</TableCell>
                <TableCell><span className="text-xs text-muted-foreground capitalize">{s.fiscal_status}</span></TableCell>
                <TableCell><SaleStatusBadge status={s.status} /></TableCell>
                <TableCell className="text-sm text-muted-foreground">{new Date(s.created_at).toLocaleDateString('pt-BR')}</TableCell>
                <TableCell><Button variant="ghost" size="sm" asChild><Link href={`${ROUTES.SALES}/${s.uuid}`}>Ver</Link></Button></TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>{sales.length} de {meta.total} vendas</span>
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
