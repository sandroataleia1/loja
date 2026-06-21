'use client'

import { useState, useEffect, useRef } from 'react'
import { createPortal } from 'react-dom'
import { Search, Barcode, Loader2, X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { catalogService } from '@/services/catalog.service'
import type { Product, ProductVariant } from '@store/shared-types'

export interface SelectedProduct {
  product_variant_id: string
  name_snapshot:      string
  sku_snapshot:       string
  unit_price_cents:   number
}

interface Props {
  open:     boolean
  onClose:  () => void
  onSelect: (product: SelectedProduct) => void
}

export function ProductSearchModal({ open, onClose, onSelect }: Props) {
  const [codeQuery,   setCodeQuery]   = useState('')
  const [descQuery,   setDescQuery]   = useState('')
  const [results,     setResults]     = useState<Product[]>([])
  const [loading,     setLoading]     = useState(false)
  const [codeLoading, setCodeLoading] = useState(false)
  const [variantMap,  setVariantMap]  = useState<Record<string, ProductVariant[]>>({})
  const timerRef   = useRef<ReturnType<typeof setTimeout> | null>(null)
  const codeInputRef = useRef<HTMLInputElement>(null)

  useEffect(() => {
    if (!open) {
      setCodeQuery('')
      setDescQuery('')
      setResults([])
      setVariantMap({})
    } else {
      setTimeout(() => codeInputRef.current?.focus(), 50)
    }
  }, [open])

  useEffect(() => {
    if (timerRef.current) clearTimeout(timerRef.current)
    if (!descQuery || descQuery.length < 2) { setResults([]); return }

    setLoading(true)
    timerRef.current = setTimeout(async () => {
      try {
        const res = await catalogService.getProducts({ q: descQuery, status: 'active', per_page: 12 })
        setResults(res.data)
      } catch {
        setResults([])
      } finally {
        setLoading(false)
      }
    }, 400)

    return () => { if (timerRef.current) clearTimeout(timerRef.current) }
  }, [descQuery])

  async function handleCodeSearch() {
    if (!codeQuery.trim()) return
    setCodeLoading(true)
    try {
      const res = await catalogService.getProducts({ q: codeQuery.trim(), status: 'active', per_page: 5 })
      setResults(res.data)
      if (res.data.length === 0) return
      // Try to load variants for the first result and auto-select if single variant
      if (res.data.length === 1) {
        const variants = await catalogService.getVariants(res.data[0].uuid)
        if (variants.length === 1) {
          pick(res.data[0], variants[0])
          return
        }
        setVariantMap((m) => ({ ...m, [res.data[0].uuid]: variants }))
      }
    } catch {
      setResults([])
    } finally {
      setCodeLoading(false)
    }
  }

  async function handleProductClick(product: Product) {
    if (variantMap[product.uuid]) return // already loaded, just toggle
    try {
      const variants = await catalogService.getVariants(product.uuid)
      if (variants.length === 1) {
        pick(product, variants[0])
        return
      }
      setVariantMap((m) => ({ ...m, [product.uuid]: variants }))
    } catch {}
  }

  function pick(product: Product, variant: ProductVariant) {
    onSelect({
      product_variant_id: variant.uuid,
      name_snapshot: product.name + (variant.name ? ` - ${variant.name}` : ''),
      sku_snapshot:  variant.sku,
      unit_price_cents: variant.price_cents,
    })
    onClose()
  }

  if (!open) return null

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60" onClick={onClose}>
      <div
        className="bg-background rounded-xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-center justify-between px-5 py-4 border-b shrink-0">
          <h2 className="font-semibold text-base">Buscar Produto</h2>
          <Button type="button" variant="ghost" size="icon" onClick={onClose}>
            <X className="h-4 w-4" />
          </Button>
        </div>

        <div className="p-4 space-y-4 overflow-y-auto flex-1">
          {/* Code / barcode search */}
          <div className="space-y-1.5">
            <Label className="text-xs text-muted-foreground">Código interno ou código de barras</Label>
            <div className="flex gap-2">
              <div className="relative flex-1">
                <Barcode className="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                <Input
                  ref={codeInputRef}
                  placeholder="Ex: 001234 ou 7891234567890"
                  value={codeQuery}
                  onChange={(e) => setCodeQuery(e.target.value)}
                  onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); void handleCodeSearch() } }}
                  className="pl-8"
                />
              </div>
              <Button
                type="button"
                variant="secondary"
                onClick={() => void handleCodeSearch()}
                disabled={codeLoading || !codeQuery.trim()}
              >
                {codeLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : 'Buscar'}
              </Button>
            </div>
          </div>

          {/* Description search */}
          <div className="space-y-1.5">
            <Label className="text-xs text-muted-foreground">Buscar por descrição</Label>
            <div className="relative">
              <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
              <Input
                placeholder="Digite o nome do produto…"
                value={descQuery}
                onChange={(e) => setDescQuery(e.target.value)}
                className="pl-8"
              />
              {loading && (
                <Loader2 className="absolute right-2.5 top-1/2 -translate-y-1/2 h-4 w-4 animate-spin text-muted-foreground" />
              )}
            </div>
          </div>

          {/* Results */}
          {results.length > 0 && (
            <div className="space-y-1">
              {results.map((product) => {
                const expanded = variantMap[product.uuid]
                return (
                  <div key={product.uuid} className="rounded-md border overflow-hidden">
                    <button
                      type="button"
                      onClick={() => void handleProductClick(product)}
                      className="w-full text-left px-3 py-2.5 text-sm hover:bg-accent flex justify-between items-center"
                    >
                      <div>
                        <div className="font-medium">{product.name}</div>
                        <div className="text-xs text-muted-foreground">{product.code}</div>
                      </div>
                      {product.base_price != null && (
                        <span className="text-xs tabular-nums text-muted-foreground">
                          {(product.base_price).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                        </span>
                      )}
                    </button>
                    {expanded && expanded.length > 0 && (
                      <div className="border-t divide-y bg-muted/20">
                        {expanded.map((v) => (
                          <button
                            key={v.uuid}
                            type="button"
                            onClick={() => pick(product, v)}
                            className="w-full text-left px-4 py-2 text-sm hover:bg-accent flex justify-between items-center"
                          >
                            <span className="text-muted-foreground">{v.name ?? v.sku}</span>
                            <span className="tabular-nums text-xs">
                              {(v.price_cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                            </span>
                          </button>
                        ))}
                      </div>
                    )}
                  </div>
                )
              })}
            </div>
          )}

          {!loading && descQuery.length >= 2 && results.length === 0 && (
            <p className="text-sm text-muted-foreground text-center py-4">Nenhum produto encontrado.</p>
          )}
        </div>
      </div>
    </div>,
    document.body
  )
}
