import { Minus, Plus, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useCartStore } from '@/stores/cartStore'
import { formatCurrency } from '@/utils/currency'
import type { CartItem as CartItemType } from '@/types/sale'
import { cn } from '@/lib/utils'

interface Props {
  item:       CartItemType
  isSelected: boolean
  onClick:    () => void
}

export function CartItem({ item, isSelected, onClick }: Props) {
  const { updateQuantity, removeItem } = useCartStore()

  return (
    <div
      role="button"
      tabIndex={0}
      onClick={onClick}
      onKeyDown={(e) => e.key === 'Enter' && onClick()}
      className={cn(
        'flex items-center gap-2 py-2.5 px-2 rounded-lg transition-all cursor-default select-none',
        isSelected
          ? 'bg-primary/8 ring-1 ring-primary/40'
          : 'hover:bg-accent/50',
      )}
    >
      {/* Selected indicator */}
      <div className={cn(
        'w-1 h-8 rounded-full shrink-0 transition-colors',
        isSelected ? 'bg-primary' : 'bg-transparent',
      )} />

      {/* Info */}
      <div className="flex-1 min-w-0">
        <p className="text-sm font-medium truncate leading-tight">{item.productName}</p>
        <p className="text-xs text-muted-foreground tabular-nums">
          {formatCurrency(item.unitPrice)} × {item.quantity}
        </p>
      </div>

      {/* Quantity controls */}
      <div className="flex items-center gap-0.5 shrink-0">
        <Button
          variant="ghost"
          size="icon"
          className="h-7 w-7 rounded-md"
          onClick={(e) => { e.stopPropagation(); updateQuantity(item.productUuid, item.quantity - 1, item.variantUuid) }}
          tabIndex={-1}
        >
          <Minus className="w-3 h-3" />
        </Button>
        <span className="w-7 text-center text-sm font-semibold tabular-nums select-none">
          {item.quantity}
        </span>
        <Button
          variant="ghost"
          size="icon"
          className="h-7 w-7 rounded-md"
          onClick={(e) => { e.stopPropagation(); updateQuantity(item.productUuid, item.quantity + 1, item.variantUuid) }}
          tabIndex={-1}
        >
          <Plus className="w-3 h-3" />
        </Button>
      </div>

      {/* Line total */}
      <span className="w-20 text-right text-sm font-semibold tabular-nums shrink-0">
        {formatCurrency(item.total)}
      </span>

      {/* Remove */}
      <Button
        variant="ghost"
        size="icon"
        className="h-7 w-7 text-muted-foreground/50 hover:text-destructive shrink-0"
        onClick={(e) => { e.stopPropagation(); removeItem(item.productUuid, item.variantUuid) }}
        tabIndex={-1}
      >
        <Trash2 className="w-3.5 h-3.5" />
      </Button>
    </div>
  )
}
