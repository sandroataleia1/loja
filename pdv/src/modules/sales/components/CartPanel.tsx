import { useState, useEffect } from 'react'
import { ShoppingBag, Trash2, UserPlus } from 'lucide-react'
import { Button }     from '@/components/ui/button'
import { Separator }  from '@/components/ui/separator'
import { ScrollArea } from '@/components/ui/scroll-area'
import { CartItem }       from './CartItem'
import { PaymentModal }   from './PaymentModal'
import { useCartStore, selectSubtotal, selectTotal } from '@/stores/cartStore'
import { formatCurrency }  from '@/utils/currency'
import { cn } from '@/lib/utils'

interface Props {
  paymentOpen:         boolean
  onPaymentOpenChange: (open: boolean) => void
}

export function CartPanel({ paymentOpen, onPaymentOpenChange }: Props) {
  const [selectedIdx, setSelectedIdx] = useState(-1)

  const { items, discount, clear, updateQuantity, removeItem } = useCartStore()
  const subtotal = useCartStore(selectSubtotal)
  const total    = useCartStore(selectTotal)
  const isEmpty  = items.length === 0

  // Deselect when cart empties
  useEffect(() => {
    if (isEmpty) setSelectedIdx(-1)
  }, [isEmpty])

  // Keyboard navigation
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (isEmpty) return

      // Don't steal keys from input fields
      const tag = (document.activeElement as HTMLElement)?.tagName
      if (tag === 'INPUT' || tag === 'TEXTAREA') return

      switch (e.key) {
        case 'ArrowDown':
          e.preventDefault()
          setSelectedIdx((i) => Math.min(i + 1, items.length - 1))
          break

        case 'ArrowUp':
          e.preventDefault()
          setSelectedIdx((i) => Math.max(i - 1, 0))
          break

        case 'Delete':
          if (selectedIdx >= 0) {
            e.preventDefault()
            const sel = items[selectedIdx]
            removeItem(sel.productUuid, sel.variantUuid)
            setSelectedIdx((i) => (items.length > 1 ? Math.min(i, items.length - 2) : -1))
          }
          break

        case '+':
          if (selectedIdx >= 0) {
            e.preventDefault()
            const it = items[selectedIdx]
            updateQuantity(it.productUuid, it.quantity + 1, it.variantUuid)
          }
          break

        case '-':
          if (selectedIdx >= 0) {
            e.preventDefault()
            const it = items[selectedIdx]
            updateQuantity(it.productUuid, it.quantity - 1, it.variantUuid)
          }
          break

        case 'F4':
          e.preventDefault()
          if (!isEmpty) onPaymentOpenChange(true)
          break
      }
    }

    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [items, selectedIdx, isEmpty, removeItem, updateQuantity, onPaymentOpenChange])

  return (
    <>
      <div className="flex flex-col h-full">
        {/* Header */}
        <div className="flex items-center justify-between px-4 py-3 border-b shrink-0">
          <div className="flex items-center gap-2">
            <ShoppingBag className="w-4 h-4 text-primary" />
            <span className="font-semibold text-sm">Carrinho</span>
            {!isEmpty && (
              <span className="text-xs text-muted-foreground tabular-nums">
                ({items.length} {items.length === 1 ? 'item' : 'itens'})
              </span>
            )}
          </div>

          <div className="flex items-center gap-1">
            {/* Customer (v2) */}
            <Button
              variant="ghost"
              size="icon"
              className="h-7 w-7 text-muted-foreground/50 hover:text-muted-foreground"
              title="Associar cliente (F6)"
              disabled
            >
              <UserPlus className="w-3.5 h-3.5" />
            </Button>

            {!isEmpty && (
              <Button
                variant="ghost"
                size="icon"
                className="h-7 w-7 text-muted-foreground/50 hover:text-destructive"
                onClick={clear}
                title="Cancelar venda (F9)"
              >
                <Trash2 className="w-3.5 h-3.5" />
              </Button>
            )}
          </div>
        </div>

        {/* Keyboard hint */}
        {!isEmpty && (
          <div className="px-4 py-1.5 border-b bg-muted/30 shrink-0">
            <p className="text-[10px] text-muted-foreground/50 flex gap-3">
              <span><kbd className="kbd">↑↓</kbd> Navegar</span>
              <span><kbd className="kbd">+</kbd><kbd className="kbd">−</kbd> Qtd</span>
              <span><kbd className="kbd">Del</kbd> Remover</span>
            </p>
          </div>
        )}

        {/* Items */}
        <ScrollArea className="flex-1">
          {isEmpty ? (
            <div className="flex flex-col items-center justify-center h-48 gap-3 text-muted-foreground">
              <ShoppingBag className="w-10 h-10 opacity-15" />
              <div className="text-center">
                <p className="text-sm font-medium opacity-50">Carrinho vazio</p>
                <p className="text-xs opacity-35 mt-0.5">Busque ou escaneie um produto</p>
              </div>
            </div>
          ) : (
            <div className="p-2 space-y-0.5">
              {items.map((item, idx) => (
                <CartItem
                  key={`${item.productUuid}:${item.variantUuid ?? ''}`}
                  item={item}
                  isSelected={idx === selectedIdx}
                  onClick={() => setSelectedIdx(idx === selectedIdx ? -1 : idx)}
                />
              ))}
            </div>
          )}
        </ScrollArea>

        {/* Totals + CTA */}
        <div className="shrink-0 border-t">
          {!isEmpty && (
            <div className="px-4 pt-3 pb-2 space-y-1">
              <div className="flex justify-between text-sm text-muted-foreground">
                <span>Subtotal</span>
                <span className="tabular-nums">{formatCurrency(subtotal)}</span>
              </div>
              {discount > 0 && (
                <div className="flex justify-between text-sm text-emerald-500">
                  <span>Desconto</span>
                  <span className="tabular-nums">− {formatCurrency(discount)}</span>
                </div>
              )}
              <Separator className="my-1" />
              <div className="flex justify-between font-bold">
                <span>Total</span>
                <span className="text-xl tabular-nums text-primary">{formatCurrency(total)}</span>
              </div>
            </div>
          )}

          <div className="px-3 pb-3">
            <Button
              size="xl"
              className={cn(
                'w-full h-14 text-base font-bold gap-2 transition-all',
                isEmpty && 'opacity-40',
              )}
              disabled={isEmpty}
              onClick={() => onPaymentOpenChange(true)}
            >
              {isEmpty ? 'Carrinho vazio' : (
                <>
                  Cobrar {formatCurrency(total)}
                  <kbd className="kbd text-[10px] bg-primary-foreground/20 border-primary-foreground/20 text-primary-foreground ml-1">F4</kbd>
                </>
              )}
            </Button>
          </div>
        </div>
      </div>

      <PaymentModal
        open={paymentOpen}
        onOpenChange={onPaymentOpenChange}
        total={total}
      />
    </>
  )
}
