'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Plus, Search } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import { ProductTable } from '@/features/catalog/components/product-table'
import { useProducts, useDeleteProduct, useBrands, useCategories } from '@/features/catalog/hooks'
import { ROUTES } from '@/constants'
import type { ProductFilters } from '@/services/catalog.service'

export default function ProductsPage() {
  const [search,     setSearch]     = useState('')
  const [status,     setStatus]     = useState('')
  const [type,       setType]       = useState('')
  const [brandId,    setBrandId]    = useState('')
  const [categoryId, setCategoryId] = useState('')
  const [page,       setPage]       = useState(1)
  const [deleteUuid, setDeleteUuid] = useState<string | null>(null)

  const filters: ProductFilters = {
    q:           search      || undefined,
    status:      status      || undefined,
    type:        type        || undefined,
    brand_id:    brandId     || undefined,
    category_id: categoryId  || undefined,
    page,
    per_page: 20,
  }

  const { data, isLoading }                              = useProducts(filters)
  const { mutate: deleteProduct, isPending: isDeleting } = useDeleteProduct()
  const { data: brands = [] }                            = useBrands()
  const { data: categories = [] }                        = useCategories()

  const products = data?.data ?? []
  const meta     = data?.meta

  function handleDeleteConfirm() {
    if (!deleteUuid) return
    deleteProduct(deleteUuid, {
      onSuccess: () => {
        toast.success('Produto excluído com sucesso.')
        setDeleteUuid(null)
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao excluir produto.')
        setDeleteUuid(null)
      },
    })
  }

  function handleSearchChange(e: React.ChangeEvent<HTMLInputElement>) {
    setSearch(e.target.value)
    setPage(1)
  }

  function resetFilters() {
    setSearch(''); setStatus(''); setType(''); setBrandId(''); setCategoryId(''); setPage(1)
  }

  const hasActiveFilters = Boolean(search || status || type || brandId || categoryId)

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Produtos"
        description="Gerencie o catálogo de produtos."
        actions={
          <Button asChild>
            <Link href={`${ROUTES.PRODUCTS}/create`}>
              <Plus className="mr-2 h-4 w-4" />
              Novo Produto
            </Link>
          </Button>
        }
      />

      {/* Filters */}
      <div className="flex flex-wrap items-center gap-3">
        <div className="relative max-w-xs flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
          <Input
            className="pl-9"
            placeholder="Buscar por nome, código…"
            value={search}
            onChange={handleSearchChange}
          />
        </div>
        <select
          value={status}
          onChange={(e) => { setStatus(e.target.value); setPage(1) }}
          className="rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">Todos os status</option>
          <option value="draft">Rascunho</option>
          <option value="active">Ativo</option>
          <option value="inactive">Inativo</option>
          <option value="archived">Arquivado</option>
          <option value="seasonal">Sazonal</option>
        </select>
        <select
          value={type}
          onChange={(e) => { setType(e.target.value); setPage(1) }}
          className="rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">Todos os tipos</option>
          <option value="simple">Simples</option>
          <option value="variable">Variável</option>
          <option value="kit">Kit</option>
        </select>
        {brands.length > 0 && (
          <select
            value={brandId}
            onChange={(e) => { setBrandId(e.target.value); setPage(1) }}
            className="rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
          >
            <option value="">Todas as marcas</option>
            {brands.map((b) => (
              <option key={b.uuid} value={b.uuid}>{b.name}</option>
            ))}
          </select>
        )}
        {categories.length > 0 && (
          <select
            value={categoryId}
            onChange={(e) => { setCategoryId(e.target.value); setPage(1) }}
            className="rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
          >
            <option value="">Todas as categorias</option>
            {categories.filter((c) => !c.parent_id).map((parent) => (
              <optgroup key={parent.uuid} label={parent.name}>
                <option value={parent.uuid}>{parent.name}</option>
                {categories.filter((c) => c.parent_id === parent.uuid).map((child) => (
                  <option key={child.uuid} value={child.uuid}>└ {child.name}</option>
                ))}
              </optgroup>
            ))}
          </select>
        )}
        {hasActiveFilters && (
          <Button variant="ghost" size="sm" onClick={resetFilters}>
            Limpar filtros
          </Button>
        )}
      </div>

      {/* Table */}
      <ProductTable
        products={products}
        isLoading={isLoading}
        onDelete={(uuid) => setDeleteUuid(uuid)}
      />

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>
            Mostrando {products.length} de {meta.total} produtos
          </span>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={page <= 1}
              onClick={() => setPage((p) => p - 1)}
            >
              Anterior
            </Button>
            <span className="px-2">
              {page} / {meta.last_page}
            </span>
            <Button
              variant="outline"
              size="sm"
              disabled={page >= meta.last_page}
              onClick={() => setPage((p) => p + 1)}
            >
              Próximo
            </Button>
          </div>
        </div>
      )}

      {/* Delete confirmation */}
      <ConfirmDialog
        open={Boolean(deleteUuid)}
        onOpenChange={(open) => { if (!open) setDeleteUuid(null) }}
        onConfirm={handleDeleteConfirm}
        loading={isDeleting}
        title="Excluir produto?"
        description="Esta ação não pode ser desfeita. Todas as variantes e dados do produto serão removidos permanentemente."
        confirmLabel="Excluir"
      />
    </div>
  )
}
