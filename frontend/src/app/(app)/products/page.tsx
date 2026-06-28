'use client'

import { useState, useEffect, useCallback } from 'react'
import Link from 'next/link'
import {
  Plus, Search, LayoutGrid, List, Filter, X,
  Eye, Pencil, Package, Star, Tag,
} from 'lucide-react'
import { toast } from 'sonner'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import { ProductTable } from '@/features/catalog/components/product-table'
import {
  useProducts,
  useDeleteProduct,
  useBrands,
  useCategories,
} from '@/features/catalog/hooks'
import { apiPost } from '@/lib/api-client'
import { ROUTES } from '@/constants'
import type { ProductFilters } from '@/services/catalog.service'
import type { Product } from '@store/shared-types'

// ── Types ─────────────────────────────────────────────────────────────────────

type ViewMode = 'table' | 'grid'

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatBRL(value: number | null | undefined): string {
  if (value == null) return '—'
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(value)
}

function getProductPrice(product: Product): number | null {
  if (product.base_price != null) return product.base_price
  if (product.variants && product.variants.length > 0) {
    const def = product.variants.find((v) => v.is_default) ?? product.variants[0]
    return def ? def.sale_price : null
  }
  return null
}

const STATUS_STYLES: Record<string, string> = {
  draft:    'bg-gray-100 text-gray-700 border-gray-200',
  active:   'bg-green-50 text-green-700 border-green-200',
  inactive: 'bg-yellow-50 text-yellow-700 border-yellow-200',
  archived: 'bg-red-50 text-red-700 border-red-200',
  seasonal: 'bg-blue-50 text-blue-700 border-blue-200',
}

// ── Select wrapper (native) ───────────────────────────────────────────────────

function NativeSelect({
  value,
  onChange,
  children,
}: {
  value: string
  onChange: (v: string) => void
  children: React.ReactNode
}) {
  return (
    <select
      value={value}
      onChange={(e) => onChange(e.target.value)}
      className="rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
    >
      {children}
    </select>
  )
}

// ── Filter panel (reutilizado no inline + overlay mobile) ─────────────────────

interface FilterPanelProps {
  status:     string
  type:       string
  brandId:    string
  categoryId: string
  featured:   boolean
  onSale:     boolean
  brands:     { uuid: string; name: string }[]
  categories: { uuid: string; name: string; parent_id?: string | null }[]
  onChange:   (key: string, value: string | boolean) => void
}

function FilterPanel({
  status, type, brandId, categoryId, featured, onSale,
  brands, categories, onChange,
}: FilterPanelProps) {
  const rootCategories = categories.filter((c) => !c.parent_id)

  return (
    <div className="flex flex-wrap gap-3">
      {/* Status */}
      <NativeSelect value={status} onChange={(v) => onChange('status', v)}>
        <option value="">Todos os status</option>
        <option value="draft">Rascunho</option>
        <option value="active">Ativo</option>
        <option value="inactive">Inativo</option>
        <option value="archived">Arquivado</option>
        <option value="seasonal">Sazonal</option>
      </NativeSelect>

      {/* Tipo */}
      <NativeSelect value={type} onChange={(v) => onChange('type', v)}>
        <option value="">Todos os tipos</option>
        <option value="simple">Simples</option>
        <option value="variable">Variável</option>
        <option value="kit">Kit</option>
      </NativeSelect>

      {/* Marca */}
      {brands.length > 0 && (
        <NativeSelect value={brandId} onChange={(v) => onChange('brandId', v)}>
          <option value="">Todas as marcas</option>
          {brands.map((b) => (
            <option key={b.uuid} value={b.uuid}>{b.name}</option>
          ))}
        </NativeSelect>
      )}

      {/* Categoria */}
      {rootCategories.length > 0 && (
        <NativeSelect value={categoryId} onChange={(v) => onChange('categoryId', v)}>
          <option value="">Todas as categorias</option>
          {rootCategories.map((parent) => (
            <optgroup key={parent.uuid} label={parent.name}>
              <option value={parent.uuid}>{parent.name}</option>
              {categories
                .filter((c) => c.parent_id === parent.uuid)
                .map((child) => (
                  <option key={child.uuid} value={child.uuid}>└ {child.name}</option>
                ))}
            </optgroup>
          ))}
        </NativeSelect>
      )}

      {/* Toggle: Destaques */}
      <button
        type="button"
        onClick={() => onChange('featured', !featured)}
        className={`flex items-center gap-1.5 rounded-md border px-3 py-2 text-sm transition-colors ${
          featured
            ? 'border-yellow-300 bg-yellow-50 text-yellow-700'
            : 'border-input bg-background text-foreground hover:bg-muted'
        }`}
      >
        <Star className="h-3.5 w-3.5" />
        Destaques
      </button>

      {/* Toggle: Em promoção */}
      <button
        type="button"
        onClick={() => onChange('onSale', !onSale)}
        className={`flex items-center gap-1.5 rounded-md border px-3 py-2 text-sm transition-colors ${
          onSale
            ? 'border-red-300 bg-red-50 text-red-600'
            : 'border-input bg-background text-foreground hover:bg-muted'
        }`}
      >
        <Tag className="h-3.5 w-3.5" />
        Em promoção
      </button>
    </div>
  )
}

