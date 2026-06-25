'use client'

import * as React from 'react'
import { ChevronDown } from 'lucide-react'
import { cn } from '@/lib/utils'

// ── Context ───────────────────────────────────────────────────────────────────

interface SelectCtx {
  value:    string
  open:     boolean
  setOpen:  (v: boolean) => void
  onChange: (v: string) => void
}

const Ctx = React.createContext<SelectCtx | null>(null)
function useCtx() {
  const ctx = React.useContext(Ctx)
  if (!ctx) throw new Error('Select components must be used inside <Select>')
  return ctx
}

// ── Select root ───────────────────────────────────────────────────────────────

interface SelectProps {
  value?:          string
  defaultValue?:   string
  onValueChange?:  (value: string) => void
  children:        React.ReactNode
}

function Select({ value, defaultValue = '', onValueChange, children }: SelectProps) {
  const [internal, setInternal] = React.useState(defaultValue)
  const [open, setOpen]         = React.useState(false)
  const controlled = value !== undefined
  const current    = controlled ? value : internal

  const handleChange = (v: string) => {
    if (!controlled) setInternal(v)
    onValueChange?.(v)
    setOpen(false)
  }

  return (
    <Ctx.Provider value={{ value: current, open, setOpen, onChange: handleChange }}>
      <div className="relative">{children}</div>
    </Ctx.Provider>
  )
}

// ── Trigger ───────────────────────────────────────────────────────────────────

interface SelectTriggerProps extends React.HTMLAttributes<HTMLButtonElement> {
  children: React.ReactNode
}

function SelectTrigger({ children, className, ...props }: SelectTriggerProps) {
  const { open, setOpen } = useCtx()
  return (
    <button
      type="button"
      onClick={() => setOpen(!open)}
      className={cn(
        'flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm',
        'ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring',
        'focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 [&>span]:line-clamp-1',
        className,
      )}
      {...props}
    >
      {children}
      <ChevronDown className="h-4 w-4 opacity-50 shrink-0" />
    </button>
  )
}

// ── Value ─────────────────────────────────────────────────────────────────────

interface SelectValueProps {
  placeholder?: string
}

function SelectValue({ placeholder }: SelectValueProps) {
  const { value } = useCtx()
  return <span>{value || <span className="text-muted-foreground">{placeholder}</span>}</span>
}

// ── Content ───────────────────────────────────────────────────────────────────

interface SelectContentProps {
  children: React.ReactNode
  className?: string
}

function SelectContent({ children, className }: SelectContentProps) {
  const { open, setOpen } = useCtx()
  const ref = React.useRef<HTMLDivElement>(null)

  React.useEffect(() => {
    if (!open) return
    function handleClickOutside(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) {
        setOpen(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [open, setOpen])

  if (!open) return null

  return (
    <div
      ref={ref}
      className={cn(
        'absolute z-50 mt-1 max-h-60 min-w-full overflow-auto rounded-md border bg-popover shadow-md',
        'text-popover-foreground',
        className,
      )}
    >
      <div className="p-1">{children}</div>
    </div>
  )
}

// ── Item ──────────────────────────────────────────────────────────────────────

interface SelectItemProps {
  value:    string
  children: React.ReactNode
  className?: string
}

function SelectItem({ value, children, className }: SelectItemProps) {
  const { value: current, onChange } = useCtx()
  return (
    <div
      role="option"
      aria-selected={current === value}
      onClick={() => onChange(value)}
      className={cn(
        'relative flex w-full cursor-pointer select-none items-center rounded-sm py-1.5 pl-2 pr-8 text-sm',
        'outline-none hover:bg-accent hover:text-accent-foreground',
        current === value && 'bg-accent text-accent-foreground font-medium',
        className,
      )}
    >
      {children}
    </div>
  )
}

export { Select, SelectTrigger, SelectValue, SelectContent, SelectItem }
