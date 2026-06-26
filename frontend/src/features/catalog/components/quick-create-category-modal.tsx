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
import { useCreateCategory, useCategories } from '@/features/catalog/hooks'
import type { Category } from '@store/shared-types'

const schema = z.object({
  name:      z.string().min(2, 'Nome deve ter ao menos 2 caracteres'),
  parent_id: z.string().optional(),
})
type FormValues = z.infer<typeof schema>

interface FlatCategory { uuid: string; label: string }

function flatten(cats: Category[], depth = 0): FlatCategory[] {
  const result: FlatCategory[] = []
  for (const c of cats) {
    result.push({ uuid: c.uuid, label: ' '.repeat(depth * 3) + (depth > 0 ? '↳ ' : '') + c.name })
    if (c.children?.length) result.push(...flatten(c.children, depth + 1))
  }
  return result
}

interface Props {
  open:      boolean
  onClose:   () => void
  onCreated: (uuid: string, name: string) => void
}

export function QuickCreateCategoryModal({ open, onClose, onCreated }: Props) {
  const { mutate, isPending } = useCreateCategory()
  const { data: categories = [] } = useCategories()
  const flat = flatten(categories)
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
      {
        name:      values.name,
        parent_id: values.parent_id || undefined,
        is_active: true,
      },
      {
        onSuccess: (category) => {
          onCreated(category.uuid, category.name)
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
          <h2 className="text-lg font-semibold">Nova Categoria</h2>
          <button
            type="button"
            onClick={onClose}
            className="rounded-md p-1 hover:bg-muted transition-colors"
            aria-label="Fechar"
          >
            <X className="h-4 w-4" />
          </button>
        </div>

        <form onSubmit={(e) => { e.stopPropagation(); void handleSubmit(onSubmit)(e) }} className="space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="cat-name">Nome *</Label>
            <Input
              id="cat-name"
              placeholder="Nome da categoria"
              {...register('name')}
              ref={(el) => {
                register('name').ref(el)
                ;(firstInputRef as React.MutableRefObject<HTMLInputElement | null>).current = el
              }}
            />
            {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
          </div>

          {flat.length > 0 && (
            <div className="space-y-1.5">
              <Label htmlFor="cat-parent">Categoria Pai</Label>
              <select
                id="cat-parent"
                {...register('parent_id')}
                className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
              >
                <option value="">Sem categoria pai (raiz)</option>
                {flat.map((c) => (
                  <option key={c.uuid} value={c.uuid}>{c.label}</option>
                ))}
              </select>
            </div>
          )}

          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" onClick={onClose} disabled={isPending}>
              Cancelar
            </Button>
            <Button type="submit" disabled={isPending}>
              {isPending ? 'Criando…' : 'Criar Categoria'}
            </Button>
          </div>
        </form>
      </div>
    </div>,
    document.body
  )
}
