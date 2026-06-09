'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Plus, Pencil, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import { useBrands, useDeleteBrand } from '@/features/catalog/hooks'
import { ROUTES } from '@/constants'

export default function BrandsPage() {
  const [deleteUuid, setDeleteUuid] = useState<string | null>(null)

  const { data: brands = [], isLoading }                     = useBrands()
  const { mutate: deleteBrand, isPending: isDeleting } = useDeleteBrand()

  function handleDeleteConfirm() {
    if (!deleteUuid) return
    deleteBrand(deleteUuid, {
      onSuccess: () => {
        toast.success('Marca excluída com sucesso.')
        setDeleteUuid(null)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao excluir marca.')
        setDeleteUuid(null)
      },
    })
  }

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Marcas"
        description="Gerencie as marcas do catálogo."
        actions={
          <Button asChild>
            <Link href={`${ROUTES.BRANDS}/create`}>
              <Plus className="mr-2 h-4 w-4" />
              Nova Marca
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
              <th className="px-4 py-3 font-medium text-muted-foreground">Site</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Ativo</th>
              <th className="px-4 py-3 font-medium text-muted-foreground">Ações</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 4 }).map((_, i) => (
                <tr key={i} className="border-b last:border-0">
                  {Array.from({ length: 5 }).map((__, j) => (
                    <td key={j} className="px-4 py-3">
                      <Skeleton className="h-4 w-full" />
                    </td>
                  ))}
                </tr>
              ))
            ) : brands.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-4 py-10 text-center text-muted-foreground">
                  Nenhuma marca cadastrada.
                </td>
              </tr>
            ) : (
              brands.map((brand) => (
                <tr key={brand.uuid} className="border-b last:border-0 hover:bg-muted/50 transition-colors">
                  <td className="px-4 py-3 font-mono text-xs text-muted-foreground">
                    {brand.code ?? '—'}
                  </td>
                  <td className="px-4 py-3 font-medium">{brand.name}</td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {brand.website_url ? (
                      <a
                        href={brand.website_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="hover:underline text-primary"
                      >
                        {brand.website_url}
                      </a>
                    ) : '—'}
                  </td>
                  <td className="px-4 py-3">
                    {brand.is_active ? (
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
                    <div className="flex items-center gap-1">
                      <Button variant="ghost" size="icon" asChild>
                        <Link href={`${ROUTES.BRANDS}/${brand.uuid}/edit`} aria-label="Editar marca">
                          <Pencil className="h-4 w-4" />
                        </Link>
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        className="text-destructive hover:text-destructive"
                        aria-label="Excluir marca"
                        onClick={() => setDeleteUuid(brand.uuid)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
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
        title="Excluir marca?"
        description="Esta ação não pode ser desfeita."
        confirmLabel="Excluir"
      />
    </div>
  )
}
