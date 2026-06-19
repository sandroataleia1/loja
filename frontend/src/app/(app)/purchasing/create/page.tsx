'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { Plus, Trash2, ChevronLeft, Loader2 } from 'lucide-react'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard }       from '@/components/shared/app-card'
import { Button }        from '@/components/ui/button'
import { Input }         from '@/components/ui/input'
import { Label }         from '@/components/ui/label'
import { useAllSuppliers, useCreatePurchaseOrder } from '@/features/purchasing/hooks'
import { useStores }     from '@/features/inventory/hooks'
import { genId } from '@/lib/utils'
import { ROUTES } from '@/constants'

const fmtBRL = (v: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v)

interface ItemRow {
  id:                 string
  product_variant_id: string
  quantity:           number
  unit_cost:          number
}

function newItem(): ItemRow {
  return { id: genId(), product_variant_id: '', quantity: 1, unit_cost: 0 }
}

export default function CreatePurchasingPage() {
  const router = useRouter()
  const { data: suppliers = [] } = useAllSuppliers()
  const { data: stores = [] }    = useStores()
  const { mutate: create, isPending } = useCreatePurchaseOrder()

  const [supplierId,           setSupplierId]           = useState('')
  const [storeId,              setStoreId]              = useState('')
  const [orderDate,            setOrderDate]            = useState(() => new Date().toISOString().slice(0, 10))
  const [expectedDeliveryDate, setExpectedDeliveryDate] = useState('')
  const [discount,             setDiscount]             = useState('0')
  const [notes,                setNotes]                = useState('')
  const [items,                setItems]                = useState<ItemRow[]>([newItem()])
  const [errors,               setErrors]              = useState<string[]>([])

  function updateItem(id: string, field: keyof ItemRow, value: string | number) {
    setItems((prev) => prev.map((it) => it.id === id ? { ...it, [field]: value } : it))
  }

  function removeItem(id: string) {
    setItems((prev) => prev.filter((it) => it.id !== id))
  }

  const subtotal  = items.reduce((s, it) => s + it.quantity * it.unit_cost, 0)
  const total     = Math.max(0, subtotal - parseFloat(discount || '0'))

  function validate(): string[] {
    const errs: string[] = []
    if (!supplierId) errs.push('Selecione um fornecedor.')
    if (!storeId)    errs.push('Selecione uma loja.')
    if (!orderDate)  errs.push('Informe a data do pedido.')
    if (items.length === 0) errs.push('Adicione ao menos um item.')
    items.forEach((it, i) => {
      if (!it.product_variant_id) errs.push(`Item ${i + 1}: informe o UUID da variante.`)
      if (it.quantity < 1)        errs.push(`Item ${i + 1}: quantidade mínima é 1.`)
      if (it.unit_cost <= 0)      errs.push(`Item ${i + 1}: custo unitário deve ser maior que zero.`)
    })
    return errs
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    const errs = validate()
    if (errs.length > 0) { setErrors(errs); return }
    setErrors([])

    create({
      supplier_id:             supplierId,
      store_id:                storeId,
      order_date:              orderDate,
      expected_delivery_date:  expectedDeliveryDate || undefined,
      discount:                parseFloat(discount || '0') || undefined,
      notes:                   notes || undefined,
      items: items.map((it) => ({
        product_variant_id: it.product_variant_id,
        quantity:           it.quantity,
        unit_cost:          it.unit_cost,
      })),
    }, {
      onSuccess: (order) => router.push(`${ROUTES.PURCHASING}/${order.uuid}`),
    })
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Novo Pedido de Compra"
        description="Crie um pedido de compra para um fornecedor."
        actions={
          <Button variant="outline" size="sm" asChild>
            <Link href={ROUTES.PURCHASING}>
              <ChevronLeft className="mr-1 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />

      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Cabeçalho do pedido */}
        <AppCard>
          <div className="p-5 space-y-4">
            <p className="text-sm font-semibold">Dados do pedido</p>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label>Fornecedor *</Label>
                <select
                  value={supplierId}
                  onChange={(e) => setSupplierId(e.target.value)}
                  className="w-full h-9 rounded-lg border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
                  <option value="">Selecionar fornecedor…</option>
                  {suppliers.map((s) => (
                    <option key={s.uuid} value={s.uuid}>{s.name}</option>
                  ))}
                </select>
              </div>

              <div className="space-y-1.5">
                <Label>Loja *</Label>
                <select
                  value={storeId}
                  onChange={(e) => setStoreId(e.target.value)}
                  className="w-full h-9 rounded-lg border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
                  <option value="">Selecionar loja…</option>
                  {stores.map((s) => (
                    <option key={s.uuid} value={s.uuid}>{s.name}</option>
                  ))}
                </select>
              </div>

              <div className="space-y-1.5">
                <Label>Data do pedido *</Label>
                <Input type="date" value={orderDate} onChange={(e) => setOrderDate(e.target.value)} />
              </div>

              <div className="space-y-1.5">
                <Label>Entrega prevista</Label>
                <Input type="date" value={expectedDeliveryDate} onChange={(e) => setExpectedDeliveryDate(e.target.value)} />
              </div>
            </div>
          </div>
        </AppCard>

        {/* Itens */}
        <AppCard>
          <div className="p-5 space-y-3">
            <div className="flex items-center justify-between">
              <p className="text-sm font-semibold">Itens</p>
              <Button type="button" size="sm" variant="outline" onClick={() => setItems((p) => [...p, newItem()])}>
                <Plus className="mr-1 h-3.5 w-3.5" />
                Adicionar item
              </Button>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b">
                    <th className="pb-2 text-left text-xs font-medium text-muted-foreground">UUID da Variante</th>
                    <th className="pb-2 px-3 text-left text-xs font-medium text-muted-foreground w-24">Qtd</th>
                    <th className="pb-2 px-3 text-left text-xs font-medium text-muted-foreground w-32">Custo Unit. (R$)</th>
                    <th className="pb-2 text-right text-xs font-medium text-muted-foreground w-28">Total</th>
                    <th className="w-8" />
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {items.map((it) => (
                    <tr key={it.id}>
                      <td className="py-2 pr-3">
                        <Input
                          placeholder="uuid da variante…"
                          value={it.product_variant_id}
                          onChange={(e) => updateItem(it.id, 'product_variant_id', e.target.value)}
                          className="h-8 font-mono text-xs"
                        />
                      </td>
                      <td className="py-2 px-3">
                        <Input
                          type="number"
                          min={1}
                          value={it.quantity}
                          onChange={(e) => updateItem(it.id, 'quantity', parseInt(e.target.value) || 1)}
                          className="h-8 w-20 text-center"
                        />
                      </td>
                      <td className="py-2 px-3">
                        <Input
                          type="number"
                          min={0}
                          step="0.01"
                          value={it.unit_cost}
                          onChange={(e) => updateItem(it.id, 'unit_cost', parseFloat(e.target.value) || 0)}
                          className="h-8 w-28"
                        />
                      </td>
                      <td className="py-2 text-right font-medium tabular-nums">
                        {fmtBRL(it.quantity * it.unit_cost)}
                      </td>
                      <td className="py-2 pl-2">
                        <button
                          type="button"
                          onClick={() => removeItem(it.id)}
                          disabled={items.length === 1}
                          className="p-1 text-muted-foreground hover:text-destructive transition-colors disabled:opacity-30"
                        >
                          <Trash2 className="h-3.5 w-3.5" />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Totais */}
            <div className="border-t pt-3 space-y-1.5 ml-auto max-w-xs">
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Subtotal</span>
                <span className="tabular-nums">{fmtBRL(subtotal)}</span>
              </div>
              <div className="flex items-center justify-between text-sm gap-4">
                <span className="text-muted-foreground">Desconto (R$)</span>
                <Input
                  type="number"
                  min={0}
                  step="0.01"
                  value={discount}
                  onChange={(e) => setDiscount(e.target.value)}
                  className="h-7 w-28 text-right text-sm"
                />
              </div>
              <div className="flex justify-between text-sm font-bold border-t pt-1.5">
                <span>Total</span>
                <span className="tabular-nums">{fmtBRL(total)}</span>
              </div>
            </div>
          </div>
        </AppCard>

        {/* Observações */}
        <AppCard>
          <div className="p-5 space-y-1.5">
            <Label>Observações</Label>
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              rows={2}
              placeholder="Condições de pagamento, instruções de entrega…"
              className="w-full rounded-lg border bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-primary/30"
            />
          </div>
        </AppCard>

        {/* Erros */}
        {errors.length > 0 && (
          <div className="rounded-xl border border-destructive/30 bg-destructive/10 px-4 py-3 space-y-1">
            {errors.map((e, i) => (
              <p key={i} className="text-sm text-destructive">{e}</p>
            ))}
          </div>
        )}

        <div className="flex justify-end gap-3">
          <Button type="button" variant="outline" asChild>
            <Link href={ROUTES.PURCHASING}>Cancelar</Link>
          </Button>
          <Button type="submit" disabled={isPending}>
            {isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Criar pedido
          </Button>
        </div>
      </form>
    </div>
  )
}
