'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Plus, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import { useCategories, useDeleteCategory } from '@/features/catalog/hooks'
import { ROUTES } from '@/constants'
import type { Category } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

interface FlatCategory extends Category {
  depth:       number
  parentName?: string
}

function flattenCategories(categories: Category[], depth = 0, parentName?: string): FlatCategory[] {
  const result: FlatCategory[] = []
  for (const cat of categories) {
    result.push({ ...cat, depth, parentName })
    if (cat.children && cat.children.length > 0) {
      result.push(...flattenCategories(cat.children, depth + 1, cat.name))
    }
  }
  return result
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function CategoriesPage() {
  const [deleteUuid, setDeleteUuid] = useState<string | null>(null)

  const { data: categories = [], isLoading }                       = useCategories()
  const { mutate: deleteCategory, isPending: isDeleting } = useDeleteCategory()

  const flatCategories = flattenCategories(categories)

  function handleDeleteConfirm() {
    if (!deleteUuid) return
    deleteCategory(deleteUuid, {
      onSuccess: () => {
        toast.success('Categoria excluída com sucesso.')
        setDeleteUuid(null)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao excluir categoria.')
        setDeleteUuid(null)
      },
    })
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Categorias"
        description="Gerencie a árvore de categorias do catálogo."
        actions={
          <Button asChild>
            <Link href={`${ROUTES.CATEGORIES}/create`}>
              <Plus className="mr-2 h-4 w-4" />
              Nova Categoria
            </Link>
          </Button>
        }
      />

      <div className="rounded-md border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-muted/50 text-left">
              <th className="px-4 py-3 font-medium text-muted-foreground">Código</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Nome</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Pai</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Ativo</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Ações</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b last:border-0">
                  {Array.from({ length: 5 }).map((__, j) => (
                    <td key={j} className="px-4 py-3">
                      <Skeleton className="h-4 w-full" />
                    </td>
                  ))}
                </tr>
              ))
            ) : flatCategories.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-4 py-10 text-center text-muted-foreground">
                  Nenhuma categoria cadastrada.
                </td>
              </tr>
            ) : (
              flatCategories.map((cat) => (
                <tr key={cat.uuid} className="border-b last:border-0 hover:bg-muted/50 transition-colors">
                  <td className="px-4 py-3 font-mono text-xs text-muted-foreground">
                    {cat.code ?? '—'}
                  </td>
                  <td className="px-4 py-3 font-medium">
                    <span style={{ paddingLeft: `${cat.depth * 16}px` }}>
                      {cat.depth > 0 && <span className="text-muted-foreground mr-1">↳</span>}
                      {cat.name}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {cat.parentName ?? '—'}
                  </td>
                  <td className="px-4 py-3">
                    {cat.is_active ? (
                      <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">
                        Ativo
                      </Badge>
                    ) : (
                      <Badge variant="outline" className="bg-gray-100 text-gray-600 border-gray-200">
                        Inativo
                      </Badge>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <Button
                      variant="ghost"
                      size="icon"
                      className="text-destructive hover:text-destructive"
                      aria-label="Excluir categoria"
                      onClick={() => setDeleteUuid(cat.uuid)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      <ConfirmDialog
        open={Boolean(deleteUuid)}
        onOpenChange={(open) => { if (!open) setDeleteUuid(null) }}
        onConfirm={handleDeleteConfirm}
        loading={isDeleting}
        title="Excluir categoria?"
        description="Esta ação excluirá a categoria e possivelmente suas subcategorias."
        confirmLabel="Excluir"
      />
    </div>
  )
}
