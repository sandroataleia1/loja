'use client'

import { useForm, useFieldArray } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AppCard } from '@/components/shared/app-card'
import { useStores, useCreateTransfer } from '@/features/inventory/hooks'

// ── Zod schema ────────────────────────────────────────────────────────────────

const transferSchema = z.object({
  origin_store_id:      z.string().min(1, 'Selecione a loja de origem'),
  destination_store_id: z.string().min(1, 'Selecione a loja de destino'),
  notes:                z.string().optional(),
  items: z
    .array(
      z.object({
        variant_id: z.string().min(1, 'Informe o UUID da variante'),
        quantity:   z.coerce.number().int().min(1, 'Mínimo 1'),
      }),
    )
    .min(1, 'Adicione ao menos 1 item'),
}).refine(
  (data) => data.origin_store_id !== data.destination_store_id,
  {
    message:  'Loja de destino deve ser diferente da origem',
    path:     ['destination_store_id'],
  },
)

type TransferFormValues = z.infer<typeof transferSchema>

// ── Component ─────────────────────────────────────────────────────────────────

interface TransferFormProps {
  onSuccess: (uuid: string) => void
}

export function TransferForm({ onSuccess }: TransferFormProps) {
  const { data: stores = [], isLoading: storesLoading } = useStores()
  const { mutate: createTransfer, isPending } = useCreateTransfer()

  const {
    register,
    handleSubmit,
    control,
    formState: { errors },
  } = useForm<TransferFormValues>({
    resolver: zodResolver(transferSchema),
    defaultValues: {
      origin_store_id:      '',
      destination_store_id: '',
      notes:                '',
      items:                [{ variant_id: '', quantity: 1 }],
    },
  })

  const { fields, append, remove } = useFieldArray({ control, name: 'items' })

  function onSubmit(values: TransferFormValues) {
    createTransfer(
      {
        origin_store_id:      values.origin_store_id,
        destination_store_id: values.destination_store_id,
        notes:                values.notes || undefined,
        items:                values.items,
      },
      {
        onSuccess: (transfer) => {
          toast.success('Transferência criada com sucesso.')
          onSuccess(transfer.uuid)
        },
        onError: (err) => {
          toast.error(err instanceof Error ? err.message : 'Erro ao criar transferência.')
        },
      },
    )
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">

      {/* ── Lojas ── */}
      <AppCard title="Lojas">
        <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">

          <div className="space-y-1.5">
            <Label htmlFor="origin_store_id">Loja de Origem *</Label>
            <select
              id="origin_store_id"
              className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:opacity-50"
              disabled={storesLoading}
              {...register('origin_store_id')}
            >
              <option value="">Selecione a origem…</option>
              {stores.map((s) => (
                <option key={s.uuid} value={s.uuid}>{s.name}</option>
              ))}
            </select>
            {errors.origin_store_id && (
              <p className="text-xs text-destructive">{errors.origin_store_id.message}</p>
            )}
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="destination_store_id">Loja de Destino *</Label>
            <select
              id="destination_store_id"
              className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:opacity-50"
              disabled={storesLoading}
              {...register('destination_store_id')}
            >
              <option value="">Selecione o destino…</option>
              {stores.map((s) => (
                <option key={s.uuid} value={s.uuid}>{s.name}</option>
              ))}
            </select>
            {errors.destination_store_id && (
              <p className="text-xs text-destructive">{errors.destination_store_id.message}</p>
            )}
          </div>

          <div className="sm:col-span-2 space-y-1.5">
            <Label htmlFor="notes">Observações</Label>
            <textarea
              id="notes"
              rows={2}
              className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 resize-y"
              placeholder="Observações opcionais…"
              {...register('notes')}
            />
          </div>
        </div>
      </AppCard>

      {/* ── Itens ── */}
      <AppCard title="Itens">
        <div className="space-y-3 max-w-2xl">
          {fields.map((field, index) => (
            <div key={field.id} className="flex items-start gap-3">
              <div className="flex-1 space-y-1.5">
                <Label>UUID da Variante *</Label>
                <Input
                  placeholder="uuid da variante"
                  {...register(`items.${index}.variant_id`)}
                />
                {errors.items?.[index]?.variant_id && (
                  <p className="text-xs text-destructive">
                    {errors.items[index]?.variant_id?.message}
                  </p>
                )}
              </div>
              <div className="w-28 space-y-1.5">
                <Label>Qtd. *</Label>
                <Input
                  type="number"
                  min={1}
                  {...register(`items.${index}.quantity`)}
                />
                {errors.items?.[index]?.quantity && (
                  <p className="text-xs text-destructive">
                    {errors.items[index]?.quantity?.message}
                  </p>
                )}
              </div>
              <div className="pt-6">
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  disabled={fields.length === 1}
                  onClick={() => remove(index)}
                  aria-label="Remover item"
                >
                  <Trash2 className="h-4 w-4 text-destructive" />
                </Button>
              </div>
            </div>
          ))}

          {errors.items?.root && (
            <p className="text-xs text-destructive">{errors.items.root.message}</p>
          )}

          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => append({ variant_id: '', quantity: 1 })}
          >
            <Plus className="mr-1.5 h-3.5 w-3.5" />
            Adicionar Item
          </Button>
        </div>
      </AppCard>

      <div className="flex gap-2">
        <Button type="submit" disabled={isPending}>
          {isPending ? 'Criando…' : 'Criar Transferência'}
        </Button>
      </div>
    </form>
  )
}
