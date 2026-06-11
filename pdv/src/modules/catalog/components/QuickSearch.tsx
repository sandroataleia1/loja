import { useState, useRef, useEffect, useCallback } from 'react'
import { Search, Barcode, X } from 'lucide-react'
import { Input }  from '@/components/ui/input'
import { Badge }  from '@/components/ui/badge'

import { useCatalogStore }   from '@/stores/catalogStore'
import { useCatalogSearch }  from '@/modules/catalog/hooks/useCatalogSearch'
import { useBarcode }        from '@/modules/catalog/hooks/useBarcode'
import { CategoryChips }     from './CategoryChips'
import { ProductCard }       from './ProductCard'
import { VariantSelector }   from './VariantSelector'

import type { CatalogProduct, CatalogVariant } from '@/types/catalog'
import { variantPrice }     from '@/types/catalog'
import type { CartableProduct } from '@/stores/cartStore'

/** Regex: "3×termo" ou "3*termo" — multiplicador de qtd */
const QTY_RE = /^(\d+)[x*](.*)$/i

interface Props {
  onAdd: (product: CartableProduct, qty: number, variantUuid?: string | null, attributes?: Record<string, string>) => void
}

export function QuickSearch({ onAdd }: Props) {
  const [query, setQuery]             = useState('')
  const [category, setCategory]       = useState<string | null>(null)
  const [selectedIdx, setSelIdx]      = useState(-1)
  const [variantProduct, setVProduct] = useState<CatalogProduct | null>(null)
  const [pendingQty, setPendingQty]   = useState(1)

  const inputRef = useRef<HTMLInputElement>(null)
  const { findByBarcode } = useBarcode()
  const categories        = useCatalogStore((s) => s.categories)

  // Parse multiplicador de quantidade: "3×camiseta" → qty=3, term="camiseta"
  const qtyMatch = QTY_RE.exec(query)
  const qty       = qtyMatch ? parseInt(qtyMatch[1], 10) : 1
  const term      = qtyMatch ? qtyMatch[2] : query

  const { results, loading } = useCatalogSearch(term, category)

  // Reset seleção quando resultados mudam
  useEffect(() => setSelIdx(-1), [results])

  // F2 sempre foca a busca
  useEffect(() => {
    const handler = (e: KeyboardEvent) => {
      if (e.key === 'F2') {
        e.preventDefault()
        inputRef.current?.focus()
        inputRef.current?.select()
      }
    }
    window.addEventListener('keydown', handler)
    return () => window.removeEventListener('keydown', handler)
  }, [])

  const selectProduct = useCallback(
    (product: CatalogProduct, quantity = qty) => {
      if (product.variants.length > 0) {
        // Produto com variantes → abrir seletor
        setPendingQty(quantity)
        setVProduct(product)
        return
      }

      const cartable: CartableProduct = {
        uuid:  product.uuid,
        name:  product.name,
        sku:   product.sku,
        price: product.price,
      }
      onAdd(cartable, quantity, null)
      setQuery('')
      inputRef.current?.focus()
    },
    [qty, onAdd],
  )

  const confirmVariant = useCallback(
    (product: CatalogProduct, variant: CatalogVariant) => {
      const cartable: CartableProduct = {
        uuid:  product.uuid,
        name:  product.name,
        sku:   variant.sku,
        price: variantPrice(product, variant),
      }
      const attrs = typeof variant.attributes === 'object' && !Array.isArray(variant.attributes)
        ? (variant.attributes as Record<string, string>)
        : undefined
      onAdd(cartable, pendingQty, variant.uuid, attrs)
      setVProduct(null)
      setQuery('')
      inputRef.current?.focus()
    },
    [pendingQty, onAdd],
  )

  // Auto-add via barcode: exata + padrão numérico ≥ 8 dígitos
  useEffect(() => {
    if (!/^\d{8,20}$/.test(term)) return

    let cancelled = false
    findByBarcode(term).then((result) => {
      if (cancelled || !result) return
      selectProduct(result.product, qty)
    })
    return () => { cancelled = true }
  }, [term, qty, findByBarcode, selectProduct])

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (results.length === 0) return

    if (e.key === 'Enter') {
      e.preventDefault()
      const idx = selectedIdx >= 0 ? selectedIdx : 0
      if (results[idx]) selectProduct(results[idx], qty)
      return
    }
    if (e.key === 'ArrowDown') {
      e.preventDefault()
      setSelIdx((i) => Math.min(i + 1, results.length - 1))
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault()
      setSelIdx((i) => Math.max(i - 1, 0))
    }
  }

  const isEmpty    = results.length === 0 && !loading
  const hasQtyHint = qty > 1

  return (
    <>
      <div className="flex flex-col h-full">
        {/* Barra de busca */}
        <div className="px-3 pt-3 pb-2 border-b bg-card/30 shrink-0">
          <div className="relative">
            <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
            <Input
              ref={inputRef}
              autoFocus
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              onKeyDown={handleKeyDown}
              placeholder="Produto, SKU ou código de barras…"
              className="pl-10 pr-20 h-12 text-base bg-background"
              autoComplete="off"
              spellCheck={false}
            />
            <div className="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
              {hasQtyHint && (
                <Badge variant="secondary" className="text-xs font-mono px-1.5">{qty}×</Badge>
              )}
              {query ? (
                <button
                  onClick={() => { setQuery(''); inputRef.current?.focus() }}
                  className="text-muted-foreground hover:text-foreground transition-colors"
                >
                  <X className="w-4 h-4" />
                </button>
              ) : (
                <Barcode className="w-4 h-4 text-muted-foreground/50" />
              )}
            </div>
          </div>

          {!query && (
            <p className="text-xs text-muted-foreground/50 mt-1.5 ml-1">
              <kbd className="kbd">F2</kbd>
              {' '}para focar · <span className="font-mono">3×</span>{' '}antes para adicionar em quantidade
            </p>
          )}
        </div>

        {/* Chips de categoria */}
        <CategoryChips
          categories={categories}
          selected={category}
          onSelect={setCategory}
        />

        {/* Grade */}
        {loading ? (
          <div className="flex-1 flex items-center justify-center">
            <div className="text-center space-y-2">
              <div className="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin mx-auto" />
              <p className="text-xs text-muted-foreground">Carregando catálogo…</p>
            </div>
          </div>
        ) : isEmpty ? (
          <div className="flex-1 flex flex-col items-center justify-center gap-3 text-muted-foreground">
            {term ? (
              <>
                <Search className="w-10 h-10 opacity-20" />
                <div className="text-center">
                  <p className="text-sm font-medium">Produto não encontrado</p>
                  <p className="text-xs mt-0.5 opacity-70">"{term}"</p>
                </div>
              </>
            ) : (
              <>
                <Barcode className="w-12 h-12 opacity-15" />
                <div className="text-center">
                  <p className="text-sm font-medium opacity-50">Aguardando busca ou leitura</p>
                  <p className="text-xs mt-0.5 opacity-40">
                    Escaneie um código de barras ou busque pelo nome
                  </p>
                </div>
              </>
            )}
          </div>
        ) : (
          <div className="flex-1 overflow-auto p-3">
            <div className="grid grid-cols-[repeat(auto-fill,minmax(160px,1fr))] gap-2">
              {results.map((product, idx) => (
                <ProductCard
                  key={product.uuid}
                  product={product}
                  selected={idx === selectedIdx}
                  onSelect={(p) => selectProduct(p, qty)}
                />
              ))}
            </div>
          </div>
        )}
      </div>

      <VariantSelector
        product={variantProduct}
        onConfirm={confirmVariant}
        onClose={() => setVProduct(null)}
      />
    </>
  )
}
