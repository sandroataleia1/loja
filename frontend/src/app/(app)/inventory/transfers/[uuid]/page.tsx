'use client'

import { use, useState } from 'react'
import Link from 'next/link'
import { ArrowLeft } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import {
  useTransfer,
  useDispatchTransfer,
  useReceiveTransfer,
  useCancelTransfer,
} from '@/features/inventory/hooks'
import { ROUTES } from '@/constants'
import { cn } from '@/lib/utils'
import type { TransferStatus, StockTransferItem } from '@store/shared-types'

// ── Status helpers ────────────────────────────────────────────────────────────

const STATUS_LABELS: Record<TransferStatus, string> = {
  pending:    'Pendente',
  in_transit: 'Em trânsito',
  received:   'Recebido',
  cancelled:  'Cancelado',
}

const STATUS_COLORS: Record<TransferStatus, string> = {
  pending:    'bg-gray-100 text-gray-700 border-gray-200',
  in_transit: 'bg-blue-100 text-blue-700 border-blue-200',
  received:   'bg-green-100 text-green-700 border-green-200',
  cancelled:  'bg-red-100 text-red-700 border-red-200',
}

// ── Inline Dispatch Form ──────────────────────────────────────────────────────

function DispatchForm({
  uuid,
  items,
}: {
  uuid:  string
  items: StockTransferItem[]
}) {
  const [quantities, setQuantities] = useState<Record<string, number>>(
    Object.fromEntries(items.map((it) => [it.variant_id, it.quantity_requested])),
  )
  const [notes, setNotes] = useState('')
  const { mutate, isPending } = useDispatchTransfer(uuid)

  function handleSubmit() {
    mutate(
      {
        items: items.map((it) => ({
          variant_id:    it.variant_id,
          quantity_sent: quantities[it.variant_id] ?? it.quantity_requested,
        })),
        notes: notes || undefined,
      },
      {
        onSuccess: () => toast.success('Transferência expedida com sucesso.'),
        onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao expedir.'),
      },
    )
  }

  return (
    <div className="rounded-md border p-4 space-y-4 mt-4">
      <p className="text-sm font-medium">Expedir transferência</p>
      <div className="space-y-2">
        {items.map((item) => (
          <div key={item.uuid} className="flex items-center gap-3">
            <span className="flex-1 text-sm font-mono text-muted-foreground">
              {item.variant?.sku ?? item.variant_id}
            </span>
            <span className="text-xs text-muted-foreground">Solicitado: {item.quantity_requested}</span>
            <Input
              type="number"
              min={0}
              max={item.quantity_requested}
              className="w-24"
              value={quantities[item.variant_id] ?? item.quantity_requested}
              onChange={(e) =>
                setQuantities((prev) => ({
                  ...prev,
                  [item.variant_id]: Number(e.target.value),
                }))
              }
            />
          </div>
        ))}
      </div>
      <div className="space-y-1">
        <p className="text-xs text-muted-foreground">Observações (opcional)</p>
        <Input
          placeholder="Observações sobre a expedição…"
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
        />
      </div>
      <Button onClick={handleSubmit} disabled={isPending} size="sm">
        {isPending ? 'Expedindo…' : 'Confirmar Expedição'}
      </Button>
    </div>
  )
}

// ── Inline Receive Form ───────────────────────────────────────────────────────

