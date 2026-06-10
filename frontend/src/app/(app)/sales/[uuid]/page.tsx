'use client'

import { use, useState } from 'react'
import { useSale, useCancelSale } from '@/features/sales/hooks'
import { SaleStatusBadge } from '@/features/sales/components/sale-status-badge'
import { Skeleton } from '@/components/ui/skeleton'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { toast } from 'sonner'

function fmt(cents: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

const METHOD_LABELS: Record<string, string> = {
  cash: 'Dinheiro', pix: 'PIX', credit_card: 'Cartão Crédito',
  debit_card: 'Cartão Débito', store_credit: 'Crédito Loja', voucher: 'Vale',
}

export default function SaleDetailPage({ params }: { params: Promise<{ uuid: string }> }) {
  const { uuid } = use(params)
  const { data: sale, isLoading } = useSale(uuid)
  const { mutate: cancel, isPending } = useCancelSale(uuid)
  const [tab, setTab] = useState<'items' | 'payments' | 'fiscal'>('items')

  if (isLoading) return <div className="space-y-4">{Array.from({ length: 5 }).map((_, i) => <Skeleton key={i} className="h-12 w-full" />)}</div>
  if (!sale) return <p className="text-muted-foreground">Venda não encontrada.</p>

  const canCancel = ['draft', 'pending', 'completed'].includes(sale.status)

  function handleCancel() {
    if (!confirm('Cancelar esta venda?')) return
    cancel(undefined, {
      onSuccess: () => toast.success('Venda cancelada.'),
      onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao cancelar.'),
    })
  }

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between">
        <div className="space-y-1">
          <div className="flex items-center gap-3">
            <h1 className="text-2xl font-bold">{sale.code ?? 'Venda'}</h1>
            <SaleStatusBadge status={sale.status} />
          </div>
          <p className="text-muted-foreground text-sm">
            {sale.store?.name} · {sale.customer?.name ?? 'Consumidor Final'} ·{' '}
            {new Date(sale.created_at).toLocaleString('pt-BR')}
          </p>
        </div>
        <div className="text-right">
          <p className="text-2xl font-bold">{fmt(sale.total_cents)}</p>
          {canCancel && (
            <Button variant="destructive" size="sm" className="mt-2" onClick={handleCancel} disabled={isPending}>
              {isPending ? 'Cancelando...' : 'Cancelar Venda'}
            </Button>
          )}
        </div>
      </div>

      {/* Tabs */}
      <div className="flex gap-1 border-b">
        {(['items', 'payments', 'fiscal'] as const).map((t) => (
          <button
            key={t}
            onClick={() => setTab(t)}
            className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${tab === t ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'}`}
          >
            {t === 'items' ? 'Itens' : t === 'payments' ? 'Pagamentos' : 'Fiscal'}
          </button>
        ))}
      </div>

      {tab === 'items' && (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>SKU</TableHead>
              <TableHead>Produto</TableHead>
              <TableHead className="text-center">Qtd</TableHead>
              <TableHead className="text-right">Preço Unit.</TableHead>
              <TableHead className="text-right">Desconto</TableHead>
              <TableHead className="text-right">Total</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {(sale.items ?? []).map((item) => (
              <TableRow key={item.uuid}>
                <TableCell className="font-mono text-sm">{item.sku_snapshot}</TableCell>
                <TableCell>{item.name_snapshot}</TableCell>
                <TableCell className="text-center">{item.quantity}</TableCell>
                <TableCell className="text-right">{fmt(item.unit_price_cents)}</TableCell>
                <TableCell className="text-right text-muted-foreground">{item.discount_amount_cents ? fmt(item.discount_amount_cents) : '—'}</TableCell>
                <TableCell className="text-right font-medium">{fmt(item.total_cents)}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}

      {tab === 'payments' && (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Método</TableHead>
              <TableHead className="text-right">Valor</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Data</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {(sale.payments ?? []).map((p) => (
              <TableRow key={p.uuid}>
                <TableCell>{METHOD_LABELS[p.method] ?? p.method}</TableCell>
                <TableCell className="text-right font-medium">{fmt(p.amount_cents)}</TableCell>
                <TableCell className="text-sm capitalize">{p.status}</TableCell>
                <TableCell className="text-sm text-muted-foreground">{p.paid_at ? new Date(p.paid_at).toLocaleString('pt-BR') : '—'}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}

      {tab === 'fiscal' && (
        <div className="space-y-3">
          <div className="rounded-lg border p-4 space-y-2">
            <p className="text-sm"><span className="font-medium">Status Fiscal:</span> {sale.fiscal_status}</p>
            {sale.notes && <p className="text-sm"><span className="font-medium">Observações:</span> {sale.notes}</p>}
          </div>
        </div>
      )}
    </div>
  )
}
