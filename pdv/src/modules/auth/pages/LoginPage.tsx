import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { ShoppingCart } from 'lucide-react'
import { useSessionStore } from '@/stores/sessionStore'
import { cn } from '@/lib/utils'

const OPERATORS = [
  { name: 'Administrador', initial: 'A', pin: '0000' },
  { name: 'Caixa 1',       initial: 'C', pin: '1111' },
]

export function LoginPage() {
  const [selectedOp, setSelectedOp] = useState(OPERATORS[0])
  const [pin, setPin]               = useState('')
  const [shake, setShake]           = useState(false)
  const [now, setNow]               = useState(new Date())

  const { login, isOpen } = useSessionStore()
  const navigate           = useNavigate()

  // Live clock
  useEffect(() => {
    const t = setInterval(() => setNow(new Date()), 1000)
    return () => clearInterval(t)
  }, [])

  const validate = useCallback((p: string) => {
    if (p === selectedOp.pin) {
      login(selectedOp.name)
      navigate(isOpen ? '/' : '/caixa/abrir', { replace: true })
    } else {
      setShake(true)
      setTimeout(() => { setPin(''); setShake(false) }, 700)
    }
  }, [selectedOp, login, isOpen, navigate])

  useEffect(() => {
    if (pin.length === 4) validate(pin)
  }, [pin, validate])

  // Keyboard support
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (/^\d$/.test(e.key))  setPin(p => p.length < 4 ? p + e.key : p)
      if (e.key === 'Backspace') setPin(p => p.slice(0, -1))
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [])

  const addDigit    = (d: string) => setPin(p => p.length < 4 ? p + d : p)
  const removeDigit = () => setPin(p => p.slice(0, -1))

  const timeStr = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  const dateStr = now.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })

  return (
    <div className="min-h-screen bg-background flex flex-col items-center justify-center p-4 auth-enter">
      {/* Store brand */}
      <div className="flex flex-col items-center gap-2 mb-10">
        <div className="flex items-center justify-center w-14 h-14 rounded-2xl bg-primary/15 text-primary">
          <ShoppingCart className="w-7 h-7" />
        </div>
        <h1 className="text-2xl font-bold tracking-tight">PDV Fashion</h1>
        <div className="text-center text-muted-foreground">
          <p className="text-3xl font-light tabular-nums">{timeStr}</p>
          <p className="text-sm capitalize mt-0.5">{dateStr}</p>
        </div>
      </div>

      {/* Login card */}
      <div className="w-full max-w-xs bg-card border rounded-2xl p-6 space-y-5 shadow-xl">
        {/* Operator selector */}
        <div>
          <p className="text-xs text-muted-foreground mb-2 font-medium tracking-wide uppercase">
            Operador
          </p>
          <div className="flex gap-2">
            {OPERATORS.map((op) => (
              <button
                key={op.name}
                onClick={() => { setSelectedOp(op); setPin('') }}
                className={cn(
                  'flex-1 flex items-center gap-2 px-3 py-2.5 rounded-lg border text-sm transition-all',
                  selectedOp.name === op.name
                    ? 'border-primary bg-primary/10 text-primary font-medium'
                    : 'border-border hover:border-muted-foreground/40 text-muted-foreground',
                )}
              >
                <span className={cn(
                  'flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold shrink-0',
                  selectedOp.name === op.name
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-muted text-muted-foreground',
                )}>
                  {op.initial}
                </span>
                <span className="truncate">{op.name}</span>
              </button>
            ))}
          </div>
        </div>

        {/* PIN dots */}
        <div className="flex flex-col items-center gap-3">
          <p className="text-xs text-muted-foreground">Digite o PIN</p>
          <div className={cn('flex gap-3 transition-all', shake && 'animate-[shake_0.4s_ease-in-out]')}>
            {[0, 1, 2, 3].map((i) => (
              <span
                key={i}
                className={cn(
                  'pin-dot',
                  shake
                    ? 'pin-dot-error'
                    : pin.length > i
                      ? 'pin-dot-filled'
                      : 'pin-dot-empty',
                )}
              />
            ))}
          </div>
        </div>

        {/* PIN pad */}
        <div className="grid grid-cols-3 gap-2">
          {[7, 8, 9, 4, 5, 6, 1, 2, 3].map((d) => (
            <PinButton key={d} label={String(d)} onClick={() => addDigit(String(d))} />
          ))}
          <PinButton label="⌫" onClick={removeDigit} variant="ghost" />
          <PinButton label="0" onClick={() => addDigit('0')} />
          <PinButton label="↵" onClick={() => { /* enter handled by useEffect on pin.length=4 */ }} variant="primary" />
        </div>
      </div>

      <p className="mt-6 text-xs text-muted-foreground/50">
        PIN padrão: 0000
      </p>
    </div>
  )
}

function PinButton({
  label,
  onClick,
  variant = 'default',
}: {
  label:    string
  onClick:  () => void
  variant?: 'default' | 'ghost' | 'primary'
}) {
  return (
    <button
      onClick={onClick}
      className={cn(
        'h-12 rounded-lg text-lg font-semibold transition-all active:scale-95 select-none',
        variant === 'default' && 'bg-muted hover:bg-accent text-foreground',
        variant === 'ghost'   && 'bg-transparent hover:bg-accent text-muted-foreground',
        variant === 'primary' && 'bg-primary hover:bg-primary/90 text-primary-foreground',
      )}
    >
      {label}
    </button>
  )
}
