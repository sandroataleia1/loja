'use client'

import { useState } from 'react'
import { FileText, QrCode, Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { getToken } from '@/lib/api-client'

type PrintType = 'datasheet' | 'qrcode'

interface CatalogPrintButtonProps {
  productUuid: string
  type:        PrintType
  label?:      string
  variant?:    'default' | 'outline' | 'ghost'
  size?:       'default' | 'sm' | 'icon'
}

const API_BASE = process.env.NEXT_PUBLIC_API_URL ?? '/api/v1'

export function CatalogPrintButton({
  productUuid,
  type,
  label,
  variant = 'outline',
  size = 'sm',
}: CatalogPrintButtonProps) {
  const [loading, setLoading] = useState(false)

  async function handleClick() {
    setLoading(true)
    try {
      const token = getToken()
      const path  = type === 'datasheet'
        ? `/catalog/products/${productUuid}/datasheet`
        : `/catalog/products/${productUuid}/qrcode`
      const url = `${API_BASE}${path}`

      const res = await fetch(url, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      })

      if (!res.ok) throw new Error(`Erro ${res.status}: ${res.statusText}`)

      const blob      = await res.blob()
      const objectUrl = URL.createObjectURL(blob)
      const win       = window.open(objectUrl, '_blank')
      if (!win) toast.warning('Pop-up bloqueado. Permita pop-ups para abrir o arquivo.')
      setTimeout(() => URL.revokeObjectURL(objectUrl), 60_000)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Erro ao gerar arquivo.')
    } finally {
      setLoading(false)
    }
  }

  const Icon = loading ? Loader2 : type === 'qrcode' ? QrCode : FileText
  const defaultLabel = type === 'datasheet' ? 'Ficha técnica' : 'QR Code'

  return (
    <Button variant={variant} size={size} onClick={handleClick} disabled={loading}>
      <Icon className={`h-3.5 w-3.5 ${label !== undefined || size !== 'icon' ? 'mr-1.5' : ''} ${loading ? 'animate-spin' : ''}`} />
      {size !== 'icon' && (label ?? defaultLabel)}
    </Button>
  )
}
