'use client'

import { useState, useEffect, useCallback, useRef } from 'react'
import { Package } from 'lucide-react'
import { Skeleton } from '@/components/ui/skeleton'
import { cn } from '@/lib/utils'
import { usePdvCategories, useProductSearch } from '@/features/pdv/hooks'
import { usePdvCartStore } from '@/features/pdv/stores/pdvCartStore'
import { ProductSearchInput } from './ProductSearchInput'
import { VariantPicker } from './VariantPicker'
import type { Product, ProductVariant } from '@/types/shared-types'

const fmtBRL = (cents: number) =>
  new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)

export function ProductGrid() {
  const addItem = usePdvCartStore((s) => s.addItem)

  const [query,    setQuery]    = useState('')
  const [search,   setSearch]   = useState('')       // valor efetivo (pós-debounce ou Enter)
  const [catId,    setCatId]    = useState<string>('')
  const [picking,  setPicking]  = useState<Product | null>(null)  // produto para VariantPicker

  const { data: categories } = usePdvCategories()
  const { data: result, isFetching } = useProductSearch(search, catId)
  const products = result?.data ?? []

  // Debounce: 400ms após digitar → dispara busca
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null)
  useEffect(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current)
    debounceRef.current = setTimeout(() => setSearch(query), 400)
    return () => { if (debounceRef.current) clearTimeout(debounceRef.current) }
  }, [query])

  // Enter → busca imediata (barcode scanners)
  function handleSearch(v: string) {
    if (debounceRef.current) clearTimeout(debounceRef.current)
    setSearch(v)
  }

  function handleProductClick(product: Product) {
    const active = (product.variants ?? []).filter((v) => v.is_active)
    if (active.length === 0) return
    if (active.length === 1) {
      addToCart(product, active[0])
    } else {
      setPicking(product)
    }
  }

  function addToCart(product: Product, variant: ProductVariant) {
    addItem({
      productUuid:    product.uuid,
      variantUuid:    variant.uuid,
      name:           product.name,
      sku:            variant.sku,
      barcode:        variant.barcode,
      unitPriceCents: variant.price_cents,
      quantity:       1,
      discountCents:  0,
    })
  }

  const showEmpty   = !isFetching && (search.length > 0 || catId) && products.length === 0
  const showInitial = !isFetching && search.length === 0 && !catId && products.length === 0

  return (
    <div className="flex flex-col h-full overflow-hidden">
      {/* Search */}
      <div className="p-3 border-b shrink-0">
        <ProductSearchInput
          value={query}
          onChange={setQuery}
          onSearch={handleSearch}
          isLoading={isFetching}
        />
      </div>

      {/* Category chips */}
      {categories && categories.length > 0 && (
        <div className="flex gap-2 px-3 py-2 overflow-x-auto shrink-0 border-b scrollbar-none">
          <button
            onClick={() => setCatId('')}
            className={cn(
              'shrink-0 px-3 py-1 rounded-full text-xs font-medium border transition-colors',
              !catId
                ? 'bg-primary text-primary-foreground border-primary'
                : 'bg-transparent text-muted-foreground border-border hover:border-primary/50',
            )}
          >
            Todos
          </button>
          {categories.map((cat) => (
            <button
              key={cat.uuid}
              onClick={() => setCatId(cat.uuid === catId ? '' : cat.uuid)}
              className={cn(
                'shrink-0 px-3 py-1 rounded-full text-xs font-medium border transition-colors',
                catId === cat.uuid
                  ? 'bg-primary text-primary-foreground border-primary'
                  : 'bg-transparent text-muted-foreground border-border hover:border-primary/50',
              )}
            >
              {cat.name}
            </button>
          ))}
        </div>
      )}

      {/* Grid */}
      <div className="flex-1 overflow-y-auto p-3">
        {isFetching ? (
          <div className="grid grid-cols-3 gap-2">
            {Array.from({ length: 9 }).map((_, i) => (
              <Skeleton key={i} className="h-24 rounded-xl" />
            ))}
          </div>
        ) : showInitial ? (
          <div className="flex flex-col items-center justify-center h-full text-muted-foreground gap-3 py-16">
            <Package className="w-10 h-10 opacity-30" />
            <p className="text-sm">Digite para buscar ou filtre por categoria</p>
          </div>
        ) : showEmpty ? (
          <div className="flex flex-col items-center justify-center h-full text-muted-foreground gap-3 py-16">
            <Package className="w-8 h-8 opacity-30" />
            <p className="text-sm">Nenhum produto encontrado para &quot;{search}&quot;</p>
          </div>
        ) : (
          <div className="grid grid-cols-3 gap-2">
            {products.map((product) => {
              const firstVariant = (product.variants ?? []).find((v) => v.is_active)
              const price = firstVariant?.price_cents ?? 0
              const hasVariants = (product.variants ?? []).filter((v) => v.is_active).length > 1

              return (
                <button
                  key={product.uuid}
                  onClick={() => handleProductClick(product)}
                  className="group flex flex-col gap-1 rounded-xl border bg-card p-3 text-left transition-all hover:border-primary/40 hover:bg-accent active:scale-[0.97]"
                >
                  {/* Placeholder de imagem */}
                  <div className="flex items-center justify-center h-12 rounded-lg bg-muted/60 mb-1">
                    <Package className="w-5 h-5 text-muted-foreground/50" />
                  </div>
                  <p className="text-xs font-medium leading-tight line-clamp-2">{product.name}</p>
                  <p className="text-[10px] text-muted-foreground">{product.code}</p>
                  <div className="flex items-center justify-between mt-auto pt-1">
                    <span className="text-xs font-bold text-primary">{fmtBRL(price)}</span>
                    {hasVariants && (
                      <span className="text-[9px] text-muted-foreground bg-muted px-1.5 py-0.5 rounded-full">
                        variantes
                      </span>
                    )}
                  </div>
                </button>
              )
            })}
          </div>
        )}
      </div>

      {/* Variant picker */}
      {picking && (
        <VariantPicker
          product={picking}
          onSelect={(v) => addToCart(picking, v)}
          onClose={() => setPicking(null)}
        />
      )}
    </div>
  )
}
