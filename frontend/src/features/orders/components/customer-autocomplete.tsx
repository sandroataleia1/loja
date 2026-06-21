'use client'

import { useState, useEffect, useRef } from 'react'
import { Search, X, Loader2 } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { customerService } from '@/services/customer.service'
import type { Customer } from '@store/shared-types'

interface Props {
  value:     string | null
  onChange:  (uuid: string | null, customer: Customer | null) => void
  disabled?: boolean
  label?:    string
}

export function CustomerAutocomplete({ value, onChange, disabled, label = 'Cliente' }: Props) {
  const [query,    setQuery]    = useState('')
  const [results,  setResults]  = useState<Customer[]>([])
  const [loading,  setLoading]  = useState(false)
  const [open,     setOpen]     = useState(false)
  const [selected, setSelected] = useState<Customer | null>(null)
  const wrapperRef = useRef<HTMLDivElement>(null)
  const timerRef   = useRef<ReturnType<typeof setTimeout> | null>(null)

  useEffect(() => {
    function handle(e: MouseEvent) {
      if (wrapperRef.current && !wrapperRef.current.contains(e.target as Node)) {
        setOpen(false)
      }
    }
    document.addEventListener('mousedown', handle)
    return () => document.removeEventListener('mousedown', handle)
  }, [])

  useEffect(() => {
    if (timerRef.current) clearTimeout(timerRef.current)

    if (!query || query.length < 3) {
      setResults([])
      setOpen(false)
      return
    }

    setLoading(true)
    timerRef.current = setTimeout(async () => {
      try {
        const res = await customerService.getCustomers({ q: query, per_page: 8 })
        setResults(res.data)
        setOpen(true)
      } catch {
        setResults([])
      } finally {
        setLoading(false)
      }
    }, 400)

    return () => { if (timerRef.current) clearTimeout(timerRef.current) }
  }, [query])

  function select(customer: Customer) {
    setSelected(customer)
    setQuery('')
    setOpen(false)
    onChange(customer.uuid, customer)
  }

  function clear() {
    setSelected(null)
    setQuery('')
    onChange(null, null)
  }

  // sync external value reset
  useEffect(() => {
    if (!value) { setSelected(null); setQuery('') }
  }, [value])

  return (
    <div className="space-y-1.5" ref={wrapperRef}>
      <Label>{label}</Label>
      {selected ? (
        <div className="flex items-center gap-2 h-9 px-3 rounded-md border border-input bg-muted/30 text-sm">
          <span className="flex-1 truncate">{selected.name}</span>
          {!disabled && (
            <button type="button" onClick={clear} className="shrink-0 text-muted-foreground hover:text-foreground">
              <X className="h-4 w-4" />
            </button>
          )}
        </div>
      ) : (
        <div className="relative">
          <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
          <Input
            placeholder="Consumidor Final — ou digite 3 letras para buscar"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            disabled={disabled}
            className="pl-8"
          />
          {loading && (
            <Loader2 className="absolute right-2.5 top-1/2 -translate-y-1/2 h-4 w-4 animate-spin text-muted-foreground" />
          )}
          {open && results.length > 0 && (
            <div className="absolute z-50 mt-1 w-full rounded-md border bg-popover shadow-lg max-h-48 overflow-y-auto">
              {results.map((c) => (
                <button
                  key={c.uuid}
                  type="button"
                  className="w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground"
                  onClick={() => select(c)}
                >
                  <span className="font-medium">{c.name}</span>
                  {c.document && (
                    <span className="ml-2 text-xs text-muted-foreground">{c.document}</span>
                  )}
                </button>
              ))}
            </div>
          )}
          {open && !loading && results.length === 0 && query.length >= 3 && (
            <div className="absolute z-50 mt-1 w-full rounded-md border bg-popover shadow-lg px-3 py-2 text-sm text-muted-foreground">
              Nenhum cliente encontrado.
            </div>
          )}
        </div>
      )}
    </div>
  )
}
