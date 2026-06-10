'use client'

import { use, useState } from 'react'
import Link from 'next/link'
import { ChevronLeft, RotateCcw, ShoppingCart, XCircle, ExternalLink } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { ConditionalStatusBadge } from '@/features/conditional/components/conditional-status-badge'
import { ConditionalItemTable } from '@/features/conditional/components/conditional-item-table'
import { ConditionalTimeline } from '@/features/conditional/components/conditional-timeline'
import {
  useConditional,
  useReturnConditional,
  useConvertConditional,
  useCancelConditional,
} from '@/features/conditional/hooks'
import { ROUTES } from '@/constants'
import type { ConditionalItem, ConditionalStatus } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatBRL(amount: number): string {
  return amount.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('pt-BR')
}

function getPendingQty(item: ConditionalItem): number {
  if (item.pending_quantity !== undefined) return item.pending_quantity
  return Math.max(0, item.quantity - item.returned_quantity - item.sold_quantity)
}

function canReturn(status: ConditionalStatus): boolean {
  return ['open', 'partially_returned', 'overdue', 'partially_converted'].includes(status)
}

function canConvert(status: ConditionalStatus): boolean {
  return ['open', 'partially_converted', 'overdue', 'partially_returned'].includes(status)
}

function canCancel(status: ConditionalStatus): boolean {
  return ['open', 'overdue'].includes(status)
}

// ── Item qty input for modals ─────────────────────────────────────────────────

interface ItemQtyRow {
  item: ConditionalItem
  value: number
  onChange: (v: number) => void
}

function ItemQtyRow({ item, value, onChange }: ItemQtyRow) {
  const pending = getPendingQty(item)
  const productName = item.variant?.product?.name ?? item.variant?.name ?? '—'
  const sku = item.variant?.sku ?? '—'

  return (
    <div className="flex items-center gap-3 py-2 border-b last:border-0">
      <div className="flex-1 min-w-0">
        <p className="text-sm font-medium truncate">{productName}</p>
        <p className="text-xs font-mono text-muted-foreground">{sku} — {pending} pendente(s)</p>
      </div>
      <Input
        type="number"
        min={0}
        max={pending}
        value={value}
        onChange={(e) => onChange(Math.min(pending, Math.max(0, Number(e.target.value))))}
        className="h-8 w-20 text-sm shrink-0"
      />
    </div>
  )
}

// ── Tabs ──────────────────────────────────────────────────────────────────────

type Tab = 'items' | 'history' | 'movements'

// ── Page content ──────────────────────────────────────────────────────────────

interface PageParams {
  params: Promise<{ uuid: string }>
}

