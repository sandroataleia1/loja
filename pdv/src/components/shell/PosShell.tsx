import { useState, useEffect, useRef } from 'react'
import { Outlet, useNavigate } from 'react-router-dom'
import { ShoppingCart, Wifi, WifiOff, CloudOff, LogOut, X, History } from 'lucide-react'
import { Separator } from '@/components/ui/separator'
import { Badge }     from '@/components/ui/badge'
import { useSessionStore } from '@/stores/sessionStore'
import { useSyncStore }    from '@/stores/syncStore'
import { cn } from '@/lib/utils'

/**
 * Shell minimalista para o modo PDV — sem sidebar, barra de status compacta.
 * Maximiza espaço útil para a operação de venda.
 */
export function PosShell() {
  return (
    <div className="flex flex-col h-screen overflow-hidden bg-background">
      <PosTopbar />
      <main className="flex-1 overflow-hidden">
        <Outlet />
      </main>
    </div>
  )
}

function PosTopbar() {
  const [now, setNow]       = useState(new Date())
  const [menuOpen, setMenuOpen] = useState(false)
  const menuRef             = useRef<HTMLDivElement>(null)

  const { userName, isOpen, closeRegister, logout } = useSessionStore()
  const { isOnline, pending, status }               = useSyncStore()
  const navigate = useNavigate()

  // Live clock
  useEffect(() => {
    const t = setInterval(() => setNow(new Date()), 1000)
    return () => clearInterval(t)
  }, [])

  // Close menu on outside click
  useEffect(() => {
    const onClick = (e: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        setMenuOpen(false)
      }
    }
    document.addEventListener('mousedown', onClick)
    return () => document.removeEventListener('mousedown', onClick)
  }, [])

  const isSyncing   = status === 'running'
  const timeStr     = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  const dateStr     = now.toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit', month: '2-digit' })

  const handleCloseRegister = () => {
    setMenuOpen(false)
    navigate('/caixa/fechar')
  }

  const handleLogout = () => {
    setMenuOpen(false)
    closeRegister()
    logout()
    navigate('/login', { replace: true })
  }

  return (
    <header className="flex items-center h-topbar px-4 border-b bg-card shrink-0 gap-3">
      {/* Brand */}
      <div className="flex items-center gap-2 shrink-0">
        <ShoppingCart className="w-4 h-4 text-primary" />
        <span className="font-bold text-sm">PDV Fashion</span>
      </div>

      <Separator orientation="vertical" className="h-4" />

      {/* Cash status */}
      <Badge
        variant={isOpen ? 'success' : 'warning'}
        className="text-xs shrink-0"
      >
        {isOpen ? 'Caixa Aberto' : 'Caixa Fechado'}
      </Badge>

      {/* Spacer */}
      <div className="flex-1" />

      {/* Sync indicator */}
      {pending > 0 && (
        <div
          className={cn(
            'flex items-center gap-1 text-xs',
            isSyncing ? 'text-blue-400' : 'text-yellow-500',
          )}
          title={`${pending} venda(s) aguardando sincronização`}
        >
          <CloudOff className="w-3.5 h-3.5 shrink-0" />
          <span className="tabular-nums">{pending}</span>
        </div>
      )}

      {/* Online/offline */}
      <div
        className={cn('flex items-center gap-1 text-xs', isOnline ? 'text-emerald-500' : 'text-red-400')}
        title={isOnline ? 'Conectado' : 'Offline'}
      >
        {isOnline
          ? <Wifi className="w-3.5 h-3.5 shrink-0" />
          : <WifiOff className="w-3.5 h-3.5 shrink-0" />}
      </div>

      <Separator orientation="vertical" className="h-4" />

      {/* Date + time */}
      <div className="text-xs text-muted-foreground tabular-nums hidden sm:flex items-center gap-1.5 shrink-0">
        <span className="capitalize">{dateStr}</span>
        <span className="text-muted-foreground/50">·</span>
        <span className="font-mono font-medium text-foreground">{timeStr}</span>
      </div>

      <Separator orientation="vertical" className="h-4" />

      {/* User menu */}
      <div ref={menuRef} className="relative">
        <button
          onClick={() => setMenuOpen((o) => !o)}
          className="flex items-center gap-2 px-2 py-1 rounded-md hover:bg-accent transition-colors"
        >
          <div className="flex items-center justify-center w-6 h-6 rounded-full bg-primary/15 text-primary text-xs font-bold shrink-0">
            {userName.charAt(0).toUpperCase()}
          </div>
          <span className="text-sm hidden md:inline max-w-[100px] truncate">{userName}</span>
        </button>

        {menuOpen && (
          <div className="absolute right-0 top-full mt-1.5 w-52 bg-card border rounded-xl shadow-xl z-50 overflow-hidden animate-fade-in">
            <div className="px-3 py-2.5 border-b">
              <p className="text-xs text-muted-foreground">Operador</p>
              <p className="text-sm font-medium truncate">{userName}</p>
            </div>
            <div className="p-1">
              <MenuBtn
                icon={<History className="w-4 h-4" />}
                label="Histórico de Vendas"
                onClick={() => { setMenuOpen(false); navigate('/sales') }}
              />
              <MenuBtn
                icon={<X className="w-4 h-4" />}
                label="Fechar Caixa"
                onClick={handleCloseRegister}
                variant="warning"
              />
              <MenuBtn
                icon={<LogOut className="w-4 h-4" />}
                label="Sair"
                onClick={handleLogout}
                variant="destructive"
              />
            </div>
          </div>
        )}
      </div>
    </header>
  )
}

function MenuBtn({
  icon, label, onClick, variant = 'default',
}: {
  icon:     React.ReactNode
  label:    string
  onClick:  () => void
  variant?: 'default' | 'warning' | 'destructive'
}) {
  return (
    <button
      onClick={onClick}
      className={cn(
        'flex items-center gap-2.5 w-full px-3 py-2 rounded-lg text-sm transition-colors text-left',
        variant === 'default'     && 'hover:bg-accent text-foreground',
        variant === 'warning'     && 'hover:bg-yellow-500/10 text-yellow-500',
        variant === 'destructive' && 'hover:bg-destructive/10 text-red-400',
      )}
    >
      {icon}
      {label}
    </button>
  )
}
