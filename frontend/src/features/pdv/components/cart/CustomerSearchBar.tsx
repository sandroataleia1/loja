'use client'

import { useState, useRef, useEffect } from 'react'
import { User, Search, X, Loader2 } from 'lucide-react'
import { usePdvCartStore } from '@/features/pdv/stores/pdvCartStore'
import { useCustomerSearch } from '@/features/pdv/hooks'

export function CustomerSearchBar() {
  const { customerUuid, customerName, setCustomer } = usePdvCartStore()
  const [query,  setQuery]  = useState('')
  const [open,   setOpen]   = useState(false)
  const containerRef = useRef<HTMLDivElement>(null)

  const { data, isFetching } = useCustomerSearch(query)
  const customers = data?.data ?? []

  // F2 → abre busca
  useEffect(() => {
    function onKey(e: KeyboardEvent) {
      if (e.key === 'F2') {
        e.preventDefault()
        setOpen(true)
        setTimeout(() => containerRef.current?.querySelector('input')?.focus(), 50)
      }
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [])

  // Fecha ao clicar fora
  useEffect(() => {
    function onClickOutside(e: MouseEvent) {
      if (!containerRef.current?.contains(e.target as Node)) setOpen(false)
    }
    document.addEventListener('mousedown', onClickOutside)
    return () => document.removeEventListener('mousedown', onClickOutside)
  }, [])

  function handleSelect(uuid: string, name: string) {
    setCustomer(uuid, name)
    setQuery('')
    setOpen(false)
  }

  function handleClear() {
    setCustomer(null, null)
    setQuery('')
  }

  return (
    <div ref={containerRef} className="relative px-4 py-2.5 border-b text-sm">
      {!open ? (
        /* Estado fechado: mostra cliente ou "Consumidor Final" */
        <button
          onClick={() => setOpen(true)}
          className="w-full flex items-center gap-2 text-left group"
        >
          <User className="w-3.5 h-3.5 text-muted-foreground shrink-0" />
          <span className={customerUuid ? 'text-foreground font-medium' : 'text-muted-foreground'}>
            {customerName ?? 'Consumidor Final'}
          </span>
          {customerUuid ? (
            <button
              onClick={(e) => { e.stopPropagation(); handleClear() }}
              className="ml-auto text-muted-foreground hover:text-destructive transition-colors"
            >
              <X className="w-3.5 h-3.5" />
            </button>
          ) : (
            <span className="ml-auto text-[10px] text-muted-foreground/60">F2</span>
          )}
        </button>
      ) : (
        /* Estado aberto: input de busca */
        <div className="space-y-1">
          <div className="relative flex items-center">
            {isFetching ? (
              <Loader2 className="absolute left-2.5 w-3.5 h-3.5 text-muted-foreground animate-spin" />
            ) : (
              <Search className="absolute left-2.5 w-3.5 h-3.5 text-muted-foreground pointer-events-none" />
            )}
            <input
              type="text"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              onKeyDown={(e) => e.key === 'Escape' && setOpen(false)}
              placeholder="Nome, CPF ou telefone…"
              className="w-full h-8 rounded-lg border bg-background pl-8 pr-8 text-xs focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
              autoFocus
              autoComplete="off"
            />
            <button
              onClick={() => setOpen(false)}
              className="absolute right-2.5 text-muted-foreground hover:text-foreground transition-colors"
            >
              <X className="w-3.5 h-3.5" />
            </button>
          </div>

          {/* Resultados */}
          {customers.length > 0 && (
            <div className="absolute left-0 right-0 mx-0 bg-card border rounded-xl shadow-xl z-10 max-h-48 overflow-y-auto top-full mt-1">
              {/* Consumidor Final sempre disponível */}
              <button
                onClick={() => handleSelect('', '')}
                className="w-full flex items-center gap-2 px-3 py-2 hover:bg-accent transition-colors text-left text-xs text-muted-foreground"
              >
                <User className="w-3 h-3" />
                Consumidor Final
              </button>
              <div className="border-t" />
              {customers.map((c) => (
                <button
                  key={c.uuid}
                  onClick={() => handleSelect(c.uuid, c.name)}
                  className="w-full flex items-center gap-2 px-3 py-2 hover:bg-accent transition-colors text-left"
                >
                  <User className="w-3 h-3 text-muted-foreground shrink-0" />
                  <div className="min-w-0">
                    <p className="text-xs font-medium truncate">{c.name}</p>
                    {c.document && <p className="text-[10px] text-muted-foreground">{c.document}</p>}
                  </div>
                </button>
              ))}
            </div>
          )}

          {query.length >= 2 && !isFetching && customers.length === 0 && (
            <p className="text-[10px] text-muted-foreground px-1 pt-1">
              Nenhum cliente encontrado.
            </p>
          )}
        </div>
      )}
    </div>
  )
}
