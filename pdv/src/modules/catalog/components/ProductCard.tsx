import { cn } from '@/lib/utils'
import { Badge } from '@/components/ui/badge'
import { formatCurrency } from '@/utils/currency'
import type { CatalogProduct } from '@/types/catalog'

interface Props {
  product:  CatalogProduct
  selected: boolean
  onSelect: (product: CatalogProduct) => void
}

export function ProductCard({ product, selected, onSelect }: Props) {
  const hasVariants = product.variants.length > 0
  const outOfStock  = !hasVariants && product.stock <= 0
  const lowStock    = !hasVariants && product.stock > 0 && product.stock <= 5

  return (
    <button
      onClick={() => !outOfStock && onSelect(product)}
      disabled={outOfStock}
      className={cn(
        'flex flex-col items-start gap-1.5 p-3.5 rounded-xl border text-left',
        'transition-all duration-100 active:scale-[0.97]',
        outOfStock
          ? 'opacity-45 cursor-not-allowed border-border/40'
          : selected
            ? 'border-primary bg-primary/10 ring-1 ring-primary/40'
            : 'border-border hover:border-primary/60 hover:bg-accent cursor-pointer',
      )}
    >
      {/* Header: nome + badge */}
      <div className="w-full flex items-start justify-between gap-1 min-h-[2.5rem]">
        <span className="text-sm font-medium leading-snug line-clamp-2 flex-1">
          {product.name}
        </span>
        <div className="flex flex-col items-end gap-0.5 shrink-0">
          {outOfStock && (
            <Badge variant="destructive" className="text-[9px] px-1 py-0 h-4">S/E</Badge>
          )}
          {lowStock && (
            <Badge variant="warning" className="text-[9px] px-1 py-0 h-4">{product.stock}</Badge>
          )}
          {hasVariants && (
            <Badge variant="secondary" className="text-[9px] px-1 py-0 h-4">
              {product.variants.length}var
            </Badge>
          )}
        </div>
      </div>

      {/* SKU */}
      <span className="text-[11px] text-muted-foreground/70 font-mono">{product.sku}</span>

      {/* Preço */}
      <span className="text-lg font-bold text-primary tabular-nums mt-auto">
        {formatCurrency(product.price)}
      </span>

      {/* Categoria */}
      {product.category_name && (
        <span className="text-[10px] text-muted-foreground/50 truncate w-full">
          {product.category_name}
        </span>
      )}
    </button>
  )
}
