import { useState, useEffect, useRef } from 'react'
import { useNavigate } from 'react-router-dom'
import { ShoppingCart, LogOut } from 'lucide-react'
import { Button }    from '@/components/ui/button'
import { Input }     from '@/components/ui/input'
import { Label }     from '@/components/ui/label'
import { useSessionStore } from '@/stores/sessionStore'
import { parseCurrency }   from '@/utils/currency'
import { cn } from '@/lib/utils'

export function OpenRegisterPage() {
  const [balance, setBalance] = useState('')
  const [now, setNow]         = useState(new Date())
  const inputRef              = useRef<HTMLInputElement>(null)

  const { userName, openRegister, logout } = useSessionStore()
  const navigate = useNavigate()

  useEffect(() => { inputRef.current?.focus() }, [])

  useEffect(() => {
    const t = setInterval(() => setNow(new Date()), 1000)
    return () => clearInterval(t)
  }, [])

  const handleOpen = () => {
    openRegister(parseCurrency(balance))
    navigate('/', { replace: true })
  }

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter') handleOpen()
    if (e.key === 'Escape') { logout(); navigate('/login', { replace: true }) }
  }

  const timeStr = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  const dateStr = now.toLocaleDateString('pt-BR', {
    weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
  })

  return (
    <div className="min-h-screen bg-background flex flex-col items-center justify-center p-4 auth-enter">
      {/* Brand */}
      <div className="flex items-center gap-3 mb-10">
        <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-primary/15 text-primary">
          <ShoppingCart className="w-5 h-5" />
        </div>
        <span className="text-xl font-bold">PDV Fashion</span>
      </div>

      {/* Card */}
      <div className="w-full max-w-sm bg-card border rounded-2xl p-7 space-y-6 shadow-xl">
        <div>
          <h2 className="text-xl font-bold">Abertura de Caixa</h2>
          <p className="text-sm text-muted-foreground mt-0.5 capitalize">{dateStr}</p>
        </div>

        {/* Info row */}
        <div className="grid grid-cols-2 gap-3">
          <InfoBlock label="Operador" value={userName} />
          <InfoBlock label="Hora de Abertura" value={timeStr} mono />
        </div>

        {/* Opening balance */}
        <div className="space-y-2" onKeyDown={handleKeyDown}>
          <Label htmlFor="balance" className="text-sm font-medium">
            Fundo de Troca
          </Label>
          <div className="relative">
            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm pointer-events-none">
              R$
            </span>
            <Input
              id="balance"
              ref={inputRef}
              value={balance}
              onChange={(e) => setBalance(e.target.value)}
              placeholder="0,00"
              className="pl-9 h-12 text-lg text-right tabular-nums"
              autoComplete="off"
            />
          </div>
          <p className="text-xs text-muted-foreground">
            Dinheiro disponível no caixa no início do turno
          </p>
        </div>

        {/* CTA */}
        <Button
          size="xl"
          className="w-full h-12 text-base font-semibold gap-2"
          onClick={handleOpen}
        >
          Abrir Caixa
          <kbd className="kbd text-[10px] ml-1">↵ Enter</kbd>
        </Button>
      </div>

      {/* Logout */}
      <button
        onClick={() => { logout(); navigate('/login', { replace: true }) }}
        className="flex items-center gap-1.5 mt-6 text-xs text-muted-foreground/60 hover:text-muted-foreground transition-colors"
      >
        <LogOut className="w-3.5 h-3.5" />
        Sair da conta
      </button>
    </div>
  )
}

function InfoBlock({
  label, value, mono = false,
}: {
  label: string; value: string; mono?: boolean
}) {
  return (
    <div className="bg-muted/50 rounded-lg px-3 py-2.5">
      <p className="text-xs text-muted-foreground mb-0.5">{label}</p>
      <p className={cn('text-sm font-medium', mono && 'tabular-nums font-mono')}>{value}</p>
    </div>
  )
}
