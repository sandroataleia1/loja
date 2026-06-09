'use client'

import { useForm, useFieldArray } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AppCard } from '@/components/shared/app-card'
import { useStores } from '@/features/inventory/hooks'
import type { CreateConditionalRequest } from '@store/contracts'

// ── Zod schema ────────────────────────────────────────────────────────────────

const itemSchema = z.object({
  variant_id:       z.string().uuid('UUID do variante inválido'),
  quantity:         z.coerce.number().int('Deve ser inteiro').min(1, 'Mínimo 1'),
  unit_price_cents: z.coerce.number().int('Deve ser inteiro').min(0, 'Deve ser ≥ 0'),
})

const conditionalSchema = z.object({
  store_id:    z.string().uuid('Loja obrigatória'),
  customer_id: z.string().uuid('Cliente obrigatório'),
  due_date:    z.string().min(1, 'Data de vencimento obrigatória'),
  notes:       z.string().max(2000, 'Máximo 2000 caracteres').optional().or(z.literal('')),
  items:       z.array(itemSchema).min(1, 'Adicione ao menos um item'),
})

type ConditionalFormValues = z.infer<typeof conditionalSchema>

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatBRL(cents: number): string {
  return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function getTodayISO(): string {
  return new Date().toISOString().split('T')[0]
}

// ── Props ─────────────────────────────────────────────────────────────────────

interface ConditionalFormProps {
  onSubmit:     (data: CreateConditionalRequest) => void
  isSubmitting: boolean
}

// ── Component ─────────────────────────────────────────────────────────────────

export function ConditionalForm({ onSubmit, isSubmitting }: ConditionalFormProps) {
  const { data: stores = [], isLoading: loadingStores } = useStores()

  const {
    register,
    control,
    handleSubmit,
    watch,
    formState: { errors },
  } = useForm<ConditionalFormValues>({
    resolver: zodResolver(conditionalSchema),
    defaultValues: {
      store_id:    '',
      customer_id: '',
      due_date:    '',
      notes:       '',
      items:       [{ variant_id: '', quantity: 1, unit_price_cents: 0 }],
    },
  })

  const { fields, append, remove } = useFieldArray({ control, name: 'items' })

  const watchedItems = watch('items')

  const totalCents = (watchedItems ?? []).reduce(
    (sum, item) => sum + (Number(item.quantity) || 0) * (Number(item.unit_price_cents) || 0),
    0,
  )

  function handleFormSubmit(values: ConditionalFormValues) {
    onSubmit({
      store_id:    values.store_id,
      customer_id: values.customer_id,
      due_date:    values.due_date,
      notes:       values.notes || undefined,
      items:       values.items,
    })
  }

  return (
    <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-6">

      {/* Section 1: Loja */}
      <AppCard title="Loja">
        <div className="space-y-2">
          <Label htmlFor="store_id">Loja *</Label>
          <select
            id="store_id"
            {...register('store_id')}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            disabled={loadingStores}
          >
            <option value="">Selecione uma loja…</option>
            {stores.map((store) => (
              <option key={store.uuid} value={store.uuid}>
                {store.name} ({store.code})
              </option>
            ))}
          </select>
          {errors.store_id && (
            <p className="text-xs text-destructive">{errors.store_id.message}</p>
          )}
        </div>
      </AppCard>

      {/* Section 2: Cliente */}
      <AppCard title="Cliente">
        <div className="space-y-2">
          <Label htmlFor="customer_id">UUID do Cliente *</Label>
          <Input
            id="customer_id"
            placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
            {...register('customer_id')}
          />
          {errors.customer_id && (
            <p className="text-xs text-destructive">{errors.customer_id.message}</p>
          )}
          <p className="text-xs text-muted-foreground">
            Informe o UUID do cliente. Em breve haverá busca por nome.
          </p>
        </div>
      </AppCard>

      {/* Section 3: Vencimento */}
      <AppCard title="Data de Vencimento">
        <div className="space-y-2">
          <Label htmlFor="due_date">Vencimento *</Label>
          <Input
            id="due_date"
            type="date"
            min={getTodayISO()}
            {...register('due_date')}
          />
          {errors.due_date && (
            <p className="text-xs text-destructive">{errors.due_date.message}</p>
          )}
        </div>
      </AppCard>

      {/* Section 4: Itens */}
      <AppCard
        title="Itens"
        actions={
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => append({ variant_id: '', quantity: 1, unit_price_cents: 0 })}
          >
            <Plus className="mr-1.5 h-4 w-4" />
            Adicionar Item
          </Button>
        }
      >
        <div className="space-y-3">
          {fields.length === 0 && (
            <p className="text-sm text-muted-foreground">Nenhum item adicionado.</p>
          )}

          {fields.map((field, index) => (
            <div
              key={field.id}
              className="grid grid-cols-1 gap-3 rounded-md border p-3 sm:grid-cols-[1fr_100px_130px_auto]"
            >
              {/* Variant UUID / SKU */}
              <div className="space-y-1">
                <Label className="text-xs">UUID do Variante *</Label>
                <Input
                  placeholder="UUID do variante"
                  {...register(`items.${index}.variant_id`)}
                  className="h-8 text-xs font-mono"
                />
                {errors.items?.[index]?.variant_id && (
                  <p className="text-xs text-destructive">
                    {errors.items[index]?.variant_id?.message}
                  </p>
                )}
              </div>

              {/* Quantidade */}
              <div className="space-y-1">
                <Label className="text-xs">Qtd *</Label>
                <Input
                  type="number"
                  min={1}
                  {...register(`items.${index}.quantity`)}
                  className="h-8 text-xs"
                />
                {errors.items?.[index]?.quantity && (
                  <p className="text-xs text-destructive">
                    {errors.items[index]?.quantity?.message}
                  </p>
                )}
              </div>

              {/* Preço unitário (em centavos) */}
              <div className="space-y-1">
                <Label className="text-xs">Preço Unit. (centavos) *</Label>
                <Input
                  type="number"
                  min={0}
                  {...register(`items.${index}.unit_price_cents`)}
                  className="h-8 text-xs"
                />
                {errors.items?.[index]?.unit_price_cents && (
                  <p className="text-xs text-destructive">
                    {errors.items[index]?.unit_price_cents?.message}
                  </p>
                )}
              </div>

              {/* Remove */}
              <div className="flex items-end">
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  className="h-8 w-8 text-destructive hover:text-destructive"
                  onClick={() => remove(index)}
                  disabled={fields.length === 1}
                  aria-label="Remover item"
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
            </div>
          ))}

          {errors.items && !Array.isArray(errors.items) && (
            <p className="text-xs text-destructive">{(errors.items as { message?: string }).message}</p>
          )}

          {/* Total */}
          {fields.length > 0 && (
            <div className="flex justify-end pt-2 border-t">
              <span className="text-sm font-semibold">
                Total: {formatBRL(totalCents)}
              </span>
            </div>
          )}
        </div>
      </AppCard>

      {/* Section 5: Observações */}
      <AppCard title="Observações">
        <div className="space-y-2">
          <Label htmlFor="notes">Observações</Label>
          <textarea
            id="notes"
            rows={3}
            placeholder="Observações opcionais…"
            {...register('notes')}
            className="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring resize-none"
          />
          {errors.notes && (
            <p className="text-xs text-destructive">{errors.notes.message}</p>
          )}
        </div>
      </AppCard>

      {/* Submit */}
      <div className="flex justify-end">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? 'Salvando…' : 'Criar Condicional'}
        </Button>
      </div>
    </form>
  )
}
