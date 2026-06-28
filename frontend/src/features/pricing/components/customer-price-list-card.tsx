'use client'

import { useState } from 'react'
import { Tag, Loader2, Check, Info } from 'lucide-react'
import { toast } from 'sonner'
import { useQueryClient } from '@tanstack/react-query'
import { AppCard } from '@/components/shared/app-card'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { apiClient } from '@/lib/api-client'
import { usePriceLists } from '@/features/pricing/hooks'
import { CUSTOMER_QUERY_KEYS } from '@/features/customers/hooks'

// ── Types ────────────────────────────────────────────────────────────────────

interface CustomerPriceListCardProps {
  customerUuid:         string
  currentPriceListUuid: string | null
}

// ── Component ────────────────────────────────────────────────────────────────

export function CustomerPriceListCard({
  customerUuid,
  currentPriceListUuid,
}: CustomerPriceListCardProps) {
  const qc = useQueryClient()

  const { data: priceLists, isLoading: loadingLists } = usePriceLists()

  const [editing, setEditing]           = useState(false)
  const [saving, setSaving]             = useState(false)
  const [selectedUuid, setSelectedUuid] = useState<string>(currentPriceListUuid ?? '')
  const [searchText, setSearchText]     = useState('')

  // ── Derive current list name ──────────────────────────────────────────────
  const currentList = priceLists?.find(pl => pl.uuid === currentPriceListUuid)

  // ── Save handler ──────────────────────────────────────────────────────────
  async function handleSave() {
    if (!selectedUuid) return
    setSaving(true)
    try {
      await apiClient.patch(`/customers/${customerUuid}/price-list`, {
        price_list_id: selectedUuid,
      })
      await qc.invalidateQueries({ queryKey: CUSTOMER_QUERY_KEYS.CUSTOMER(customerUuid) })
      toast.success('Tabela de preços atualizada')
      setEditing(false)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Erro ao atualizar tabela de preços')
    } finally {
      setSaving(false)
    }
  }

  function handleCancel() {
    setSelectedUuid(currentPriceListUuid ?? '')
    setEditing(false)
  }

  // ── Render ────────────────────────────────────────────────────────────────

  return (
    <AppCard
      title="Tabela de Preços"
      actions={<Tag className="h-4 w-4 text-muted-foreground" />}
    >
      <div className="space-y-4">
        {/* Current list */}
        <div className="space-y-1">
          <Label className="text-xs text-muted-foreground">Lista vinculada</Label>

          {loadingLists ? (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" />
              <span>Carregando...</span>
            </div>
          ) : editing ? (
            <div className="flex items-center gap-2">
              <Select
                value={selectedUuid}
                onValueChange={setSelectedUuid}
              >
                <SelectTrigger className="flex-1">
                  <SelectValue placeholder="Selecione uma lista..." />
                </SelectTrigger>
                <SelectContent>
                  {priceLists
                    ?.filter(pl => pl.is_active !== false)
                    .map(pl => (
                      <SelectItem key={pl.uuid} value={pl.uuid}>
                        <span>{pl.name}</span>
                        {pl.type && (
                          <span className="ml-2 text-xs text-muted-foreground capitalize">
                            ({pl.type})
                          </span>
                        )}
                      </SelectItem>
                    ))}
                </SelectContent>
              </Select>

              <Button
                size="sm"
                onClick={handleSave}
                disabled={saving || !selectedUuid}
              >
                {saving ? (
                  <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                  <Check className="h-4 w-4" />
                )}
              </Button>
              <Button size="sm" variant="outline" onClick={handleCancel} disabled={saving}>
                Cancelar
              </Button>
            </div>
          ) : (
            <div className="flex items-center justify-between">
              <div>
                {currentList ? (
                  <div>
                    <p className="text-sm font-medium">{currentList.name}</p>
                    {currentList.type && (
                      <p className="text-xs text-muted-foreground capitalize">
                        Tipo: {currentList.type}
                      </p>
                    )}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground italic">
                    Nenhuma lista vinculada (usa lista padrão)
                  </p>
                )}
              </div>
              <Button
                variant="outline"
                size="sm"
                onClick={() => {
                  setSelectedUuid(currentPriceListUuid ?? '')
                  setEditing(true)
                }}
              >
                Alterar
              </Button>
            </div>
          )}
        </div>

        {/* Divider */}
        <hr />

        {/* Price preview section */}
        <div className="space-y-2">
          <Label className="text-xs text-muted-foreground">Consultar preço de produto</Label>
          <div className="flex gap-2">
            <Input
              placeholder="Buscar produto..."
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              className="flex-1"
            />
            <Button
              variant="outline"
              size="sm"
              onClick={() => {
                toast.info('Funcionalidade de preview disponível no PDV')
              }}
            >
              Ver preço
            </Button>
          </div>
          <div className="flex items-start gap-2 rounded-md bg-muted/40 p-2.5 text-xs text-muted-foreground">
            <Info className="h-3.5 w-3.5 mt-0.5 shrink-0" />
            <span>
              A consulta de preço por produto está disponível no módulo PDV, onde o contexto de
              variante é resolvido automaticamente.
            </span>
          </div>
        </div>
      </div>
    </AppCard>
  )
}
