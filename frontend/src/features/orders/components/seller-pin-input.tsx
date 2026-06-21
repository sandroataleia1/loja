'use client'

import { useState, useEffect, useRef } from 'react'
import { User, Loader2, CheckCircle, XCircle } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { quotesService } from '@/services/orders.service'

interface Props {
  value:              string
  onChange:           (pin: string) => void
  onSellerResolved:   (seller: { uuid: string; name: string } | null) => void
  disabled?:          boolean
}

export function SellerPinInput({ value, onChange, onSellerResolved, disabled }: Props) {
  const [status,     setStatus]     = useState<'idle' | 'loading' | 'found' | 'error'>('idle')
  const [sellerName, setSellerName] = useState<string | null>(null)
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null)

  useEffect(() => {
    if (timerRef.current) clearTimeout(timerRef.current)

    if (!value || value.length < 3) {
      setStatus('idle')
      setSellerName(null)
      onSellerResolved(null)
      return
    }

    setStatus('loading')
    timerRef.current = setTimeout(async () => {
      try {
        const seller = await quotesService.resolveSellerPin(value)
        setSellerName(seller.name)
        setStatus('found')
        onSellerResolved(seller)
      } catch {
        setSellerName(null)
        setStatus('error')
        onSellerResolved(null)
      }
    }, 600)

    return () => { if (timerRef.current) clearTimeout(timerRef.current) }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [value])

  return (
    <div className="space-y-1.5">
      <Label htmlFor="seller_pin">PIN do Vendedor</Label>
      <div className="relative">
        <Input
          id="seller_pin"
          type="text"
          inputMode="numeric"
          maxLength={10}
          placeholder="Digite o PIN"
          value={value}
          onChange={(e) => onChange(e.target.value)}
          disabled={disabled}
          className="pr-8"
        />
        <div className="absolute right-2.5 top-1/2 -translate-y-1/2">
          {status === 'loading' && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />}
          {status === 'found'   && <CheckCircle className="h-4 w-4 text-green-500" />}
          {status === 'error'   && <XCircle className="h-4 w-4 text-destructive" />}
        </div>
      </div>
      {status === 'found' && sellerName && (
        <p className="text-xs text-green-600 flex items-center gap-1">
          <User className="h-3 w-3" /> {sellerName}
        </p>
      )}
      {status === 'error' && (
        <p className="text-xs text-destructive">PIN inválido ou vendedor inativo.</p>
      )}
    </div>
  )
}