function ReceiveForm({
  uuid,
  items,
}: {
  uuid:  string
  items: StockTransferItem[]
}) {
  const [quantities, setQuantities] = useState<Record<string, number>>(
    Object.fromEntries(
      items.map((it) => [it.variant_id, it.quantity_sent ?? it.quantity_requested]),
    ),
  )
  const [notes, setNotes] = useState('')
  const { mutate, isPending } = useReceiveTransfer(uuid)

  function handleSubmit() {
    mutate(
      {
        items: items.map((it) => ({
          variant_id:        it.variant_id,
          quantity_received: quantities[it.variant_id] ?? (it.quantity_sent ?? it.quantity_requested),
        })),
        notes: notes || undefined,
      },
      {
        onSuccess: () => toast.success('Recebimento confirmado.'),
        onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao receber.'),
      },
    )
  }

  return (
    <div className="rounded-md border p-4 space-y-4 mt-4">
      <p className="text-sm font-medium">Confirmar recebimento</p>
      <div className="space-y-2">
        {items.map((item) => (
          <div key={item.uuid} className="flex items-center gap-3">
            <span className="flex-1 text-sm font-mono text-muted-foreground">
              {item.variant?.sku ?? item.variant_id}
            </span>
            <span className="text-xs text-muted-foreground">Enviado: {item.quantity_sent ?? '—'}</span>
            <Input
              type="number"
              min={0}
              className="w-24"
              value={quantities[item.variant_id] ?? item.quantity_sent ?? item.quantity_requested}
              onChange={(e) =>
                setQuantities((prev) => ({
                  ...prev,
                  [item.variant_id]: Number(e.target.value),
                }))
              }
            />
          </div>
        ))}
      </div>
      <div className="space-y-1">
        <p className="text-xs text-muted-foreground">Observações (opcional)</p>
        <Input
          placeholder="Observações sobre o recebimento…"
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
        />
      </div>
      <Button onClick={handleSubmit} disabled={isPending} size="sm">
        {isPending ? 'Confirmando…' : 'Confirmar Recebimento'}
      </Button>
    </div>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function TransferDetailPage({
  params,
}: {
  params: Promise<{ uuid: string }>
}) {
  const { uuid } = use(params)
  const [showCancelDialog, setShowCancelDialog] = useState(false)
  const [showDispatch,     setShowDispatch]     = useState(false)
  const [showReceive,      setShowReceive]      = useState(false)

  const { data: transfer, isLoading } = useTransfer(uuid)
  const { mutate: cancelTransfer, isPending: isCancelling } = useCancelTransfer(uuid)

  function handleCancel() {
    cancelTransfer(undefined, {
      onSuccess: () => {
        toast.success('Transferência cancelada.')
        setShowCancelDialog(false)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao cancelar.')
        setShowCancelDialog(false)
      },
    })
  }

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-10 w-64" />
        <Skeleton className="h-48 w-full rounded-md" />
      </div>
    )
  }

  if (!transfer) {
    return (
      <div className="py-12 text-center text-muted-foreground">
        Transferência não encontrada.
      </div>
    )
  }

  const items    = transfer.items ?? []
  const isPending    = transfer.status === 'pending'
  const isInTransit  = transfer.status === 'in_transit'
  const canCancel    = isPending || isInTransit
  const statusColor  = STATUS_COLORS[transfer.status] ?? 'bg-gray-100 text-gray-700 border-gray-200'

  return (
    <div className="space-y-6">
      <AppPageHeader
        title={`Transferência ${transfer.code ?? transfer.uuid.slice(0, 8)}`}
        actions={
          <Button variant="outline" asChild>
            <Link href={ROUTES.INVENTORY_TRANSFERS}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />

      {/* Header info */}
      <div className="rounded-md border p-4 space-y-3">
        <div className="flex flex-wrap items-center gap-4">
          <div>
            <p className="text-xs text-muted-foreground">Origem</p>
            <p className="font-medium">
              {transfer.origin_store?.name ?? transfer.origin_store_id}
            </p>
          </div>
          <span className="text-muted-foreground">→</span>
          <div>
            <p className="text-xs text-muted-foreground">Destino</p>
            <p className="font-medium">
              {transfer.destination_store?.name ?? transfer.destination_store_id}
            </p>
          </div>
          <div className="ml-auto">
            <span className={cn(
              'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border',
              statusColor,
            )}>
              {transfer.status_label ?? STATUS_LABELS[transfer.status] ?? transfer.status}
            </span>
          </div>
        </div>

        {transfer.notes && (
          <p className="text-sm text-muted-foreground">{transfer.notes}</p>
        )}

        <p className="text-xs text-muted-foreground">
          Criada em {new Date(transfer.created_at).toLocaleDateString('pt-BR')}
          {transfer.dispatched_at && ` · Expedida em ${new Date(transfer.dispatched_at).toLocaleDateString('pt-BR')}`}
          {transfer.received_at  && ` · Recebida em ${new Date(transfer.received_at).toLocaleDateString('pt-BR')}`}
          {transfer.cancelled_at && ` · Cancelada em ${new Date(transfer.cancelled_at).toLocaleDateString('pt-BR')}`}
        </p>
      </div>

      {/* Items table */}
      <div className="rounded-md border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-muted/50 text-left">
              <th className="px-4 py-3 font-medium text-muted-foreground">SKU</th>
              <th className="px-4 py-3 font-medium text-muted-foreground text-right">Solicitado</th>
              <th className="px-4 py-3 font-medium text-muted-foreground text-right">Enviado</th>
              <th className="px-4 py-3 font-medium text-muted-foreground text-right">Recebido</th>
            </tr>
          </thead>
          <tbody>
            {items.length === 0 ? (
              <tr>
                <td colSpan={4} className="px-4 py-10 text-center text-muted-foreground">
                  Nenhum item.
                </td>
              </tr>
            ) : (
              items.map((item) => (
                <tr key={item.uuid} className="border-b last:border-0 hover:bg-muted/50 transition-colors">
                  <td className="px-4 py-3 font-mono text-xs text-muted-foreground">
                    {item.variant?.sku ?? item.variant_id}
                  </td>
                  <td className="px-4 py-3 text-right tabular-nums">{item.quantity_requested}</td>
                  <td className="px-4 py-3 text-right tabular-nums text-muted-foreground">
                    {item.quantity_sent ?? '—'}
                  </td>
                  <td className="px-4 py-3 text-right tabular-nums text-muted-foreground">
                    {item.quantity_received ?? '—'}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Action buttons */}
      {(isPending || isInTransit || canCancel) && (
        <div className="flex flex-wrap gap-3">
          {isPending && (
            <Button
              variant="outline"
              onClick={() => setShowDispatch((v) => !v)}
            >
              {showDispatch ? 'Fechar' : 'Expedir'}
            </Button>
          )}

          {isInTransit && (
            <Button
              variant="outline"
              onClick={() => setShowReceive((v) => !v)}
            >
              {showReceive ? 'Fechar' : 'Receber'}
            </Button>
          )}

          {canCancel && (
            <Button
              variant="outline"
              className="text-destructive border-destructive hover:bg-destructive hover:text-destructive-foreground"
              onClick={() => setShowCancelDialog(true)}
            >
              Cancelar Transferência
            </Button>
          )}
        </div>
      )}

      {/* Inline forms */}
      {showDispatch && isPending && items.length > 0 && (
        <DispatchForm uuid={uuid} items={items} />
      )}

      {showReceive && isInTransit && items.length > 0 && (
        <ReceiveForm uuid={uuid} items={items} />
      )}

      {/* Cancel confirmation */}
      <ConfirmDialog
        open={showCancelDialog}
        onOpenChange={(open) => { if (!open) setShowCancelDialog(false) }}
        onConfirm={handleCancel}
        loading={isCancelling}
        title="Cancelar transferência?"
        description="Esta ação não pode ser desfeita. A transferência será cancelada e o estoque reservado será liberado."
        confirmLabel="Cancelar Transferência"
      />
    </div>
  )
}