function ConditionalDetailContent({ uuid }: { uuid: string }) {
  const { data: conditional, isLoading, isError } = useConditional(uuid)
  const { mutate: doReturn,  isPending: isReturning  } = useReturnConditional(uuid)
  const { mutate: doConvert, isPending: isConverting } = useConvertConditional(uuid)
  const { mutate: doCancel,  isPending: isCancelling } = useCancelConditional(uuid)

  const [activeTab, setActiveTab] = useState<Tab>('items')
  const [showCancelConfirm, setShowCancelConfirm] = useState(false)
  const [showReturnModal,   setShowReturnModal]   = useState(false)
  const [showConvertModal,  setShowConvertModal]  = useState(false)

  // qty maps for modals
  const [returnQtys,  setReturnQtys]  = useState<Record<string, number>>({})
  const [convertQtys, setConvertQtys] = useState<Record<string, number>>({})

  // ── Loading ─────────────────────────────────────────────────────────────────
  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-32 w-full rounded-lg" />
        <Skeleton className="h-64 w-full rounded-lg" />
      </div>
    )
  }

  if (isError || !conditional) {
    return (
      <p className="text-center text-sm text-muted-foreground py-12">
        Condicional não encontrado.
      </p>
    )
  }

  const items   = conditional.items   ?? []
  const history = conditional.status_history ?? []

  const pendingItems = items.filter((item) => getPendingQty(item) > 0)

  // ── Init modal qty maps ─────────────────────────────────────────────────────
  function openReturnModal() {
    const map: Record<string, number> = {}
    pendingItems.forEach((item) => { map[item.uuid] = getPendingQty(item) })
    setReturnQtys(map)
    setShowReturnModal(true)
  }

  function openConvertModal() {
    const map: Record<string, number> = {}
    pendingItems.forEach((item) => { map[item.uuid] = getPendingQty(item) })
    setConvertQtys(map)
    setShowConvertModal(true)
  }

  // ── Submit return ───────────────────────────────────────────────────────────
  function handleReturnSubmit() {
    const returns = Object.entries(returnQtys)
      .filter(([, qty]) => qty > 0)
      .map(([item_uuid, quantity]) => ({ item_uuid, quantity }))

    if (returns.length === 0) {
      toast.error('Informe ao menos uma quantidade para devolver.')
      return
    }

    doReturn({ returns }, {
      onSuccess: () => {
        toast.success('Devolução registrada com sucesso.')
        setShowReturnModal(false)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao registrar devolução.')
      },
    })
  }

  // ── Submit convert ──────────────────────────────────────────────────────────
  function handleConvertSubmit() {
    const conversions = Object.entries(convertQtys)
      .filter(([, qty]) => qty > 0)
      .map(([item_uuid, quantity]) => ({ item_uuid, quantity }))

    if (conversions.length === 0) {
      toast.error('Informe ao menos uma quantidade para converter.')
      return
    }

    doConvert({ conversions }, {
      onSuccess: () => {
        toast.success('Itens convertidos em venda com sucesso.')
        setShowConvertModal(false)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao converter itens.')
      },
    })
  }

  // ── Submit cancel ───────────────────────────────────────────────────────────
  function handleCancelConfirm() {
    doCancel(undefined, {
      onSuccess: () => {
        toast.success('Condicional cancelado.')
        setShowCancelConfirm(false)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao cancelar.')
        setShowCancelConfirm(false)
      },
    })
  }

  const tabs: { key: Tab; label: string }[] = [
    { key: 'items',     label: 'Itens' },
    { key: 'history',   label: 'Histórico' },
    { key: 'movements', label: 'Movimentações' },
  ]

  return (
    <div className="space-y-6">

      {/* Header card */}
      <AppCard>
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="space-y-1">
            <div className="flex items-center gap-3 flex-wrap">
              <h2 className="text-xl font-bold font-mono">{conditional.code}</h2>
              <ConditionalStatusBadge
                status={conditional.status}
                label={conditional.status_label}
              />
              {conditional.is_overdue && (
                <span className="text-xs font-medium text-red-600 bg-red-50 border border-red-200 px-2 py-0.5 rounded-full">
                  Vencido
                </span>
              )}
            </div>

            <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
              <span>
                Vencimento:{' '}
                <span className={conditional.is_overdue ? 'text-red-600 font-medium' : ''}>
                  {formatDate(conditional.due_date ?? conditional.expires_at)}
                </span>
              </span>
              <span>Total: <span className="font-semibold text-foreground">{formatBRL(conditional.total_amount)}</span></span>
              <span>Itens: {conditional.total_items}</span>
            </div>
          </div>

          {/* Action buttons */}
          <div className="flex flex-wrap gap-2">
            {canReturn(conditional.status) && pendingItems.length > 0 && (
              <Button variant="outline" size="sm" onClick={openReturnModal}>
                <RotateCcw className="mr-1.5 h-4 w-4" />
                Registrar Devolução
              </Button>
            )}
            {canConvert(conditional.status) && pendingItems.length > 0 && (
              <Button size="sm" onClick={openConvertModal}>
                <ShoppingCart className="mr-1.5 h-4 w-4" />
                Converter em Venda
              </Button>
            )}
            {canCancel(conditional.status) && (
              <Button variant="destructive" size="sm" onClick={() => setShowCancelConfirm(true)}>
                <XCircle className="mr-1.5 h-4 w-4" />
                Cancelar
              </Button>
            )}
          </div>
        </div>

        {/* Customer + Store info */}
        <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 border-t pt-4">
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">Cliente</p>
            {conditional.customer ? (
              <>
                <p className="text-sm font-medium">{conditional.customer.name}</p>
                <p className="text-xs font-mono text-muted-foreground">{conditional.customer.code}</p>
              </>
            ) : (
              <p className="text-sm text-muted-foreground">—</p>
            )}
          </div>
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">Loja</p>
            {conditional.store ? (
              <p className="text-sm font-medium">{conditional.store.name}</p>
            ) : (
              <p className="text-sm text-muted-foreground">—</p>
            )}
          </div>
        </div>

        {conditional.notes && (
          <div className="mt-4 border-t pt-4">
            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">Observações</p>
            <p className="text-sm whitespace-pre-wrap">{conditional.notes}</p>
          </div>
        )}
      </AppCard>

      {/* Tabs */}
      <div>
        <div className="border-b flex gap-1 mb-4">
          {tabs.map((tab) => (
            <button
              key={tab.key}
              type="button"
              onClick={() => setActiveTab(tab.key)}
              className={[
                'px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px',
                activeTab === tab.key
                  ? 'border-primary text-primary'
                  : 'border-transparent text-muted-foreground hover:text-foreground',
              ].join(' ')}
            >
              {tab.label}
            </button>
          ))}
        </div>

        {/* Items tab */}
        {activeTab === 'items' && (
          <ConditionalItemTable items={items} readonly />
        )}

        {/* History tab */}
        {activeTab === 'history' && (
          <AppCard title="Histórico de Status">
            <ConditionalTimeline history={history} />
          </AppCard>
        )}

        {/* Movements tab */}
        {activeTab === 'movements' && (
          <AppCard title="Movimentações">
            <p className="text-sm text-muted-foreground mb-3">
              Visualize todas as movimentações de estoque relacionadas a este condicional.
            </p>
            <Button variant="outline" size="sm" asChild>
              <Link href={`${ROUTES.INVENTORY_MOVEMENTS}?reference_id=${uuid}`}>
                <ExternalLink className="mr-1.5 h-4 w-4" />
                Ver movimentações no estoque
              </Link>
            </Button>
          </AppCard>
        )}
      </div>

      {/* Cancel confirm */}
      <ConfirmDialog
        open={showCancelConfirm}
        onOpenChange={(open) => { if (!open) setShowCancelConfirm(false) }}
        onConfirm={handleCancelConfirm}
        loading={isCancelling}
        title="Cancelar condicional?"
        description="Esta ação não pode ser desfeita. O condicional será marcado como cancelado e os itens pendentes serão liberados do estoque."
        confirmLabel="Cancelar condicional"
        variant="destructive"
      />

      {/* Return modal */}
      <Dialog open={showReturnModal} onOpenChange={setShowReturnModal}>
        <DialogContent className="sm:max-w-[520px]">
          <DialogHeader>
            <DialogTitle>Registrar Devolução</DialogTitle>
          </DialogHeader>
          <div className="py-2 max-h-72 overflow-y-auto">
            {pendingItems.map((item) => (
              <ItemQtyRow
                key={item.uuid}
                item={item}
                value={returnQtys[item.uuid] ?? 0}
                onChange={(v) => setReturnQtys((prev) => ({ ...prev, [item.uuid]: v }))}
              />
            ))}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowReturnModal(false)} disabled={isReturning}>
              Cancelar
            </Button>
            <Button onClick={handleReturnSubmit} disabled={isReturning}>
              {isReturning ? 'Registrando…' : 'Confirmar Devolução'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Convert modal */}
      <Dialog open={showConvertModal} onOpenChange={setShowConvertModal}>
        <DialogContent className="sm:max-w-[520px]">
          <DialogHeader>
            <DialogTitle>Converter em Venda</DialogTitle>
          </DialogHeader>
          <div className="py-2 max-h-72 overflow-y-auto">
            {pendingItems.map((item) => (
              <ItemQtyRow
                key={item.uuid}
                item={item}
                value={convertQtys[item.uuid] ?? 0}
                onChange={(v) => setConvertQtys((prev) => ({ ...prev, [item.uuid]: v }))}
              />
            ))}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowConvertModal(false)} disabled={isConverting}>
              Cancelar
            </Button>
            <Button onClick={handleConvertSubmit} disabled={isConverting}>
              {isConverting ? 'Convertendo…' : 'Confirmar Conversão'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}

export default function ConditionalDetailPage({ params }: PageParams) {
  const { uuid } = use(params)

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Detalhes do Condicional"
        actions={
          <Button variant="outline" asChild>
            <Link href={ROUTES.CONDITIONALS}>
              <ChevronLeft className="mr-1.5 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />
      <ConditionalDetailContent uuid={uuid} />
    </div>
  )
}
