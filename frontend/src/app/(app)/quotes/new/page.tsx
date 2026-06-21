'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { ChevronLeft } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import {
  DocumentItemEditor,
  type EditorItem,
  formatBRL,
} from '@/features/orders/components/document-item-editor'
import { useCreateQuote } from '@/features/orders/hooks'
import { useCustomers } from '@/features/customers/hooks'
import { ROUTES } from '@/constants'
import type { CreateQuoteRequest } from '@/services/orders.service'

export default function NewQuotePage() {
  const router = useRouter()

  const [customerId,    setCustomerId]    = useState('')
  const [validityDays,  setValidityDays]  = useState(30)
  const [discountType,  setDiscountType]  = useState<'fixed' | 'percent'>('fixed')
  const [discountValue, setDiscountValue] = useState(0)
  const [paymentTerms,  setPaymentTerms]  = useState('')
  const [notes,         setNotes]         = useState('')
  const [items,         setItems]         = useState<EditorItem[]>([])

  const { data: customersData } = useCustomers({ per_page: 200 })
  const customers = customersData?.data ?? []

  const { mutate: createQuote, isPending } = useCreateQuote()

  const subtotalCents = items.reduce((sum, item) => {
    return sum + Math.max(0, Math.round(item.quantity * item.unit_price_cents) - (item.discount_cents ?? 0))
  }, 0)

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()

    if (items.length === 0) {
      toast.error('Adicione ao menos um item.')
      return
    }

    if (items.some((i) => !i.name_snapshot.trim())) {
      toast.error('Todos os itens precisam de uma descrição.')
      return
    }

    const payload: CreateQuoteRequest = {
      customer_id:   customerId || null,
      validity_days: validityDays,
      discount_type: discountType,
      discount_value: discountValue,
      payment_terms:  paymentTerms || null,
      notes:          notes || null,
      items: items.map((item, idx) => ({
        product_variant_id: item.product_variant_id ?? null,
        name_snapshot:      item.name_snapshot,
        sku_snapshot:       item.sku_snapshot ?? null,
        quantity:           item.quantity,
        unit_price_cents:   item.unit_price_cents,
        discount_cents:     item.discount_cents ?? 0,
        sort_order:         idx,
      })),
    }

    createQuote(payload, {
      onSuccess: (quote) => {
        toast.success('Orçamento criado com sucesso.')
        router.push(`${ROUTES.QUOTES}/${quote.uuid}`)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao criar orçamento.')
      },
    })
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Novo Orçamento"
        description="Crie um orçamento para enviar ao cliente."
        actions={
          <Button variant="outline" asChild>
            <Link href={ROUTES.QUOTES}>
              <ChevronLeft className="mr-1.5 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Header */}
        <AppCard title="Informações Gerais">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div className="space-y-1.5">
              <Label htmlFor="customer_id">Cliente</Label>
              <select
                id="customer_id"
                value={customerId}
                onChange={(e) => setCustomerId(e.target.value)}
                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
                <option value="">Consumidor Final</option>
                {customers.map((c) => (
                  <option key={c.uuid} value={c.uuid}>{c.name}</option>
                ))}
              </select>
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="validity_days">Validade (dias)</Label>
              <Input
                id="validity_days"
                type="number"
                min={1}
                value={validityDays}
                onChange={(e) => setValidityDays(parseInt(e.target.value) || 30)}
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="payment_terms">Condições de Pagamento</Label>
              <Input
                id="payment_terms"
                placeholder="Ex: 30/60/90 dias"
                value={paymentTerms}
                onChange={(e) => setPaymentTerms(e.target.value)}
              />
            </div>
          </div>
        </AppCard>

        {/* Items */}
        <AppCard title="Itens do Orçamento">
          <DocumentItemEditor items={items} onChange={setItems} disabled={isPending} />
        </AppCard>

        {/* Discount + Notes */}
        <AppCard title="Desconto e Observações">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Desconto</Label>
              <div className="flex gap-2">
                <select
                  value={discountType}
                  onChange={(e) => setDiscountType(e.target.value as 'fixed' | 'percent')}
                  className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                >
                  <option value="fixed">R$ (fixo)</option>
                  <option value="percent">% (percentual)</option>
                </select>
                <Input
                  type="number"
                  min={0}
                  step={0.01}
                  value={discountValue}
                  onChange={(e) => setDiscountValue(parseFloat(e.target.value) || 0)}
                  placeholder={discountType === 'percent' ? '0.00' : '0.00'}
                  className="flex-1"
                />
              </div>
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="notes">Observações</Label>
              <Textarea
                id="notes"
                rows={3}
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                placeholder="Observações visíveis ao cliente"
              />
            </div>
          </div>
        </AppCard>

        {/* Summary + Submit */}
        <div className="flex items-center justify-between">
          <p className="text-sm text-muted-foreground">
            Subtotal dos itens: <span className="font-medium text-foreground">{formatBRL(subtotalCents)}</span>
          </p>
          <div className="flex gap-3">
            <Button variant="outline" type="button" asChild>
              <Link href={ROUTES.QUOTES}>Cancelar</Link>
            </Button>
            <Button type="submit" disabled={isPending}>
              {isPending ? 'Salvando…' : 'Criar Orçamento'}
            </Button>
          </div>
        </div>
      </form>
    </div>
  )
}
