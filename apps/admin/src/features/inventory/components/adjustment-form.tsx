'use client'

import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AppCard } from '@/components/shared/app-card'
import { StockBadge } from './stock-badge'
import { useStores, useCreateAdjustment, useInventoryBalances } from '@/features/inventory/hooks'

// ── Zod schema ────────────────────────────────────────────────────────────────

const adjustmentSchema = z.object({
  store_id:   z.string().min(1, 'Selecione uma loja'),
  variant_id: z.string().min(1, 'Informe o UUID da variante'),
  quantity:   z.coerce
    .number({ invalid_type_error: 'Informe um número inteiro' })
    .int('Deve ser um número inteiro')
    .refine((v) => v !== 0, 'A quantidade não pode ser zero'),
  reason:     z.string().min(3, 'Motivo deve ter ao menos 3 caracteres'),
})

type AdjustmentFormValues = z.infer<typeof adjustmentSchema>

// ── Component ─────────────────────────────────────────────────────────────────

interface AdjustmentFormProps {
  onSuccess: () => void
}

export function AdjustmentForm({ onSuccess }: AdjustmentFormProps) {
  const { data: stores = [], isLoading: storesLoading } = useStores()
  const { mutate: createAdjustment, isPending } = useCreateAdjustment()

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors },
  } = useForm<AdjustmentFormValues>({
    resolver: zodResolver(adjustmentSchema),
    defaultValues: { store_id: '', variant_id: '', quantity: 0, reason: '' },
  })

  const watchedStoreId   = watch('store_id')
  const watchedVariantId = watch('variant_id')

  // Fetch current balance when both fields are filled
  const { data: balancesData } = useInventoryBalances(
    watchedStoreId && watchedVariantId
      ? { store_id: watchedStoreId, variant_id: watchedVariantId, per_page: 1 }
      : undefined,
  )
  const currentBalance = balancesData?.data?.[0]

  function onSubmit(values: AdjustmentFormValues) {
    createAdjustment(values, {
      onSuccess: () => {
        toast.success('Ajuste de estoque criado com sucesso.')
        onSuccess()
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao criar ajuste.')
      },
    })
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <AppCard title="Dados do Ajuste">
        <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">

          {/* store_id */}
          <div className="space-y-1.5">
            <Label htmlFor="store_id">Loja *</Label>
            <select
              id="store_id"
              className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:opacity-50"
              disabled={storesLoading}
              {...register('store_id')}
            >
              <option value="">Selecione uma loja…</option>
              {stores.map((s) => (
                <option key={s.uuid} value={s.uuid}>{s.name}</option>
              ))}
            </select>
            {errors.store_id && (
              <p className="text-xs text-destructive">{errors.store_id.message}</p>
            )}
          </div>

          {/* variant_id */}
          <div className="space-y-1.5">
            <Label htmlFor="variant_id">UUID da Variante *</Label>
            <Input
              id="variant_id"
              placeholder="uuid da variante"
              {...register('variant_id')}
            />
            {errors.variant_id && (
              <p className="text-xs text-destructive">{errors.variant_id.message}</p>
            )}
          </div>

          {/* Current balance info */}
          {currentBalance && (
            <div className="sm:col-span-2 flex items-center gap-2 rounded-md border bg-muted/40 px-3 py-2 text-sm">
              <span className="text-muted-foreground">Estoque atual:</span>
              <StockBadge
                quantity={currentBalance.quantity}
                reserved={currentBalance.reserved_quantity}
              />
              {currentBalance.variant?.sku && (
                <span className="ml-auto font-mono text-xs text-muted-foreground">
                  SKU: {currentBalance.variant.sku}
                </span>
              )}
            </div>
          )}

          {/* quantity */}
          <div className="space-y-1.5">
            <Label htmlFor="quantity">Quantidade *</Label>
            <Input
              id="quantity"
              type="number"
              placeholder="Ex.: +10 ou -5 (não pode ser 0)"
              {...register('quantity')}
            />
            <p className="text-xs text-muted-foreground">
              Use valor positivo para entrada e negativo para saída.
            </p>
            {errors.quantity && (
              <p className="text-xs text-destructive">{errors.quantity.message}</p>
            )}
          </div>

          {/* reason */}
          <div className="sm:col-span-2 space-y-1.5">
            <Label htmlFor="reason">Motivo *</Label>
            <textarea
              id="reason"
              rows={3}
              className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 resize-y"
              placeholder="Descreva o motivo do ajuste…"
              {...register('reason')}
            />
            {errors.reason && (
              <p className="text-xs text-destructive">{errors.reason.message}</p>
            )}
          </div>
        </div>
      </AppCard>

      <div className="flex gap-2">
        <Button type="submit" disabled={isPending}>
          {isPending ? 'Salvando…' : 'Criar Ajuste'}
        </Button>
      </div>
    </form>
  )
}
