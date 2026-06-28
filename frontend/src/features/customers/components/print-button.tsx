'use client'

import { useState } from 'react'
import { FileText, Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { getToken } from '@/lib/api-client'

type PrintType = 'registration-card' | 'registration-card-blank' | 'contract' | 'contract-blank'

interface PrintButtonProps {
  label:         string
  customerUuid?: string  // não necessário para blank
  type:          PrintType
  variant?:      'default' | 'outline' | 'ghost'
  size?:         'default' | 'sm' | 'icon'
}

// Build the URL based on type and customer uuid
function buildUrl(type: PrintType, uuid?: string): string {
  const base = '/api/v1/customers'
  switch (type) {
    case 'registration-card':       return `${base}/${uuid}/registration-card`
    case 'registration-card-blank': return `${base}/registration-card/blank`
    case 'contract':                return `${base}/${uuid}/contract`
    case 'contract-blank':          return `${base}/contract/template`
  }
}

export function PrintButton({
  label,
  customerUuid,
  type,
  variant = 'outline',
  size = 'sm',
}: PrintButtonProps) {
  const [loading, setLoading] = useState(false)

  async function handleClick() {
    setLoading(true)
    try {
      const token = getToken()
      const url = buildUrl(type, customerUuid)

      const res = await fetch(url, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      })

      if (!res.ok) {
        throw new Error(`Erro ${res.status}: ${res.statusText}`)
      }

      const blob = await res.blob()
      const objectUrl = URL.createObjectURL(blob)
      const win = window.open(objectUrl, '_blank')
      if (!win) toast.error('Pop-up bloqueado. Permita pop-ups para esta página.')
      // Revoke after 60s
      setTimeout(() => URL.revokeObjectURL(objectUrl), 60_000)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Erro ao gerar PDF.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Button variant={variant} size={size} onClick={handleClick} disabled={loading}>
      {loading ? (
        <Loader2 className="h-3.5 w-3.5 mr-1.5 animate-spin" />
      ) : (
        <FileText className="h-3.5 w-3.5 mr-1.5" />
      )}
      {label}
    </Button>
  )
}
