'use client'

import { useState, useRef, useEffect } from 'react'
import Link from 'next/link'
import { Monitor, LogOut, ArrowUpFromLine, ArrowDownToLine, DoorOpen, ChevronDown } from 'lucide-react'
import { useAuth }            from '@/hooks/use-auth'
import { usePdvSessionStore } from '@/features/pdv/stores/pdvSessionStore'
import { usePdvCartStore }    from '@/features/pdv/stores/pdvCartStore'
import { ROUTES }             from '@/constants'
import { SuprimentoDialog }   from '../session/SuprimentoDialog'
import { SangriaDialog }      from '../session/SangriaDialog'
import { cn }                 from '@/lib/utils'

export function PdvTopbar() {
  const { session, clearSession } = usePdvSessionStore()
  const clear   = usePdvCartStore((s) => s.clear)
  const { user } = useAuth()

  const [menuOpen,       setMenuOpen]       = useState(false)
  const [suprimentoOpen, setSuprimentoOpen] = useState(false)
  const [sangriaOpen,    setSangriaOpen]    = useState(false)
  const menuRef = useRef<HTMLDivElement>(null)

  const openedTime = session
    ? new Date(session.openedAt).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
    : '--:--'

  // Fecha dropdown ao clicar fora
  useEffect(() => {
    function onClickOutside(e: MouseEvent) {
      if (!menuRef.current?.contains(e.target as Node)) setMenuOpen(false)
    }
    document.addEventListener('mousedown', onClickOutside)
    return () => document.removeEventListener('mousedown', onClickOutside)
  }, [])

  function handleExit() {
    if (confirm('Deseja sair do PDV? O caixa continuará aberto.')) {
      clear()
      clearSession()
      window.location.href = ROUTES.SALES
    }
  }

  return (
    <>
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

        {/* Direita: menu caixa + operador + sair */}
        <div className="flex items-center gap-3 text-xs text-muted-foreground">

          {/* Dropdown de ações do caixa */}
          {session && (
            <div ref={menuRef} className="relative">
              <button
                onClick={() => setMenuOpen((v) => !v)}
                className="flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-accent transition-colors"
              >
                Caixa
                <ChevronDown className={cn('w-3 h-3 transition-transform', menuOpen && 'rotate-180')} />
              </button>

              {menuOpen && (
                <div className="absolute right-0 top-full mt-1 w-44 bg-popover border rounded-xl shadow-xl z-50 overflow-hidden py-1">
                  <button
                    onClick={() => { setMenuOpen(false); setSuprimentoOpen(true) }}
                    className="w-full flex items-center gap-2.5 px-3 py-2 text-left hover:bg-accent transition-colors"
                  >
                    <ArrowUpFromLine className="w-3.5 h-3.5 text-green-600 shrink-0" />
                    Suprimento
                  </button>
                  <button
                    onClick={() => { setMenuOpen(false); setSangriaOpen(true) }}
                    className="w-full flex items-center gap-2.5 px-3 py-2 text-left hover:bg-accent transition-colors"
                  >
                    <ArrowDownToLine className="w-3.5 h-3.5 text-destructive shrink-0" />
                    Sangria
                  </button>
                  <div className="border-t my-1" />
                  <Link
                    href={ROUTES.PDV_CAIXA_FECHAR}
                    onClick={() => setMenuOpen(false)}
                    className="flex items-center gap-2.5 px-3 py-2 hover:bg-accent transition-colors text-destructive"
                  >
                    <DoorOpen className="w-3.5 h-3.5 shrink-0" />
                    Fechar Caixa
                  </Link>
                </div>
              )}
            </div>
          )}

          {user && (
            <span>
              Op: <span className="text-foreground font-medium">{user.name.split(' ')[0]}</span>
            </span>
          )}

          <button
            onClick={handleExit}
            className="flex items-center gap-1 hover:text-destructive transition-colors"
            title="Sair do PDV (caixa permanece aberto)"
          >
            <LogOut className="w-3.5 h-3.5" />
            Sair
          </button>
        </div>
      </header>

      {suprimentoOpen && <SuprimentoDialog onClose={() => setSuprimentoOpen(false)} />}
      {sangriaOpen    && <SangriaDialog    onClose={() => setSangriaOpen(false)}    />}
    </>
  )
}
