'use client'

import { useState } from 'react'
import Link from 'next/link'
import { ChevronLeft } from 'lucide-react'
import { useRouter } from 'next/navigation'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { useCreateCategory, useCategories } from '@/features/catalog/hooks'
import { ROUTES } from '@/constants'
import type { Category } from '@store/shared-types'

function flattenCategories(categories: Category[], depth = 0): Array<{ uuid: string; label: string }> {
  const result: Array<{ uuid: string; label: string }> = []
  for (const cat of categories) {
    result.push({ uuid: cat.uuid, label: `${'— '.repeat(depth)}${cat.name}` })
    if (cat.children && cat.children.length > 0) {
      result.push(...flattenCategories(cat.children, depth + 1))
    }
  }
  return result
}

export default function CreateCategoryPage() {
  const router = useRouter()
  const { mutate: createCategory, isPending } = useCreateCategory()
  const { data: categories = [] }             = useCategories()

  const [name,     setName]     = useState('')
  const [parentId, setParentId] = useState('')
  const [isActive, setIsActive] = useState(true)

  const flatCategories = flattenCategories(categories)

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!name.trim()) return

    createCategory(
      {
        name:      name.trim(),
        parent_id: parentId || undefined,
        is_active: isActive,
      },
      {
        onSuccess: () => {
          toast.success('Categoria criada com sucesso.')
          router.push(ROUTES.CATEGORIES)
        },
        onError: (err) => {
          toast.error(err instanceof Error ? err.message : 'Erro ao criar categoria.')
        },
      },
    )
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Nova Categoria"
        description="Preencha os dados da nova categoria."
        actions={
          <Button variant="outline" asChild>
            <Link href={ROUTES.CATEGORIES}>
              <ChevronLeft className="mr-1.5 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />

      <form onSubmit={handleSubmit} className="space-y-6">
        <AppCard title="Dados da Categoria">
          <div className="grid gap-4 sm:grid-cols-2 max-w-2xl">
            <div className="sm:col-span-2 space-y-1.5">
              <Label htmlFor="name">Nome *</Label>
              <Input
                id="name"
                placeholder="Nome da categoria"
                value={name}
                onChange={(e) => setName(e.target.value)}
                required
              />
            </div>

            <div className="sm:col-span-2 space-y-1.5">
              <Label htmlFor="parent_id">Categoria Pai</Label>
              <select
                id="parent_id"
                value={parentId}
                onChange={(e) => setParentId(e.target.value)}
                className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
              >
                <option value="">Sem categoria pai (raiz)</option>
                {flatCategories.map((cat) => (
                  <option key={cat.uuid} value={cat.uuid}>{cat.label}</option>
                ))}
              </select>
            </div>

            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                id="is_active"
                checked={isActive}
                onChange={(e) => setIsActive(e.target.checked)}
                className="accent-primary"
              />
              <Label htmlFor="is_active" className="cursor-pointer">Categoria ativa</Label>
            </div>
          </div>
        </AppCard>

        <div className="flex gap-2">
          <Button type="submit" disabled={isPending || !name.trim()}>
            {isPending ? 'Salvando…' : 'Criar Categoria'}
          </Button>
        </div>
      </form>
    </div>
  )
}