// ── Grid card ─────────────────────────────────────────────────────────────────

function ProductCard({ product }: { product: Product }) {
  const primary = product.images?.find((i) => i.is_primary) ?? product.images?.[0]

  return (
    <div className="group relative overflow-hidden rounded-lg border bg-card transition-shadow hover:shadow-md">
      {/* Imagem */}
      <Link href={`${ROUTES.PRODUCTS}/${product.uuid}`} className="block">
        <div className="relative aspect-video overflow-hidden bg-muted">
          {primary ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={primary.thumbnail_url ?? primary.url}
              alt={primary.alt_text ?? product.name}
              className="h-full w-full object-cover transition-transform group-hover:scale-105"
              onError={(e) => {
                const target = e.currentTarget
                if (primary.thumbnail_url && target.src !== primary.url) {
                  target.src = primary.url
                } else {
                  target.style.display = 'none'
                }
              }}
            />
          ) : (
            <div className="flex h-full w-full items-center justify-center">
              <Package className="h-10 w-10 text-muted-foreground/30" />
            </div>
          )}

          {/* Hover overlay */}
          <div className="absolute inset-0 flex items-center justify-center gap-2 bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
            <Button
              size="icon"
              variant="secondary"
              asChild
              title="Visualizar"
              className="h-8 w-8"
            >
              <Link href={`${ROUTES.PRODUCTS}/${product.uuid}`}>
                <Eye className="h-4 w-4" />
              </Link>
            </Button>
            <Button
              size="icon"
              variant="secondary"
              asChild
              title="Editar"
              className="h-8 w-8"
            >
              <Link href={`${ROUTES.PRODUCTS}/${product.uuid}/edit`}>
                <Pencil className="h-4 w-4" />
              </Link>
            </Button>
          </div>
        </div>
      </Link>

      {/* Info */}
      <div className="p-3">
        <div className="mb-1 flex items-start justify-between gap-2">
          <Link
            href={`${ROUTES.PRODUCTS}/${product.uuid}`}
            className="line-clamp-2 text-sm font-medium leading-tight hover:underline"
          >
            {product.name}
          </Link>
          <Badge
            variant="outline"
            className={`shrink-0 text-[10px] ${STATUS_STYLES[product.status] ?? ''}`}
          >
            {product.status_label}
          </Badge>
        </div>

        {product.brand && (
          <p className="mb-1 text-xs text-muted-foreground">{product.brand.name}</p>
        )}

        <p className="font-mono text-[10px] text-muted-foreground/70">{product.code}</p>

        <p className="mt-1.5 text-sm font-semibold tabular-nums">
          {formatBRL(getProductPrice(product))}
        </p>
      </div>
    </div>
  )
}

// ── Grid skeleton ─────────────────────────────────────────────────────────────

