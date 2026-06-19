'use client'

import Link from 'next/link'
import { Monitor, LogOut } from 'lucide-react'
import { useAuth } from '@/hooks/use-auth'
import { usePdvSessionStore } from '@/features/pdv/stores/pdvSessionStore'
import { usePdvCartStore } from '@/features/pdv/stores/pdvCartStore'
import { ROUTES } from '@/constants'

export function PdvTopbar() {
  const { session, clearSession } = usePdvSessionStore()
  const clear = usePdvCartStore((s) => s.clear)
  const { user } = useAuth()

  const openedTime = session
    ? new Date(session.openedAt).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
    : '--:--'

  function handleExit() {
    if (confirm('Deseja sair do PDV? O caixa continuará aberto.')) {
      clear()
      clearSession()
      window.location.href = ROUTES.SALES
    }
  }

  return (
    <header className="flex items-center justify-between h-11 px-4 border-b bg-card text-sm shrink-0 select-none">
      {/* Esquerda: marca + caixa */}
      <div className="flex items-center gap-3">
        <div className="flex items-center gap-1.5 font-bold text-primary">
          <Monitor className="w-4 h-4" />
          PDV Fashion
        </div>
        {session && (
          <>
            <span className="text-border text-xs">|</span>
            <span className="text-muted-foreground text-xs">{session.registerName}</span>
            <span className="text-border text-xs">|</span>
            <span className="text-muted-foreground text-xs">Aberto {openedTime}</span>
          </>
        )}
      </div>

      {/* Direita: operador + sair */}
      <div className="flex items-center gap-4 text-xs text-muted-foreground">
        {user && (
          <span>
            Operador: <span className="text-foreground font-medium">{user.name}</span>
          </span>
        )}
        <button
          onClick={handleExit}
          className="flex items-center gap-1 hover:text-destructive transition-colors"
          title="Sair do PDV"
        >
          <LogOut className="w-3.5 h-3.5" />
          Sair
        </button>
      </div>
    </header>
  )
}
