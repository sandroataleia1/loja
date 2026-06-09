import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'
import { formatCurrency } from '@/utils/currency'
import { cn } from '@/lib/utils'
import type { CatalogProduct, CatalogVariant } from '@/types/catalog'
import { variantLabel, variantPrice } from '@/types/catalog'

interface Props {
  product:   CatalogProduct | null
  onConfirm: (product: CatalogProduct, variant: CatalogVariant) => void
  onClose:   () => void
}

export function VariantSelector({ product, onConfirm, onClose }: Props) {
  if (!product) return null

  const activeVariants = product.variants.filter((v) => v.is_active)

  return (
    <Dialog open={!!product} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-w-sm">
        <DialogHeader>
          <DialogTitle className="text-base">{product.name}</DialogTitle>
        </DialogHeader>

        <div className="grid grid-cols-2 gap-2 py-1">
          {activeVariants.map((variant) => {
            const price      = variantPrice(product, variant)
            const outOfStock = variant.stock <= 0
            const lowStock   = variant.stock > 0 && variant.stock <= 5

            return (
              <button
                key={variant.uuid}
                disabled={outOfStock}
                onClick={() => onConfirm(product, variant)}
                className={cn(
                  'flex flex-col items-start gap-1 p-3 rounded-lg border text-left',
                  'transition-all duration-100 active:scale-[0.97]',
                  outOfStock
                    ? 'opacity-40 cursor-not-allowed border-border/40'
                    : 'border-border hover:border-primary/60 hover:bg-accent cursor-pointer',
                )}
              >
                <div className="flex items-center justify-between w-full">
                  <span className="text-sm font-medium">{variantLabel(variant)}</span>
                  {outOfStock && (
                    <Badge variant="destructive" className="text-[9px] px-1 py-0 h-4">S/E</Badge>
                  )}
                  {lowStock && (
                    <Badge variant="warning" className="text-[9px] px-1 py-0 h-4">{variant.stock}</Badge>
                  )}
                </div>
                <span className="text-xs text-muted-foreground font-mono">{variant.sku}</span>
                <span className="text-base font-bold text-primary tabular-nums">
                  {formatCurrency(price)}
                </span>
              </button>
            )
          })}
        </div>
      </DialogContent>
    </Dialog>
  )
}
