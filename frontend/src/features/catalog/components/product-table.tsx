'use client'

import Link from 'next/link'
import { Eye, Pencil, Trash2, Package } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { ROUTES } from '@/constants'
import type { Product, ProductStatus } from '@store/shared-types'

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatBRL(value: number | null | undefined): string {
  if (value == null) return '—'
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
}

function getProductPrice(product: Product): number | null {
  if (product.base_price != null) return product.base_price
  if (product.variants && product.variants.length > 0) {
    const def = product.variants.find((v) => v.is_default) ?? product.variants[0]
    return def ? def.sale_price : null
  }
  return null
}

const STATUS_STYLES: Record<ProductStatus, string> = {
  draft:    'bg-gray-100 text-gray-700 border-gray-200',
  active:   'bg-green-50 text-green-700 border-green-200',
  inactive: 'bg-yellow-50 text-yellow-700 border-yellow-200',
  archived: 'bg-red-50 text-red-700 border-red-200',
  seasonal: 'bg-blue-50 text-blue-700 border-blue-200',
}

const COL_COUNT = 7

// ── Sub-components ────────────────────────────────────────────────────────────

function ProductThumbnail({ product }: { product: Product }) {
  const primary = product.images?.find((i) => i.is_primary) ?? product.images?.[0]

  if (!primary) {
    return (
      <div className="h-10 w-10 rounded-md border bg-muted flex items-center justify-center shrink-0">
        <Package className="h-5 w-5 text-muted-foreground/40" />
      </div>
    )
  }

  return (
    // eslint-disable-next-line @next/next/no-img-element
    <img
      src={primary.thumbnail_url ?? primary.url}
      alt={primary.alt_text ?? product.name}
      className="h-10 w-10 rounded-md border object-cover shrink-0"
      onError={(e) => {
        const target = e.currentTarget
        if (primary.thumbnail_url && target.src !== primary.url) {
          target.src = primary.url
        } else {
          target.style.display = 'none'
        }
      }}
    />
  )
}

function TableHead() {
  return (
    <tr className="border-b bg-muted/50 text-left text-xs uppercase tracking-wide">
      <th className="w-14 px-3 py-3 font-medium text-muted-foreground"></th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Código</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Nome / Marca</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Preço Base</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Status</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Tipo</th>
      <th className="px-4 py-3 font-medium text-muted-foreground text-right">Ações</th>
    </tr>
  )
}

// ── Table ─────────────────────────────────────────────────────────────────────

interface ProductTableProps {
  products:  Product[]
  isLoading: boolean
  onDelete:  (uuid: string) => void
}

export function ProductTable({ products, isLoading, onDelete }: ProductTableProps) {
  if (isLoading) {
    return (
      <div className="rounded-md border overflow-hidden">
        <table className="w-full text-sm">
          <thead><TableHead /></thead>
          <tbody>
            {Array.from({ length: 6 }).map((_, i) => (
              <tr key={i} className="border-b last:border-0">
                <td className="px-3 py-3">
                  <Skeleton className="h-10 w-10 rounded-md" />
                </td>
                {Array.from({ length: COL_COUNT - 1 }).map((__, j) => (
                  <td key={j} className="px-4 py-3">
                    <Skeleton className="h-4 w-full" />
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    )
  }

  if (products.length === 0) {
    return (
      <div className="rounded-md border">
        <table className="w-full text-sm">
          <thead><TableHead /></thead>
          <tbody>
            <tr>
              <td colSpan={COL_COUNT} className="px-4 py-12 text-center text-muted-foreground">
                <Package className="h-10 w-10 mx-auto mb-2 opacity-20" />
                Nenhum produto encontrado.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    )
  }

  return (
    <div className="rounded-md border overflow-x-auto">
      <table className="w-full text-sm">
        <thead><TableHead /></thead>
        <tbody>
          {products.map((product) => (
            <tr
              key={product.uuid}
              className="border-b last:border-0 hover:bg-muted/40 transition-colors"
            >
              {/* Thumbnail */}
              <td className="px-3 py-2">
                <ProductThumbnail product={product} />
              </td>

              {/* Código */}
              <td className="px-4 py-3 font-mono text-xs text-muted-foreground whitespace-nowrap">
                {product.code}
              </td>

              {/* Nome / Marca */}
              <td className="px-4 py-3">
                <Link
                  href={`${ROUTES.PRODUCTS}/${product.uuid}`}
                  className="font-medium hover:underline hover:text-primary transition-colors line-clamp-1"
                >
                  {product.name}
                </Link>
                {product.brand && (
                  <div className="text-xs text-muted-foreground mt-0.5">{product.brand.name}</div>
                )}
              </td>

              {/* Preço */}
              <td className="px-4 py-3 font-medium tabular-nums whitespace-nowrap">
                {formatBRL(getProductPrice(product))}
              </td>

              {/* Status */}
              <td className="px-4 py-3">
                <Badge variant="outline" className={STATUS_STYLES[product.status]}>
                  {product.status_label}
                </Badge>
              </td>

              {/* Tipo */}
              <td className="px-4 py-3 text-muted-foreground whitespace-nowrap">
                {product.type_label}
              </td>

              {/* Ações */}
              <td className="px-4 py-3">
                <div className="flex items-center justify-end gap-1">
                  <Button variant="ghost" size="icon" asChild title="Visualizar produto">
                    <Link href={`${ROUTES.PRODUCTS}/${product.uuid}`} aria-label="Visualizar produto">
                      <Eye className="h-4 w-4" />
                    </Link>
                  </Button>
                  <Button variant="ghost" size="icon" asChild title="Editar produto">
                    <Link href={`${ROUTES.PRODUCTS}/${product.uuid}/edit`} aria-label="Editar produto">
                      <Pencil className="h-4 w-4" />
                    </Link>
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="text-destructive hover:text-destructive"
                    aria-label="Excluir produto"
                    title="Excluir produto"
                    onClick={() => onDelete(product.uuid)}
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
