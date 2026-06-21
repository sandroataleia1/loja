'use client'

import { use, useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { ChevronLeft, Trash2, RefreshCw } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import { DocumentStatusBadge } from '@/features/orders/components/document-status-badge'
import { PrintDocument } from '@/features/orders/components/print-document'
import {
  useOrder,
  useUpdateOrderStatus,
  useDeleteOrder,
} from '@/features/orders/hooks'
import { ROUTES } from '@/constants'
import type { OrderStatus } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatBRL(cents: number): string {
  return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('pt-BR')
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function OrderDetailPage({ params }: { params: Promise<{ uuid: string }> }) {
  const { uuid } = use(params)
  const router   = useRouter()

  const [confirmDelete,    setConfirmDelete]    = useState(false)
  const [statusDialogOpen, setStatusDialogOpen] = useState(false)
  const [nextStatus,       setNextStatus]       = useState<{ value: OrderStatus; label: string } | null>(null)
  const [cancelReason,     setCancelReason]     = useState('')

  const { data: order, isLoading } = useOrder(uuid)

  const { mutate: updateStatus, isPending: isUpdating } = useUpdateOrderStatus()
  const { mutate: deleteOrder,  isPending: isDeleting }  = useDeleteOrder()

  const canDelete = order && ['pending', 'cancelled'].includes(order.status)

  function handleStatusChange(transition: { value: OrderStatus; label: string }) {
    setNextStatus(transition)
    setCancelReason('')
    setStatusDialogOpen(true)
  }

  function handleStatusConfirm() {
    if (!nextStatus) return
    updateStatus(
      {
        uuid,
        status: nextStatus.value,
        cancellationReason: nextStatus.value === 'cancelled' ? cancelReason : undefined,
      },
      {
        onSuccess: () => {
          toast.success(`Status atualizado para "${nextStatus.label}".`)
          setStatusDialogOpen(false)
        },
        onError: (err) => toast.error(err instanceof Error ? err.message : 'Erro ao atualizar status.'),
      }
    )
  }

  function handleDelete() {
    deleteOrder(uuid, {
      onSuccess: () => {
        toast.success('Pedido excluído.')
        router.push(ROUTES.ORDERS)
      },
      onError: (err) => toast.error(err instanceof Error ? err.message : 'Erro ao excluir.'),
    })
  }

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-12 w-64" />
        <Skeleton className="h-48 w-full" />
        <Skeleton className="h-64 w-full" />
      </div>
    )
  }

  if (!order) {
    return (
      <div className="space-y-4">
        <p className="text-muted-foreground">Pedido não encontrado.</p>
        <Button asChild variant="outline"><Link href={ROUTES.ORDERS}>Voltar</Link></Button>
      </div>
    )
  }

  const transitions = order.allowed_transitions ?? []

  return (
    <div className="space-y-6">
      <AppPageHeader
        title={`Pedido ${order.number}`}
        description={`Criado em ${formatDate(order.created_at)}`}
        actions={
          <div className="flex flex-wrap gap-2">
            <Button variant="outline" asChild>
              <Link href={ROUTES.ORDERS}>
                <ChevronLeft className="mr-1.5 h-4 w-4" />
                Voltar
              </Link>
            </Button>

            <PrintDocument doc={order} type="order" />

            {transitions.map((t: { value: OrderStatus; label: string }) => (
              <Button
                key={t.value}
                size="sm"
                variant={t.value === 'cancelled' ? 'outline' : 'default'}
                className={t.value === 'cancelled' ? 'text-destructive border-destructive/30 hover:bg-destructive/10' : ''}
                onClick={() => handleStatusChange(t)}
              >
                <RefreshCw className="mr-2 h-4 w-4" />
                {t.label}
              </Button>
            ))}

            {canDelete && (
              <Button
                variant="outline"
                size="sm"
                className="text-destructive border-destructive/30 hover:bg-destructive/10"
                onClick={() => setConfirmDelete(true)}
              >
                <Trash2 className="mr-2 h-4 w-4" />
                Excluir
              </Button>
            )}
          </div>
        }
      />

      {/* Status + Summary */}
      <AppCard title="Resumo">
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
          <div>
            <p className="text-xs text-muted-foreground">Status</p>
            <div className="mt-1">
              <DocumentStatusBadge status={order.status} label={order.status_label} type="order" />
            </div>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Previsão</p>
            <p className="mt-1 text-sm font-medium">{formatDate(order.expected_at)}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Subtotal</p>
            <p className="mt-1 text-sm font-medium">{formatBRL(order.subtotal_cents)}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Total</p>
            <p className="mt-1 text-lg font-semibold">{formatBRL(order.total_cents)}</p>
          </div>
        </div>

        {(order.discount_cents > 0 || order.payment_terms || order.cancellation_reason) && (
          <div className="mt-4 grid grid-cols-2 gap-4 border-t pt-4 sm:grid-cols-3">
            {order.discount_cents > 0 && (
              <div>
                <p className="text-xs text-muted-foreground">Desconto</p>
                <p className="mt-1 text-sm">{formatBRL(order.discount_cents)}</p>
              </div>
            )}
            {order.payment_terms && (
              <div>
                <p className="text-xs text-muted-foreground">Condições</p>
                <p className="mt-1 text-sm">{order.payment_terms}</p>
              </div>
            )}
            {order.cancellation_reason && (
              <div>
                <p className="text-xs text-muted-foreground">Motivo do Cancelamento</p>
                <p className="mt-1 text-sm text-red-600">{order.cancellation_reason}</p>
              </div>
            )}
          </div>
        )}

        {/* Lifecycle timestamps */}
        <div className="mt-4 grid grid-cols-2 gap-2 border-t pt-4 sm:grid-cols-4 text-xs text-muted-foreground">
          {order.confirmed_at  && <span>Confirmado: {formatDate(order.confirmed_at)}</span>}
          {order.delivered_at  && <span>Entregue: {formatDate(order.delivered_at)}</span>}
          {order.completed_at  && <span>Concluído: {formatDate(order.completed_at)}</span>}
          {order.cancelled_at  && <span>Cancelado: {formatDate(order.cancelled_at)}</span>}
        </div>
      </AppCard>

      {/* Customer */}
      {order.customer && (
        <AppCard title="Cliente">
          <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
            <div>
              <p className="text-xs text-muted-foreground">Nome</p>
              <p className="mt-1 text-sm font-medium">{order.customer.name}</p>
            </div>
            {order.customer.phone && (
              <div>
                <p className="text-xs text-muted-foreground">Telefone</p>
                <p className="mt-1 text-sm">{order.customer.phone}</p>
              </div>
            )}
            {order.customer.email && (
              <div>
                <p className="text-xs text-muted-foreground">E-mail</p>
                <p className="mt-1 text-sm">{order.customer.email}</p>
              </div>
            )}
          </div>
        </AppCard>
      )}

      {/* Items */}
      <AppCard title="Itens">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50 text-left">
                <th className="px-3 py-2 font-medium text-muted-foreground">Produto</th>
                <th className="px-3 py-2 font-medium text-muted-foreground text-right">Qtd</th>
                <th className="px-3 py-2 font-medium text-muted-foreground text-right">Unit.</th>
                <th className="px-3 py-2 font-medium text-muted-foreground text-right">Desc.</th>
                <th className="px-3 py-2 font-medium text-muted-foreground text-right">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              {(order.items ?? []).map((item: import('@store/shared-types').DocumentItem) => (
                <tr key={item.uuid} className="border-b last:border-0">
                  <td className="px-3 py-2">
                    <div className="font-medium">{item.name_snapshot}</div>
                    {item.sku_snapshot && (
                      <div className="text-xs text-muted-foreground font-mono">{item.sku_snapshot}</div>
                    )}
                  </td>
                  <td className="px-3 py-2 text-right tabular-nums">{item.quantity}</td>
                  <td className="px-3 py-2 text-right tabular-nums">{formatBRL(item.unit_price_cents)}</td>
                  <td className="px-3 py-2 text-right tabular-nums text-muted-foreground">
                    {item.discount_cents > 0 ? formatBRL(item.discount_cents) : '—'}
                  </td>
                  <td className="px-3 py-2 text-right tabular-nums font-medium">
                    {formatBRL(item.subtotal_cents)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </AppCard>

      {order.notes && (
        <AppCard title="Observações">
          <p className="text-sm whitespace-pre-wrap">{order.notes}</p>
        </AppCard>
      )}

      {/* Link to origin quote */}
      {order.quote_id && (
        <div className="text-sm text-muted-foreground">
          Originado do orçamento{' '}
          <Link href={`${ROUTES.QUOTES}/${order.quote_id}`} className="text-primary underline underline-offset-2">
            Ver orçamento
          </Link>
        </div>
      )}

      {/* Status transition dialog */}
      <Dialog open={statusDialogOpen} onOpenChange={setStatusDialogOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>
              {nextStatus?.value === 'cancelled' ? 'Cancelar Pedido' : `Confirmar: ${nextStatus?.label}`}
            </DialogTitle>
          </DialogHeader>

          <div className="space-y-4 py-2">
            {nextStatus?.value === 'cancelled' ? (
              <div className="space-y-1.5">
                <Label htmlFor="cancel_reason">Motivo do cancelamento (opcional)</Label>
                <Textarea
                  id="cancel_reason"
                  rows={3}
                  value={cancelReason}
                  onChange={(e) => setCancelReason(e.target.value)}
                  placeholder="Informe o motivo…"
                />
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">
                Deseja mover o pedido para o status <strong>{nextStatus?.label}</strong>?
              </p>
            )}
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setStatusDialogOpen(false)}>Cancelar</Button>
            <Button
              onClick={handleStatusConfirm}
              disabled={isUpdating}
              variant={nextStatus?.value === 'cancelled' ? 'destructive' : 'default'}
            >
              {isUpdating ? 'Salvando…' : 'Confirmar'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete confirmation */}
      <ConfirmDialog
        open={confirmDelete}
        onOpenChange={setConfirmDelete}
        onConfirm={handleDelete}
        loading={isDeleting}
        title="Excluir pedido?"
        description="Esta ação não pode ser desfeita."
        confirmLabel="Excluir"
      />
    </div>
  )
}
