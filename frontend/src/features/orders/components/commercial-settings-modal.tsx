'use client'

import { useState, useEffect } from 'react'
import { createPortal } from 'react-dom'
import { Settings, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { toast } from 'sonner'
import { useSystemSettings, useUpdateSection } from '@/features/system-settings/hooks'

export interface CommercialSettings {
  default_validity_days: number
}

/** @deprecated Usar useSystemSettings().data.commercial.quote_validity_days */
export function loadCommercialSettings(): CommercialSettings {
  return { default_validity_days: 30 }
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
  const { data: settings, isLoading } = useSystemSettings()
  const { mutate: updateSection, isPending } = useUpdateSection('commercial')

  const [validityDays, setValidityDays] = useState(30)

  useEffect(() => {
    if (settings?.commercial?.quote_validity_days !== undefined) {
      setValidityDays(settings.commercial.quote_validity_days)
    }
  }, [settings])

  function handleSave() {
    updateSection(
      { quote_validity_days: validityDays },
      {
        onSuccess: () => {
          toast.success('Configurações salvas.')
          onClose()
        },
        onError: () => toast.error('Erro ao salvar configurações.'),
      },
    )
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
          {isLoading ? (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" /> Carregando...
            </div>
          ) : (
            <div className="space-y-1.5">
              <Label htmlFor="validity_days">Validade padrão do orçamento (dias)</Label>
              <Input
                id="validity_days"
                type="number"
                min={1}
                max={3650}
                value={validityDays}
                onChange={(e) => setValidityDays(parseInt(e.target.value) || 30)}
              />
              <p className="text-xs text-muted-foreground">Padrão: 30 dias.</p>
            </div>
          )}
        </div>

        <div className="flex justify-end gap-2 px-5 py-4 border-t">
          <Button type="button" variant="outline" onClick={onClose} disabled={isPending}>Cancelar</Button>
          <Button type="button" onClick={handleSave} disabled={isPending || isLoading}>
            {isPending ? <><Loader2 className="h-4 w-4 animate-spin mr-2" />Salvando...</> : 'Salvar'}
          </Button>
        </div>
      </div>
    </div>,
    document.body
  )
}