function GridSkeleton() {
  return (
    <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
      {Array.from({ length: 8 }).map((_, i) => (
        <div key={i} className="overflow-hidden rounded-lg border">
          <Skeleton className="aspect-video w-full" />
          <div className="space-y-2 p-3">
            <Skeleton className="h-4 w-3/4" />
            <Skeleton className="h-3 w-1/2" />
            <Skeleton className="h-4 w-1/3" />
          </div>
        </div>
      ))}
    </div>
  )
}

// ── Mobile filter overlay (sem Sheet, implementação própria) ──────────────────

interface MobileFilterOverlayProps {
  open:         boolean
  onClose:      () => void
  activeCount:  number
  children:     React.ReactNode
}

function MobileFilterOverlay({ open, onClose, activeCount, children }: MobileFilterOverlayProps) {
  // Fechar com Escape
  useEffect(() => {
    if (!open) return
    function handleKey(e: KeyboardEvent) {
      if (e.key === 'Escape') onClose()
    }
    document.addEventListener('keydown', handleKey)
    return () => document.removeEventListener('keydown', handleKey)
  }, [open, onClose])

  if (!open) return null

  return (
    <>
      {/* Backdrop */}
      <div
        className="fixed inset-0 z-40 bg-black/50 md:hidden"
        onClick={onClose}
        aria-hidden="true"
      />

      {/* Painel lateral */}
      <div
        role="dialog"
        aria-modal="true"
        aria-label="Filtros"
        className="fixed inset-y-0 right-0 z-50 flex w-80 max-w-full flex-col bg-background shadow-xl md:hidden"
      >
        {/* Header */}
        <div className="flex items-center justify-between border-b px-4 py-3">
          <div className="flex items-center gap-2 font-semibold">
            <Filter className="h-4 w-4" />
            Filtros
            {activeCount > 0 && (
              <Badge className="h-5 px-1.5 text-xs">{activeCount}</Badge>
            )}
          </div>
          <Button variant="ghost" size="icon" onClick={onClose} aria-label="Fechar filtros">
            <X className="h-4 w-4" />
          </Button>
        </div>

        {/* Conteúdo */}
        <div className="flex-1 overflow-y-auto p-4">
          <div className="flex flex-col gap-3">{children}</div>
        </div>
      </div>
    </>
  )
}

// ── Barra de ações em lote ────────────────────────────────────────────────────

interface BulkActionBarProps {
  count:       number
  onActivate:  () => void
  onDeactivate:() => void
  onExport:    () => void
  onClear:     () => void
  isWorking:   boolean
}

