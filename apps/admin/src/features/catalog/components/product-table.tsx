'use client'

import Link from 'next/link'
import { Pencil, Trash2 } from 'lucide-react'
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
  draft:    'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
  active:   'bg-green-50 text-green-700 border-green-200 dark:bg-green-950 dark:text-green-300 dark:border-green-800',
  inactive: 'bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-950 dark:text-yellow-300 dark:border-yellow-800',
  archived: 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950 dark:text-red-300 dark:border-red-800',
  seasonal: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800',
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
          <thead>
            <TableHead />
          </thead>
          <tbody>
            {Array.from({ length: 5 }).map((_, i) => (
              <tr key={i} className="border-b last:border-0">
                {Array.from({ length: 7 }).map((__, j) => (
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
          <thead>
            <TableHead />
          </thead>
          <tbody>
            <tr>
              <td colSpan={7} className="px-4 py-10 text-center text-muted-foreground">
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
        <thead>
          <TableHead />
        </thead>
        <tbody>
          {products.map((product) => (
            <tr key={product.uuid} className="border-b last:border-0 hover:bg-muted/50 transition-colors">
              <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{product.code}</td>
              <td className="px-4 py-3 font-medium">
                <div>{product.name}</div>
                {product.brand && (
                  <div className="text-xs text-muted-foreground">{product.brand.name}</div>
                )}
              </td>
              <td className="px-4 py-3 text-muted-foreground">
                {product.brand?.name ?? '—'}
              </td>
              <td className="px-4 py-3 text-muted-foreground">
                {formatBRL(getProductPrice(product))}
              </td>
              <td className="px-4 py-3">
                <Badge
                  variant="outline"
                  className={STATUS_STYLES[product.status]}
                >
                  {product.status_label}
                </Badge>
              </td>
              <td className="px-4 py-3 text-muted-foreground">
                {product.type_label}
              </td>
              <td className="px-4 py-3">
                <div className="flex items-center gap-1">
                  <Button variant="ghost" size="icon" asChild>
                    <Link href={`${ROUTES.PRODUCTS}/${product.uuid}`} aria-label="Ver produto">
                      <Pencil className="h-4 w-4" />
                    </Link>
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="text-destructive hover:text-destructive"
                    aria-label="Excluir produto"
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

function TableHead() {
  return (
    <tr className="border-b bg-muted/50 text-left">
      <th className="px-4 py-3 font-medium text-muted-foreground">Código</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Nome</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Marca</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Preço Base</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Status</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Tipo</th>
      <th className="px-4 py-3 font-medium text-muted-foreground">Ações</th>
    </tr>
  )
}
