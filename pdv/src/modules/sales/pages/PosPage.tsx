import { useState, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { ProductSearch } from '../components/ProductSearch'
import { CartPanel }     from '../components/CartPanel'
import { useCartStore }  from '@/stores/cartStore'
import { useKeyboardShortcut } from '@/hooks/useKeyboardShortcut'
import { cn } from '@/lib/utils'

export function PosPage() {
  const [paymentOpen, setPaymentOpen] = useState(false)
  const { items, clear }              = useCartStore()
  const navigate                      = useNavigate()
  const isEmpty                       = items.length === 0

  // F4 — abrir cobrança
  useKeyboardShortcut('F4', useCallback(() => {
    if (!isEmpty) setPaymentOpen(true)
  }, [isEmpty]))

  // F9 — cancelar venda
  useKeyboardShortcut('F9', useCallback(() => {
    if (!isEmpty && window.confirm('Cancelar a venda atual?')) clear()
  }, [isEmpty, clear]))

  // F8 — histórico
  useKeyboardShortcut('F8', useCallback(() => {
    navigate('/sales')
  }, [navigate]))

  return (
    <div className="flex flex-col h-full overflow-hidden">
      {/* Main area: search + cart */}
      <div className="flex flex-1 overflow-hidden min-h-0">
        {/* Left — product search (flexible) */}
        <div className="flex-1 flex flex-col min-w-0 overflow-hidden border-r">
          <ProductSearch />
        </div>

        {/* Right — cart (fixed width) */}
        <div className="w-[380px] flex flex-col overflow-hidden shrink-0">
          <CartPanel
            paymentOpen={paymentOpen}
            onPaymentOpenChange={setPaymentOpen}
          />
        </div>
      </div>

      {/* Function key bar */}
      <FnBar isEmpty={isEmpty} onPayment={() => setPaymentOpen(true)} onClear={clear} />
    </div>
  )
}

function FnBar({
  isEmpty,
  onPayment,
  onClear,
}: {
  isEmpty:   boolean
  onPayment: () => void
  onClear:   () => void
}) {
  const navigate = useNavigate()

  return (
    <div className="flex items-center gap-1 px-4 py-2 border-t bg-card/60 shrink-0 select-none overflow-x-auto">
      <FnKey fn="F2"  label="Buscar" />
      <Divider />
      <FnKey fn="F4"  label="Cobrar"    disabled={isEmpty} onClick={onPayment} />
      <Divider />
      <FnKey fn="F6"  label="Cliente"   disabled />
      <Divider />
      <FnKey fn="F10" label="Desconto"  disabled={isEmpty} />
      <div className="flex-1" />
      <FnKey fn="F8"  label="Histórico" onClick={() => navigate('/sales')} />
      <Divider />
      <FnKey fn="F9"  label="Cancelar"  disabled={isEmpty}
        onClick={() => !isEmpty && window.confirm('Cancelar a venda atual?') && onClear()}
        className="text-red-400 hover:text-red-300"
      />
    </div>
  )
}

function FnKey({
  fn, label, disabled, onClick, className,
}: {
  fn:        string
  label:     string
  disabled?: boolean
  onClick?:  () => void
  className?: string
}) {
  return (
    <button
      onClick={!disabled ? onClick : undefined}
      disabled={disabled}
      className={cn(
        'flex items-center gap-1.5 px-2 py-1 rounded text-xs transition-colors',
        disabled
          ? 'opacity-30 cursor-not-allowed'
          : 'hover:bg-accent cursor-pointer',
        className,
      )}
    >
      <kbd className="kbd">{fn}</kbd>
      <span className="text-muted-foreground">{label}</span>
    </button>
  )
}

function Divider() {
  return <span className="text-border/60 text-xs select-none">│</span>
}
