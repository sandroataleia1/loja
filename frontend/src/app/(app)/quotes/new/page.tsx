'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { useQuery } from '@tanstack/react-query'
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
import { CustomerAutocomplete } from '@/features/orders/components/customer-autocomplete'
import { SellerPinInput } from '@/features/orders/components/seller-pin-input'
import {
  ProductSearchModal,
  type SelectedProduct,
} from '@/features/orders/components/product-search-modal'
import { CommercialSettingsButton, loadCommercialSettings } from '@/features/orders/components/commercial-settings-modal'
import { useCreateQuote } from '@/features/orders/hooks'
import { paymentService } from '@/services/payment.service'
import { ROUTES } from '@/constants'
import type { CreateQuoteRequest } from '@/services/orders.service'

export default function NewQuotePage() {
  const router = useRouter()

  const { data: paymentConditions = [] } = useQuery({
    queryKey: ['payment-conditions'],
    queryFn:  () => paymentService.listConditions(),
  })

  const { data: paymentMethods = [] } = useQuery({
    queryKey: ['payment-methods'],
    queryFn:  () => paymentService.listMethods(),
  })

  const [customerId,          setCustomerId]          = useState<string | null>(null)
  const [sellerPin,           setSellerPin]           = useState('')
  const [discountType,        setDiscountType]        = useState<'fixed' | 'percent'>('fixed')
  const [discountValue,       setDiscountValue]       = useState(0)
  const [paymentMethodId,     setPaymentMethodId]     = useState<string | null>(null)
  const [paymentConditionId,  setPaymentConditionId]  = useState<string | null>(null)
  const [notes,               setNotes]               = useState('')
  const [items,         setItems]         = useState<EditorItem[]>([])
  const [productOpen,   setProductOpen]   = useState(false)

  const { mutate: createQuote, isPending } = useCreateQuote()

  const subtotalCents = items.reduce((sum, item) => {
    return sum + Math.max(0, Math.round(item.quantity * item.unit_price_cents) - (item.discount_cents ?? 0))
  }, 0)

  function handleProductSelected(product: SelectedProduct) {
    const item: EditorItem = {
      _key:               crypto.randomUUID(),
      product_variant_id: product.product_variant_id,
      name_snapshot:      product.name_snapshot,
      sku_snapshot:       product.sku_snapshot,
      quantity:           1,
      unit_price_cents:   product.unit_price_cents,
      discount_cents:     0,
      notes:              null,
    }
    setItems((prev) => [...prev, item])
  }

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

    const settings = loadCommercialSettings()

    const payload: CreateQuoteRequest = {
      customer_id:    customerId || null,
      seller_pin:     sellerPin || null,
      validity_days:  settings.default_validity_days,
      discount_type:  discountType,
      discount_value: discountValue,
      payment_method_id:    paymentMethodId || null,
      payment_condition_id: paymentConditionId || null,
      notes:                notes || null,
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
          <div className="flex items-center gap-2">
            <CommercialSettingsButton />
            <Button variant="outline" asChild>
              <Link href={ROUTES.QUOTES}>
                <ChevronLeft className="mr-1.5 h-4 w-4" />
                Voltar
              </Link>
            </Button>
          </div>
        }
      />

      <ProductSearchModal
        open={productOpen}
        onClose={() => setProductOpen(false)}
        onSelect={handleProductSelected}
      />

      <form onSubmit={handleSubmit} className="space-y-6">
        <AppCard title="Informações Gerais">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <CustomerAutocomplete
              value={customerId}
              onChange={(uuid) => setCustomerId(uuid)}
              disabled={isPending}
            />

            <SellerPinInput
              value={sellerPin}
              onChange={setSellerPin}
              onSellerResolved={() => {}}
              disabled={isPending}
            />

            <div className="space-y-1.5">
              <Label htmlFor="payment_method_id">Forma de Pagamento</Label>
              <select
                id="payment_method_id"
                value={paymentMethodId ?? ''}
                onChange={(e) => setPaymentMethodId(e.target.value || null)}
                disabled={isPending}
                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
                <option value="">Selecionar forma</option>
                {paymentMethods.filter((m) => m.is_active).map((m) => (
                  <option key={m.uuid} value={m.uuid}>{m.name}</option>
                ))}
              </select>
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="payment_condition_id">Condição de Pagamento</Label>
              <select
                id="payment_condition_id"
                value={paymentConditionId ?? ''}
                onChange={(e) => setPaymentConditionId(e.target.value || null)}
                disabled={isPending}
                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
                <option value="">Selecionar condição</option>
                {paymentConditions.filter((c) => c.is_active).map((c) => (
                  <option key={c.uuid} value={c.uuid}>{c.name}</option>
                ))}
              </select>
            </div>
          </div>
        </AppCard>

        <AppCard title="Itens do Orçamento">
          <DocumentItemEditor
            items={items}
            onChange={setItems}
            disabled={isPending}
            onOpenProductSearch={() => setProductOpen(true)}
          />
        </AppCard>

        <AppCard title="Desconto e Observações">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Desconto</Label>
              <div className="flex gap-2">
                <select
                  value={discountType}
                  onChange={(e) => setDiscountType(e.target.value as 'fixed' | 'percent')}
                  className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                  disabled={isPending}
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
                  disabled={isPending}
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
                disabled={isPending}
              />
            </div>
          </div>
        </AppCard>

        <div className="flex items-center justify-between">
          <p className="text-sm text-muted-foreground">
            Subtotal dos itens:{' '}
            <span className="font-medium text-foreground">{formatBRL(subtotalCents)}</span>
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
