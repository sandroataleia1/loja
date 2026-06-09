import { Menu, Wifi, WifiOff, CloudOff, Store } from 'lucide-react'
import { Button }    from '@/components/ui/button'
import { Badge }     from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { useUiStore }      from '@/stores/uiStore'
import { useSessionStore } from '@/stores/sessionStore'
import { useSyncStore }    from '@/stores/syncStore'
import { cn } from '@/lib/utils'

export function Topbar() {
  const { toggleSidebar }              = useUiStore()
  const { userName, isOpen: cashOpen } = useSessionStore()
  const { isOnline, pending }          = useSyncStore()

  return (
    <header className="flex items-center h-topbar px-3 border-b bg-card shrink-0 gap-2">
      {/* Menu toggle */}
      <Button
        variant="ghost"
        size="icon"
        onClick={toggleSidebar}
        className="h-8 w-8 shrink-0"
        title="Alternar menu"
      >
        <Menu className="w-4 h-4" />
      </Button>

      <Separator orientation="vertical" className="h-5 mx-1" />

      {/* Cash register status */}
      <div className="flex items-center gap-2">
        <Store className="w-3.5 h-3.5 text-muted-foreground shrink-0" />
        <Badge
          variant={cashOpen ? 'success' : 'warning'}
          className="text-xs font-medium"
        >
          {cashOpen ? 'Caixa Aberto' : 'Caixa Fechado'}
        </Badge>
      </div>

      {/* Spacer */}
      <div className="flex-1" />

      {/* Sync pending indicator */}
      {pending > 0 && (
        <div className="flex items-center gap-1.5 text-xs text-yellow-500" title={`${pending} venda(s) aguardando sincronização`}>
          <CloudOff className="w-3.5 h-3.5 shrink-0" />
          <span className="tabular-nums">
            {pending} {pending === 1 ? 'pendente' : 'pendentes'}
          </span>
        </div>
      )}

      {/* Online/offline */}
      <div
        className={cn(
          'flex items-center gap-1.5 text-xs',
          isOnline ? 'text-emerald-500' : 'text-red-400',
        )}
        title={isOnline ? 'Conectado à internet' : 'Sem conexão — modo offline'}
      >
        {isOnline
          ? <Wifi className="w-3.5 h-3.5 shrink-0" />
          : <WifiOff className="w-3.5 h-3.5 shrink-0" />}
        <span className="hidden md:inline">
          {isOnline ? 'Online' : 'Offline'}
        </span>
      </div>

      <Separator orientation="vertical" className="h-5 mx-1" />

      {/* User */}
      <div className="flex items-center gap-2 text-sm text-muted-foreground">
        <div className="flex items-center justify-center w-7 h-7 rounded-full bg-primary/15 text-primary text-xs font-semibold shrink-0">
          {userName.charAt(0).toUpperCase()}
        </div>
        <span className="hidden lg:inline max-w-[120px] truncate">{userName}</span>
      </div>
    </header>
  )
}
