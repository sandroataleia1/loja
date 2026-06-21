'use client'

import { use, useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { ChevronLeft, Send, ArrowRight, Trash2 } from 'lucide-react'
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
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import { DocumentStatusBadge } from '@/features/orders/components/document-status-badge'
import { PrintDocument } from '@/features/orders/components/print-document'
import { WhatsAppShare } from '@/features/orders/components/whatsapp-share'
import {
  useQuote,
  useMarkQuoteSent,
  useConvertQuote,
  useDeleteQuote,
} from '@/features/orders/hooks'
import { ROUTES } from '@/constants'

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatBRL(cents: number): string {
  return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('pt-BR')
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function QuoteDetailPage({ params }: { params: Promise<{ uuid: string }> }) {
  const { uuid }  = use(params)
  const router    = useRouter()

  const [confirmDelete,  setConfirmDelete]  = useState(false)
  const [convertOpen,    setConvertOpen]    = useState(false)
  const [expectedAt,     setExpectedAt]     = useState('')

  const { data: quote, isLoading } = useQuote(uuid)

  const { mutate: markSent,  isPending: isSending }    = useMarkQuoteSent()
  const { mutate: convert,   isPending: isConverting } = useConvertQuote()
  const { mutate: deleteDoc, isPending: isDeleting }   = useDeleteQuote()

  const isEditable  = quote && ['draft', 'sent', 'viewed'].includes(quote.status)
  const canConvert  = quote && ['draft', 'sent', 'viewed', 'accepted'].includes(quote.status)
  const canDelete   = quote && ['draft', 'cancelled'].includes(quote.status)
  const canMarkSent = quote && quote.status === 'draft'

  function handleMarkSent() {
    markSent(uuid, {
      onSuccess: () => toast.success('Orçamento marcado como enviado.'),
      onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro.'),
    })
  }

  function handleConvert() {
    convert({ uuid, expectedAt: expectedAt || undefined }, {
      onSuccess: (order) => {
        toast.success('Orçamento convertido em pedido.')
        router.push(`${ROUTES.ORDERS}/${order.uuid}`)
      },
      onError: (err) => toast.error(err instanceof Error ? err.message : 'Erro ao converter.'),
    })
  }

  function handleDelete() {
    deleteDoc(uuid, {
      onSuccess: () => {
        toast.success('Orçamento excluído.')
        router.push(ROUTES.QUOTES)
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

  if (!quote) {
    return (
      <div className="space-y-4">
        <p className="text-muted-foreground">Orçamento não encontrado.</p>
        <Button asChild variant="outline"><Link href={ROUTES.QUOTES}>Voltar</Link></Button>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title={`Orçamento ${quote.number}`}
        description={`Criado em ${formatDate(quote.created_at)}`}
        actions={
          <div className="flex flex-wrap gap-2">
            <Button variant="outline" asChild>
              <Link href={ROUTES.QUOTES}>
                <ChevronLeft className="mr-1.5 h-4 w-4" />
                Voltar
              </Link>
            </Button>

            <PrintDocument doc={quote} type="quote" />

            {quote.items && quote.items.length > 0 && (
              <WhatsAppShare quote={quote} />
            )}

            {canMarkSent && (
              <Button variant="outline" size="sm" onClick={handleMarkSent} disabled={isSending}>
                <Send className="mr-2 h-4 w-4" />
                Marcar como Enviado
              </Button>
            )}

            {canConvert && (
              <Button size="sm" onClick={() => setConvertOpen(true)}>
                <ArrowRight className="mr-2 h-4 w-4" />
                Converter em Pedido
              </Button>
            )}

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
              <DocumentStatusBadge status={quote.status} label={quote.status_label} type="quote" />
            </div>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Validade</p>
            <p className={`mt-1 text-sm font-medium ${quote.is_expired ? 'text-red-600' : ''}`}>
              {formatDate(quote.valid_until)}
              {quote.is_expired && ' (expirado)'}
            </p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Subtotal</p>
            <p className="mt-1 text-sm font-medium">{formatBRL(quote.subtotal_cents)}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Total</p>
            <p className="mt-1 text-lg font-semibold">{formatBRL(quote.total_cents)}</p>
          </div>
        </div>

        {(quote.discount_cents > 0 || quote.payment_terms) && (
          <div className="mt-4 grid grid-cols-2 gap-4 border-t pt-4 sm:grid-cols-3">
            {quote.discount_cents > 0 && (
              <div>
                <p className="text-xs text-muted-foreground">Desconto</p>
                <p className="mt-1 text-sm">{formatBRL(quote.discount_cents)}</p>
              </div>
            )}
            {quote.payment_terms && (
              <div>
                <p className="text-xs text-muted-foreground">Condições</p>
                <p className="mt-1 text-sm">{quote.payment_terms}</p>
              </div>
            )}
          </div>
        )}
      </AppCard>

      {/* Customer */}
      {quote.customer && (
        <AppCard title="Cliente">
          <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
            <div>
              <p className="text-xs text-muted-foreground">Nome</p>
              <p className="mt-1 text-sm font-medium">{quote.customer.name}</p>
            </div>
            {quote.customer.phone && (
              <div>
                <p className="text-xs text-muted-foreground">Telefone</p>
                <p className="mt-1 text-sm">{quote.customer.phone}</p>
              </div>
            )}
            {quote.customer.email && (
              <div>
                <p className="text-xs text-muted-foreground">E-mail</p>
                <p className="mt-1 text-sm">{quote.customer.email}</p>
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
              {(quote.items ?? []).map((item: import('@store/shared-types').DocumentItem) => (
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

      {/* Notes */}
      {quote.notes && (
        <AppCard title="Observações">
          <p className="text-sm whitespace-pre-wrap">{quote.notes}</p>
        </AppCard>
      )}

      {/* Convert to Order dialog */}
      <Dialog open={convertOpen} onOpenChange={setConvertOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Converter em Pedido</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-2">
            <p className="text-sm text-muted-foreground">
              Um pedido será criado com os mesmos itens deste orçamento.
            </p>
            <div className="space-y-1.5">
              <Label htmlFor="expected_at">Previsão de entrega (opcional)</Label>
              <Input
                id="expected_at"
                type="date"
                value={expectedAt}
                onChange={(e) => setExpectedAt(e.target.value)}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setConvertOpen(false)}>Cancelar</Button>
            <Button onClick={handleConvert} disabled={isConverting}>
              {isConverting ? 'Convertendo…' : 'Converter'}
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
        title="Excluir orçamento?"
        description="Esta ação não pode ser desfeita."
        confirmLabel="Excluir"
      />
    </div>
  )
}
