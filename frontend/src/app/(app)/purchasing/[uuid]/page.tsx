'use client'

import { use, useState } from 'react'
import Link from 'next/link'
import { ChevronLeft, Send, XCircle, PackageCheck, Loader2, AlertCircle } from 'lucide-react'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard }       from '@/components/shared/app-card'
import { Skeleton }      from '@/components/ui/skeleton'
import { Button }        from '@/components/ui/button'
import { Input }         from '@/components/ui/input'
import {
  usePurchaseOrder,
  useSendPurchaseOrder,
  useCancelPurchaseOrder,
  useReceivePurchaseOrder,
} from '@/features/purchasing/hooks'
import { ROUTES } from '@/constants'
import type { PurchaseOrderItem, PurchaseOrderStatus } from '@/types/shared-types'

const STATUS_STYLES: Record<PurchaseOrderStatus, string> = {
  draft:               'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  sent:                'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
  partially_received:  'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
  received:            'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
  cancelled:           'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500',
}

const fmtBRL  = (v: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v)
const fmtDate = (d: string | null) =>
  d ? new Date(d + 'T00:00:00').toLocaleDateString('pt-BR') : '—'

// ── Receive Dialog ────────────────────────────────────────────────────────────

function ReceiveDialog({
  orderUuid,
  items,
  onClose,
}: {
  orderUuid: string
  items: PurchaseOrderItem[]
  onClose: () => void
}) {
  const receive = useReceivePurchaseOrder()
  const pendingItems = items.filter((it) => it.pending_quantity > 0)

  const [quantities, setQuantities] = useState<Record<string, number>>(
    Object.fromEntries(pendingItems.map((it) => [it.uuid, it.pending_quantity])),
  )
  const [notes, setNotes] = useState('')

  function handleConfirm() {
    const payload = pendingItems
      .filter((it) => (quantities[it.uuid] ?? 0) > 0)
      .map((it) => ({ order_item_uuid: it.uuid, quantity_received: quantities[it.uuid] }))

    if (payload.length === 0) return

    receive.mutate({ uuid: orderUuid, data: { items: payload, notes: notes || undefined } }, {
      onSuccess: () => onClose(),
    })
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div className="bg-card border rounded-2xl shadow-2xl w-full max-w-lg space-y-4 p-6">
        <div>
          <p className="font-bold text-base">Registrar Recebimento</p>
          <p className="text-sm text-muted-foreground mt-0.5">
            Informe as quantidades recebidas para cada item.
          </p>
        </div>

        <div className="overflow-auto max-h-64 border rounded-xl divide-y">
          {pendingItems.map((it) => (
            <div key={it.uuid} className="flex items-center gap-3 px-3 py-2.5">
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium truncate">{it.variant?.sku ?? it.product_variant_id.slice(0, 8)}</p>
                <p className="text-xs text-muted-foreground">
                  Pedido: {it.quantity} · Recebido: {it.received_quantity} · Pendente: {it.pending_quantity}
                </p>
              </div>
              <Input
                type="number"
                min={0}
                max={it.pending_quantity}
                value={quantities[it.uuid] ?? 0}
                onChange={(e) => setQuantities((p) => ({ ...p, [it.uuid]: parseInt(e.target.value) || 0 }))}
                className="h-8 w-20 text-center"
              />
            </div>
          ))}
        </div>

        <div className="space-y-1.5">
          <label className="text-sm text-muted-foreground">Observações</label>
          <textarea
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            rows={2}
            className="w-full rounded-lg border bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-primary/30"
            placeholder="Divergências, avarias…"
          />
        </div>

        <div className="flex justify-end gap-3">
          <Button variant="outline" onClick={onClose} disabled={receive.isPending}>Cancelar</Button>
          <Button onClick={handleConfirm} disabled={receive.isPending}>
            {receive.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Confirmar recebimento
          </Button>
        </div>
      </div>
    </div>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function PurchaseOrderDetailPage({ params }: { params: Promise<{ uuid: string }> }) {
  const { uuid } = use(params)
  const { data: order, isLoading, isError } = usePurchaseOrder(uuid)
  const sendOrder   = useSendPurchaseOrder()
  const cancelOrder = useCancelPurchaseOrder()
  const [showReceive, setShowReceive] = useState(false)

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-14 w-96 rounded-xl" />
        <Skeleton className="h-48 w-full rounded-2xl" />
        <Skeleton className="h-64 w-full rounded-2xl" />
      </div>
    )
  }

  if (isError || !order) {
    return (
      <div className="flex items-center gap-2 p-6 text-destructive text-sm">
        <AlertCircle className="w-4 h-4 shrink-0" />
        Pedido não encontrado.
      </div>
    )
  }

  const canSend    = order.status === 'draft'
  const canCancel  = order.status === 'draft' || order.status === 'sent'
  const canReceive = order.status === 'sent' || order.status === 'partially_received'
  const pendingItems = (order.items ?? []).filter((it) => it.pending_quantity > 0)

  return (
    <div className="space-y-6">
      <AppPageHeader
        title={`Pedido ${order.code}`}
        description={order.supplier?.name ?? ''}
        actions={
          <div className="flex gap-2 flex-wrap">
            <Button variant="outline" size="sm" asChild>
              <Link href={ROUTES.PURCHASING}>
                <ChevronLeft className="mr-1 h-4 w-4" />
                Voltar
              </Link>
            </Button>

            {canSend && (
              <Button
                size="sm"
                onClick={() => sendOrder.mutate(order.uuid)}
                disabled={sendOrder.isPending}
              >
                {sendOrder.isPending
                  ? <Loader2 className="mr-1.5 h-4 w-4 animate-spin" />
                  : <Send className="mr-1.5 h-4 w-4" />}
                Enviar ao fornecedor
              </Button>
            )}

            {canReceive && pendingItems.length > 0 && (
              <Button size="sm" variant="outline" onClick={() => setShowReceive(true)}>
                <PackageCheck className="mr-1.5 h-4 w-4" />
                Registrar recebimento
              </Button>
            )}

            {canCancel && (
              <Button
                size="sm"
                variant="destructive"
                onClick={() => {
                  if (!confirm('Cancelar este pedido?')) return
                  cancelOrder.mutate(order.uuid)
                }}
                disabled={cancelOrder.isPending}
              >
                {cancelOrder.isPending
                  ? <Loader2 className="mr-1.5 h-4 w-4 animate-spin" />
                  : <XCircle className="mr-1.5 h-4 w-4" />}
                Cancelar
              </Button>
            )}
          </div>
        }
      />

      {/* Resumo */}
      <AppCard>
        <div className="p-5 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
          <div>
            <p className="text-xs text-muted-foreground">Status</p>
            <span className={`inline-flex mt-1 rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_STYLES[order.status]}`}>
              {order.status_label}
            </span>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Data do pedido</p>
            <p className="font-medium mt-0.5">{fmtDate(order.order_date)}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Entrega prevista</p>
            <p className="font-medium mt-0.5">{fmtDate(order.expected_delivery_date)}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Subtotal</p>
            <p className="font-medium mt-0.5 tabular-nums">{fmtBRL(order.subtotal)}</p>
          </div>
          {order.discount > 0 && (
            <div>
              <p className="text-xs text-muted-foreground">Desconto</p>
              <p className="font-medium mt-0.5 text-destructive tabular-nums">- {fmtBRL(order.discount)}</p>
            </div>
          )}
          <div>
            <p className="text-xs text-muted-foreground">Total</p>
            <p className="font-bold mt-0.5 tabular-nums">{fmtBRL(order.total)}</p>
          </div>
          {order.notes && (
            <div className="col-span-full">
              <p className="text-xs text-muted-foreground">Observações</p>
              <p className="mt-0.5 text-sm">{order.notes}</p>
            </div>
          )}
        </div>
      </AppCard>

      {/* Itens */}
      {order.items && order.items.length > 0 && (
        <AppCard className="overflow-hidden">
          <div className="px-4 py-3 border-b">
            <p className="text-sm font-semibold">Itens do pedido</p>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-muted/30">
                  {['SKU / Variante', 'Qtd pedida', 'Custo unit.', 'Total', 'Recebido', 'Pendente'].map((h) => (
                    <th key={h} className="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {order.items.map((it) => (
                  <tr key={it.uuid} className="border-b last:border-0">
                    <td className="px-4 py-3 font-mono text-xs">{it.variant?.sku ?? it.product_variant_id.slice(0, 8)}</td>
                    <td className="px-4 py-3 tabular-nums">{it.quantity}</td>
                    <td className="px-4 py-3 tabular-nums">{fmtBRL(it.unit_cost)}</td>
                    <td className="px-4 py-3 tabular-nums font-medium">{fmtBRL(it.total_cost)}</td>
                    <td className="px-4 py-3 tabular-nums text-green-600 dark:text-green-400">
                      {it.received_quantity > 0 ? it.received_quantity : '—'}
                    </td>
                    <td className="px-4 py-3 tabular-nums">
                      {it.pending_quantity > 0
                        ? <span className="text-orange-600 dark:text-orange-400">{it.pending_quantity}</span>
                        : <span className="text-muted-foreground">—</span>}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </AppCard>
      )}

      {showReceive && order.items && (
        <ReceiveDialog
          orderUuid={order.uuid}
          items={order.items}
          onClose={() => setShowReceive(false)}
        />
      )}
    </div>
  )
}