function BulkActionBar({
  count, onActivate, onDeactivate, onExport, onClear, isWorking,
}: BulkActionBarProps) {
  return (
    <div className="no-print fixed inset-x-0 bottom-0 z-30 flex items-center justify-between gap-3 border-t bg-background px-4 py-3 shadow-lg md:px-6">
      <span className="text-sm font-medium">
        {count} selecionado{count !== 1 ? 's' : ''}
      </span>
      <div className="flex items-center gap-2">
        <Button
          size="sm"
          variant="outline"
          onClick={onActivate}
          disabled={isWorking}
        >
          Ativar
        </Button>
        <Button
          size="sm"
          variant="outline"
          onClick={onDeactivate}
          disabled={isWorking}
        >
          Desativar
        </Button>
        <Button
          size="sm"
          variant="outline"
          onClick={onExport}
          disabled={isWorking}
        >
          Exportar CSV
        </Button>
        <Button
          size="sm"
          variant="ghost"
          onClick={onClear}
          disabled={isWorking}
        >
          <X className="mr-1 h-3.5 w-3.5" />
          Desmarcar
        </Button>
      </div>
    </div>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function ProductsPage() {
  // ── View mode (persistido) ──────────────────────────────────────────────────
  const [viewMode, setViewMode] = useState<ViewMode>('table')

  useEffect(() => {
    const saved = localStorage.getItem('products-view-mode')
    if (saved === 'grid' || saved === 'table') setViewMode(saved)
  }, [])

  function toggleViewMode(mode: ViewMode) {
    setViewMode(mode)
    localStorage.setItem('products-view-mode', mode)
  }

  // ── Filtros ─────────────────────────────────────────────────────────────────
  const [search,     setSearch]     = useState('')
  const [status,     setStatus]     = useState('')
  const [type,       setType]       = useState('')
  const [brandId,    setBrandId]    = useState('')
  const [categoryId, setCategoryId] = useState('')
  const [featured,   setFeatured]   = useState(false)
  const [onSale,     setOnSale]     = useState(false)
  const [page,       setPage]       = useState(1)

  // ── Misc state ──────────────────────────────────────────────────────────────
  const [deleteUuid,      setDeleteUuid]      = useState<string | null>(null)
  const [filterOpen,      setFilterOpen]      = useState(false)
  const [selectedUuids,   setSelectedUuids]   = useState<Set<string>>(new Set())
  const [isBulkWorking,   setIsBulkWorking]   = useState(false)

  // ── Dados ───────────────────────────────────────────────────────────────────
  const filters: ProductFilters = {
    q:           search      || undefined,
    status:      status      || undefined,
    type:        type        || undefined,
    brand_id:    brandId     || undefined,
    category_id: categoryId  || undefined,
    featured:    featured    || undefined,
    on_sale:     onSale      || undefined,
    page,
    per_page: 20,
  }

  const { data, isLoading }                              = useProducts(filters)
  const { mutate: deleteProduct, isPending: isDeleting } = useDeleteProduct()
  const { data: brands = [] }                            = useBrands()
  const { data: categories = [] }                        = useCategories()

  const products = data?.data ?? []
  const meta     = data?.meta

  // ── Filter helpers ──────────────────────────────────────────────────────────
  const hasActiveFilters = Boolean(search || status || type || brandId || categoryId || featured || onSale)
  const activeFilterCount = [search, status, type, brandId, categoryId, featured, onSale].filter(Boolean).length

  function handleFilterChange(key: string, value: string | boolean) {
    setPage(1)
    switch (key) {
      case 'status':     setStatus(value as string);     break
      case 'type':       setType(value as string);       break
      case 'brandId':    setBrandId(value as string);    break
      case 'categoryId': setCategoryId(value as string); break
      case 'featured':   setFeatured(value as boolean);  break
      case 'onSale':     setOnSale(value as boolean);    break
    }
  }

  function resetFilters() {
    setSearch('')
    setStatus('')
    setType('')
    setBrandId('')
    setCategoryId('')
    setFeatured(false)
    setOnSale(false)
    setPage(1)
  }

  // ── Delete ──────────────────────────────────────────────────────────────────
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

  // ── Bulk selection ──────────────────────────────────────────────────────────
  function handleSelectToggle(uuid: string) {
    setSelectedUuids((prev) => {
      const next = new Set(prev)
      if (next.has(uuid)) next.delete(uuid)
      else next.add(uuid)
      return next
    })
  }

  const handleSelectAll = useCallback(() => {
    const allSelected = products.every((p) => selectedUuids.has(p.uuid))
    if (allSelected) {
      setSelectedUuids((prev) => {
        const next = new Set(prev)
        products.forEach((p) => next.delete(p.uuid))
        return next
      })
    } else {
      setSelectedUuids((prev) => {
        const next = new Set(prev)
        products.forEach((p) => next.add(p.uuid))
        return next
      })
    }
  }, [products, selectedUuids])

  // ── Bulk actions ─────────────────────────────────────────────────────────────
  async function handleBulkStatus(newStatus: 'active' | 'inactive') {
    setIsBulkWorking(true)
    try {
      await apiPost('/catalog/products/bulk-status', {
        uuids: Array.from(selectedUuids),
        status: newStatus,
      })
      toast.success(
        newStatus === 'active'
          ? 'Produtos ativados com sucesso.'
          : 'Produtos desativados com sucesso.',
      )
      setSelectedUuids(new Set())
    } catch (err) {
      const isNotSupported =
        err instanceof Error &&
        (err.message.includes('404') || err.message.includes('422') ||
          err.message.toLowerCase().includes('not found'))
      if (isNotSupported) {
        toast.info('Ação em lote em breve disponível.')
      } else {
        toast.error(err instanceof Error ? err.message : 'Erro ao atualizar produtos.')
      }
    } finally {
      setIsBulkWorking(false)
    }
  }

  function handleBulkExport() {
    const uuidList = Array.from(selectedUuids).join(',')
    window.open(`/api/v1/catalog/products/export/csv?uuids=${uuidList}`, '_blank')
  }

  // ── Filter panel content (shared) ───────────────────────────────────────────
  const filterPanelProps: FilterPanelProps = {
    status,
    type,
    brandId,
    categoryId,
    featured,
    onSale,
    brands,
    categories,
    onChange: handleFilterChange,
  }

  // ── Render ──────────────────────────────────────────────────────────────────
  return (
    <>
      {/* Print: esconder elementos de UI */}
      <style>{`.no-print { display: none !important; }`}</style>

      <div className={`space-y-6 ${selectedUuids.size > 0 ? 'pb-20' : ''}`}>

        {/* Header */}
        <AppPageHeader
          title="Produtos"
          description="Gerencie o catálogo de produtos."
          actions={
            <div className="flex items-center gap-2">
              {/* Toggle grid/tabela */}
              <div className="no-print flex items-center rounded-md border">
                <button
                  type="button"
                  title="Visualização em tabela"
                  onClick={() => toggleViewMode('table')}
                  className={`rounded-l-md p-2 transition-colors ${
                    viewMode === 'table'
                      ? 'bg-primary text-primary-foreground'
                      : 'hover:bg-muted'
                  }`}
                >
                  <List className="h-4 w-4" />
                </button>
                <button
                  type="button"
                  title="Visualização em grade"
                  onClick={() => toggleViewMode('grid')}
                  className={`rounded-r-md p-2 transition-colors ${
                    viewMode === 'grid'
                      ? 'bg-primary text-primary-foreground'
                      : 'hover:bg-muted'
                  }`}
                >
                  <LayoutGrid className="h-4 w-4" />
                </button>
              </div>

              <Button asChild>
                <Link href={`${ROUTES.PRODUCTS}/create`}>
                  <Plus className="mr-2 h-4 w-4" />
                  Novo Produto
                </Link>
              </Button>
            </div>
          }
        />

        {/* Filtros ─────────────────────────────────────────────────────────── */}
        <div className="no-print flex flex-wrap items-center gap-3">
          {/* Busca — sempre visível */}
          <div className="relative w-full flex-1 md:max-w-xs">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              className="pl-9"
              placeholder="Buscar por nome, código…"
              value={search}
              onChange={(e) => { setSearch(e.target.value); setPage(1) }}
            />
          </div>

          {/* Filtros inline — ocultos em mobile */}
          <div className="hidden items-center gap-3 md:flex">
            <FilterPanel {...filterPanelProps} />
          </div>

          {/* Botão filtros — só em mobile */}
          <Button
            variant="outline"
            size="sm"
            className="flex items-center gap-1.5 md:hidden"
            onClick={() => setFilterOpen(true)}
          >
            <Filter className="h-4 w-4" />
            Filtros
            {activeFilterCount > 0 && (
              <Badge className="h-4 px-1 text-[10px]">{activeFilterCount}</Badge>
            )}
          </Button>

          {/* Limpar filtros */}
          {hasActiveFilters && (
            <Button variant="ghost" size="sm" onClick={resetFilters} className="gap-1">
              <X className="h-3.5 w-3.5" />
              Limpar filtros
            </Button>
          )}
        </div>

        {/* Mobile filter overlay */}
        <MobileFilterOverlay
          open={filterOpen}
          onClose={() => setFilterOpen(false)}
          activeCount={activeFilterCount}
        >
          {/* Dentro do overlay: filtros em coluna */}
          <NativeSelect value={status} onChange={(v) => { handleFilterChange('status', v) }}>
            <option value="">Todos os status</option>
            <option value="draft">Rascunho</option>
            <option value="active">Ativo</option>
            <option value="inactive">Inativo</option>
            <option value="archived">Arquivado</option>
            <option value="seasonal">Sazonal</option>
          </NativeSelect>

          <NativeSelect value={type} onChange={(v) => handleFilterChange('type', v)}>
            <option value="">Todos os tipos</option>
            <option value="simple">Simples</option>
            <option value="variable">Variável</option>
            <option value="kit">Kit</option>
          </NativeSelect>

          {brands.length > 0 && (
            <NativeSelect value={brandId} onChange={(v) => handleFilterChange('brandId', v)}>
              <option value="">Todas as marcas</option>
              {brands.map((b) => (
                <option key={b.uuid} value={b.uuid}>{b.name}</option>
              ))}
            </NativeSelect>
          )}

          {categories.filter((c) => !c.parent_id).length > 0 && (
            <NativeSelect value={categoryId} onChange={(v) => handleFilterChange('categoryId', v)}>
              <option value="">Todas as categorias</option>
              {categories.filter((c) => !c.parent_id).map((parent) => (
                <optgroup key={parent.uuid} label={parent.name}>
                  <option value={parent.uuid}>{parent.name}</option>
                  {categories
                    .filter((c) => c.parent_id === parent.uuid)
                    .map((child) => (
                      <option key={child.uuid} value={child.uuid}>└ {child.name}</option>
                    ))}
                </optgroup>
              ))}
            </NativeSelect>
          )}

          <button
            type="button"
            onClick={() => handleFilterChange('featured', !featured)}
            className={`flex w-full items-center gap-1.5 rounded-md border px-3 py-2 text-sm transition-colors ${
              featured
                ? 'border-yellow-300 bg-yellow-50 text-yellow-700'
                : 'border-input bg-background text-foreground hover:bg-muted'
            }`}
          >
            <Star className="h-3.5 w-3.5" />
            Destaques
          </button>

          <button
            type="button"
            onClick={() => handleFilterChange('onSale', !onSale)}
            className={`flex w-full items-center gap-1.5 rounded-md border px-3 py-2 text-sm transition-colors ${
              onSale
                ? 'border-red-300 bg-red-50 text-red-600'
                : 'border-input bg-background text-foreground hover:bg-muted'
            }`}
          >
            <Tag className="h-3.5 w-3.5" />
            Em promoção
          </button>

          {hasActiveFilters && (
            <Button
              variant="ghost"
              size="sm"
              className="w-full gap-1"
              onClick={() => { resetFilters(); setFilterOpen(false) }}
            >
              <X className="h-3.5 w-3.5" />
              Limpar filtros
            </Button>
          )}
        </MobileFilterOverlay>

        {/* Conteúdo principal ───────────────────────────────────────────────── */}
        {viewMode === 'table' ? (
          <ProductTable
            products={products}
            isLoading={isLoading}
            onDelete={(uuid) => setDeleteUuid(uuid)}
            selectedUuids={selectedUuids}
            onSelectToggle={handleSelectToggle}
            onSelectAll={handleSelectAll}
          />
        ) : (
          <>
            {isLoading ? (
              <GridSkeleton />
            ) : products.length === 0 ? (
              <div className="flex flex-col items-center justify-center rounded-md border py-16 text-muted-foreground">
                <Package className="mb-3 h-12 w-12 opacity-20" />
                <p>Nenhum produto encontrado.</p>
              </div>
            ) : (
              <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                {products.map((product) => (
                  <ProductCard key={product.uuid} product={product} />
                ))}
              </div>
            )}
          </>
        )}

        {/* Paginação ─────────────────────────────────────────────────────────── */}
        {meta && meta.last_page > 1 && (
          <div className="no-print flex items-center justify-between text-sm text-muted-foreground">
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

      {/* Barra de ações em lote */}
      {selectedUuids.size > 0 && (
        <BulkActionBar
          count={selectedUuids.size}
          onActivate={() => handleBulkStatus('active')}
          onDeactivate={() => handleBulkStatus('inactive')}
          onExport={handleBulkExport}
          onClear={() => setSelectedUuids(new Set())}
          isWorking={isBulkWorking}
        />
      )}
    </>
  )
}
