'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { DocumentStatusBadge } from '@/features/orders/components/document-status-badge'
import { useOrders } from '@/features/orders/hooks'
import { ROUTES } from '@/constants'
import type { Order } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatBRL(cents: number): string {
  return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('pt-BR')
}

// ── Status options ────────────────────────────────────────────────────────────

const STATUS_OPTIONS = [
  { value: '',            label: 'Todos os status' },
  { value: 'pending',     label: 'Pendente' },
  { value: 'confirmed',   label: 'Confirmado' },
  { value: 'in_progress', label: 'Em Andamento' },
  { value: 'ready',       label: 'Pronto' },
  { value: 'delivered',   label: 'Entregue' },
  { value: 'completed',   label: 'Concluído' },
  { value: 'cancelled',   label: 'Cancelado' },
]

// ── Row ───────────────────────────────────────────────────────────────────────

function OrderRow({ order }: { order: Order }) {
  return (
    <tr className="border-b last:border-0 hover:bg-muted/50 transition-colors">
      <td className="px-4 py-3 font-mono text-xs font-medium">{order.number}</td>
      <td className="px-4 py-3 text-sm">
        {order.customer ? (
          <span className="font-medium">{order.customer.name}</span>
        ) : (
          <span className="text-muted-foreground">—</span>
        )}
      </td>
      <td className="px-4 py-3 text-sm text-muted-foreground">{formatDate(order.created_at)}</td>
      <td className="px-4 py-3 text-sm text-muted-foreground">
        {order.expected_at ? formatDate(order.expected_at) : '—'}
      </td>
      <td className="px-4 py-3 text-sm text-right tabular-nums font-medium">
        {formatBRL(order.total_cents)}
      </td>
      <td className="px-4 py-3">
        <DocumentStatusBadge status={order.status} label={order.status_label} type="order" />
      </td>
      <td className="px-4 py-3">
        <Button variant="ghost" size="sm" asChild>
          <Link href={`${ROUTES.ORDERS}/${order.uuid}`}>Ver</Link>
        </Button>
      </td>
    </tr>
  )
}

// ── Skeleton rows ─────────────────────────────────────────────────────────────

function SkeletonRows() {
  return (
    <>
      {Array.from({ length: 5 }).map((_, i) => (
        <tr key={i} className="border-b">
          {Array.from({ length: 7 }).map((__, j) => (
            <td key={j} className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
          ))}
        </tr>
      ))}
    </>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function OrdersPage() {
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page,   setPage]   = useState(1)

  const params: Record<string, string | number> = { page, per_page: 20 }
  if (search) params.q      = search
  if (status) params.status = status

  const { data, isLoading } = useOrders(params)
  const orders = data?.data ?? []
  const meta   = data?.meta

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Pedidos"
        description="Gerencie pedidos de venda."
        actions={
          <Button asChild>
            <Link href={ROUTES.ORDERS_NEW}>
              <Plus className="mr-2 h-4 w-4" />
              Novo Pedido
            </Link>
          </Button>
        }
      />

      <div className="flex flex-wrap items-center gap-3">
        <Input
          className="w-56"
          placeholder="Buscar por número, cliente…"
          value={search}
          onChange={(e) => { setSearch(e.target.value); setPage(1) }}
        />
        <select
          value={status}
          onChange={(e) => { setStatus(e.target.value); setPage(1) }}
          className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        >
          {STATUS_OPTIONS.map((opt) => (
            <option key={opt.value} value={opt.value}>{opt.label}</option>
          ))}
        </select>
      </div>

      <div className="rounded-md border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-muted/50 text-left">
              <th className="px-4 py-3 font-medium text-muted-foreground">Número</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Cliente</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Data</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Previsão</th>
              <th className="px-4 py-3 font-medium text-muted-foreground text-right">Total</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Status</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Ações</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              <SkeletonRows />
            ) : orders.length === 0 ? (
              <tr>
                <td colSpan={7} className="px-4 py-10 text-center text-muted-foreground">
                  Nenhum pedido encontrado.
                </td>
              </tr>
            ) : (
              orders.map((o: Order) => <OrderRow key={o.uuid} order={o} />)
            )}
          </tbody>
        </table>
      </div>

      {meta && meta.total > meta.per_page && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>Mostrando {orders.length} de {meta.total}</span>
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
              Anterior
            </Button>
            <span className="px-2">{page} / {Math.ceil(meta.total / meta.per_page)}</span>
            <Button
              variant="outline"
              size="sm"
              disabled={orders.length < meta.per_page}
              onClick={() => setPage((p) => p + 1)}
            >
              Próximo
            </Button>
          </div>
        </div>
      )}
    </div>
  )
}
