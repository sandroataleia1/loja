'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Plus, ShoppingBag, AlertCircle } from 'lucide-react'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard }       from '@/components/shared/app-card'
import { Skeleton }      from '@/components/ui/skeleton'
import { Button }        from '@/components/ui/button'
import { usePurchaseOrders } from '@/features/purchasing/hooks'
import { ROUTES } from '@/constants'
import type { PurchaseOrderStatus } from '@/types/shared-types'

const STATUS_STYLES: Record<PurchaseOrderStatus, string> = {
  draft:               'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  sent:                'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
  partially_received:  'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
  received:            'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
  cancelled:           'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
}

const STATUS_OPTIONS = [
  { value: '',                   label: 'Todos' },
  { value: 'draft',              label: 'Rascunho' },
  { value: 'sent',               label: 'Enviado' },
  { value: 'partially_received', label: 'Parcialmente Recebido' },
  { value: 'received',           label: 'Recebido' },
  { value: 'cancelled',          label: 'Cancelado' },
]

const fmtBRL = (v: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v)

const fmtDate = (d: string | null) =>
  d ? new Date(d + 'T00:00:00').toLocaleDateString('pt-BR') : '—'

export default function PurchasingPage() {
  const [status, setStatus] = useState('')
  const [page,   setPage]   = useState(1)

  const { data, isLoading, isError } = usePurchaseOrders({
    status:   status || undefined,
    per_page: 20,
    page,
  })

  const orders = data?.data ?? []
  const meta   = data?.meta

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Pedidos de Compra"
        description="Gerencie pedidos de compra e recebimento de mercadorias."
        actions={
          <div className="flex gap-2">
            <Button variant="outline" size="sm" asChild>
              <Link href={ROUTES.SUPPLIERS}>Fornecedores</Link>
            </Button>
            <Button size="sm" asChild>
              <Link href={ROUTES.PURCHASING_CREATE}>
                <Plus className="mr-1.5 h-3.5 w-3.5" />
                Novo Pedido
              </Link>
            </Button>
          </div>
        }
      />

      <AppCard className="overflow-hidden">
        {/* Filtros */}
        <div className="flex items-center gap-3 px-4 py-3 border-b">
          <select
            value={status}
            onChange={(e) => { setStatus(e.target.value); setPage(1) }}
            className="h-8 rounded-lg border bg-background px-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
          >
            {STATUS_OPTIONS.map((o) => (
              <option key={o.value} value={o.value}>{o.label}</option>
            ))}
          </select>
          <span className="ml-auto text-xs text-muted-foreground">
            {meta ? `${meta.total} pedido${meta.total !== 1 ? 's' : ''}` : ''}
          </span>
        </div>

        {/* Tabela */}
        {isLoading ? (
          <div className="space-y-3 p-4">
            {Array.from({ length: 8 }).map((_, i) => <Skeleton key={i} className="h-10 w-full rounded-lg" />)}
          </div>
        ) : isError ? (
          <div className="flex items-center gap-2 p-6 text-destructive text-sm">
            <AlertCircle className="w-4 h-4 shrink-0" />
            Erro ao carregar pedidos.
          </div>
        ) : orders.length === 0 ? (
          <div className="flex flex-col items-center gap-3 py-16 text-muted-foreground">
            <ShoppingBag className="w-10 h-10 opacity-30" />
            <p className="text-sm">Nenhum pedido encontrado.</p>
            <Button size="sm" asChild>
              <Link href={ROUTES.PURCHASING_CREATE}>Criar primeiro pedido</Link>
            </Button>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-muted/30">
                  {['Código', 'Fornecedor', 'Status', 'Data Pedido', 'Entrega Prevista', 'Total', ''].map((h) => (
                    <th key={h} className="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {orders.map((order) => (
                  <tr key={order.uuid} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                    <td className="px-4 py-3 font-mono text-xs font-medium">{order.code}</td>
                    <td className="px-4 py-3">{order.supplier?.name ?? '—'}</td>
                    <td className="px-4 py-3">
                      <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_STYLES[order.status]}`}>
                        {order.status_label}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-muted-foreground">{fmtDate(order.order_date)}</td>
                    <td className="px-4 py-3 text-muted-foreground">{fmtDate(order.expected_delivery_date)}</td>
                    <td className="px-4 py-3 font-medium tabular-nums">{fmtBRL(order.total)}</td>
                    <td className="px-4 py-3 text-right">
                      <Link
                        href={`${ROUTES.PURCHASING}/${order.uuid}`}
                        className="text-xs text-primary hover:underline underline-offset-2"
                      >
                        Ver detalhes →
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Paginação */}
        {meta && meta.last_page > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t text-sm">
            <button
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={page <= 1}
              className="px-3 py-1 rounded-lg border disabled:opacity-40 hover:bg-accent transition-colors"
            >
              Anterior
            </button>
            <span className="text-muted-foreground">Página {meta.current_page} de {meta.last_page}</span>
            <button
              onClick={() => setPage((p) => Math.min(meta.last_page, p + 1))}
              disabled={page >= meta.last_page}
              className="px-3 py-1 rounded-lg border disabled:opacity-40 hover:bg-accent transition-colors"
            >
              Próxima
            </button>
          </div>
        )}
      </AppCard>
    </div>
  )
}
