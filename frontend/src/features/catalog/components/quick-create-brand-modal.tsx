'use client'

import { useEffect, useRef } from 'react'
import { createPortal } from 'react-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useCreateBrand } from '@/features/catalog/hooks'

const schema = z.object({
  name:        z.string().min(2, 'Nome deve ter ao menos 2 caracteres'),
  description: z.string().optional(),
})
type FormValues = z.infer<typeof schema>

interface Props {
  open:     boolean
  onClose:  () => void
  onCreated: (uuid: string, name: string) => void
}

export function QuickCreateBrandModal({ open, onClose, onCreated }: Props) {
  const { mutate, isPending } = useCreateBrand()
  const firstInputRef = useRef<HTMLInputElement>(null)

  const { register, handleSubmit, reset, formState: { errors } } = useForm<FormValues>({
    resolver: zodResolver(schema),
  })

  useEffect(() => {
    if (open) {
      reset()
      setTimeout(() => firstInputRef.current?.focus(), 50)
    }
  }, [open, reset])

  function onSubmit(values: FormValues) {
    mutate(
      { name: values.name, description: values.description || undefined, is_active: true },
      {
        onSuccess: (brand) => {
          onCreated(brand.uuid, brand.name)
          onClose()
        },
      }
    )
  }

  if (!open) return null

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      {/* backdrop */}
      <div className="absolute inset-0 bg-black/50" onClick={onClose} />

      <div className="relative z-10 w-full max-w-md rounded-lg border bg-background p-6 shadow-xl">
        {/* header */}
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-semibold">Nova Marca</h2>
          <button
            type="button"
            onClick={onClose}
            className="rounded-md p-1 hover:bg-muted transition-colors"
            aria-label="Fechar"
          >
            <X className="h-4 w-4" />
          </button>
        </div>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="brand-name">Nome *</Label>
            <Input
              id="brand-name"
              placeholder="Nome da marca"
              {...register('name')}
              ref={(el) => {
                register('name').ref(el)
                ;(firstInputRef as React.MutableRefObject<HTMLInputElement | null>).current = el
              }}
            />
            {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="brand-desc">Descrição</Label>
            <Input id="brand-desc" placeholder="Opcional" {...register('description')} />
          </div>

          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" onClick={onClose} disabled={isPending}>
              Cancelar
            </Button>
            <Button type="submit" disabled={isPending}>
              {isPending ? 'Criando…' : 'Criar Marca'}
            </Button>
          </div>
        </form>
      </div>
    </div>,
    document.body
  )
}
