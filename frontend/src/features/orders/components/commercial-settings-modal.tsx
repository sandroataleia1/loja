'use client'

import { useState, useEffect } from 'react'
import { createPortal } from 'react-dom'
import { Settings } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { toast } from 'sonner'

const LS_KEY = 'commercial_settings'

export interface CommercialSettings {
  default_validity_days: number
}

export function loadCommercialSettings(): CommercialSettings {
  try {
    const raw = typeof window !== 'undefined' ? localStorage.getItem(LS_KEY) : null
    if (raw) return { default_validity_days: 30, ...JSON.parse(raw) as Partial<CommercialSettings> }
  } catch {}
  return { default_validity_days: 30 }
}

function saveCommercialSettings(settings: CommercialSettings) {
  localStorage.setItem(LS_KEY, JSON.stringify(settings))
}

export function CommercialSettingsButton() {
  const [open, setOpen] = useState(false)
  return (
    <>
      <Button
        type="button"
        variant="ghost"
        size="icon"
        onClick={() => setOpen(true)}
        title="Configurações Comerciais"
      >
        <Settings className="h-4 w-4" />
      </Button>
      {open && <CommercialSettingsModal onClose={() => setOpen(false)} />}
    </>
  )
}

function CommercialSettingsModal({ onClose }: { onClose: () => void }) {
  const [validityDays, setValidityDays] = useState(30)

  useEffect(() => {
    const s = loadCommercialSettings()
    setValidityDays(s.default_validity_days)
  }, [])

  function handleSave() {
    saveCommercialSettings({ default_validity_days: validityDays })
    toast.success('Configurações salvas.')
    onClose()
  }

  return createPortal(
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60"
      onClick={onClose}
    >
      <div
        className="bg-background rounded-xl shadow-2xl w-full max-w-sm"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-center justify-between px-5 py-4 border-b">
          <h2 className="font-semibold text-base flex items-center gap-2">
            <Settings className="h-4 w-4" /> Configurações Comerciais
          </h2>
          <Button type="button" variant="ghost" size="icon" onClick={onClose}>
            <span className="text-base leading-none">✕</span>
          </Button>
        </div>

        <div className="p-5 space-y-4">
          <div className="space-y-1.5">
            <Label htmlFor="validity_days">Validade padrão do orçamento (dias)</Label>
            <Input
              id="validity_days"
              type="number"
              min={1}
              max={365}
              value={validityDays}
              onChange={(e) => setValidityDays(parseInt(e.target.value) || 30)}
            />
            <p className="text-xs text-muted-foreground">Padrão: 30 dias.</p>
          </div>
        </div>

        <div className="flex justify-end gap-2 px-5 py-4 border-t">
          <Button type="button" variant="outline" onClick={onClose}>Cancelar</Button>
          <Button type="button" onClick={handleSave}>Salvar</Button>
        </div>
      </div>
    </div>,
    document.body
  )
}
