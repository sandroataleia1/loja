'use client'

import { useEffect, useRef, KeyboardEvent } from 'react'
import { Search, Loader2, X } from 'lucide-react'
import { cn } from '@/lib/utils'

interface Props {
  value:      string
  onChange:   (v: string) => void
  onSearch:   (v: string) => void   // dispara busca imediata (Enter / barcode)
  isLoading?: boolean
  className?: string
}

export function ProductSearchInput({ value, onChange, onSearch, isLoading, className }: Props) {
  const inputRef = useRef<HTMLInputElement>(null)

  // F1 → foca o input
  useEffect(() => {
    function onKey(e: globalThis.KeyboardEvent) {
      if (e.key === 'F1') {
        e.preventDefault()
        inputRef.current?.focus()
        inputRef.current?.select()
      }
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [])

  function handleKeyDown(e: KeyboardEvent<HTMLInputElement>) {
    if (e.key === 'Enter') {
      e.preventDefault()
      onSearch(value.trim())
    }
    if (e.key === 'Escape') {
      onChange('')
      inputRef.current?.blur()
    }
  }

  return (
    <div className={cn('relative flex items-center', className)}>
      {isLoading ? (
        <Loader2 className="absolute left-3 w-4 h-4 text-muted-foreground animate-spin" />
      ) : (
        <Search className="absolute left-3 w-4 h-4 text-muted-foreground pointer-events-none" />
      )}
      <input
        ref={inputRef}
        type="text"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        onKeyDown={handleKeyDown}
        placeholder="Buscar por nome, SKU ou código de barras… (F1)"
        className="w-full h-10 rounded-lg border bg-background pl-9 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
        autoComplete="off"
        spellCheck={false}
      />
      {value && (
        <button
          onClick={() => { onChange(''); inputRef.current?.focus() }}
          className="absolute right-3 text-muted-foreground hover:text-foreground transition-colors"
          tabIndex={-1}
        >
          <X className="w-3.5 h-3.5" />
        </button>
      )}
    </div>
  )
}
