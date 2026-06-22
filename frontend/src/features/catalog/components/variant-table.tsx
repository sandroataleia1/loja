'use client'

import { useState } from 'react'
import { Trash2, Zap } from 'lucide-react'
import { toast } from 'sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import { useDeleteVariant, useGenerateVariants, useGrids } from '@/features/catalog/hooks'
import type { ProductVariant } from '@store/shared-types'
import type { GenerateVariantsRequest } from '@store/contracts'

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatBRL(value: number): string {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
}

function getAttributeLabel(variant: ProductVariant): string {
  if (!variant.grid_combination || variant.grid_combination.length === 0) return '—'
  return variant.grid_combination.map((c) => c.attr_value).join(' / ')
}

// ── Generate Variants Form ────────────────────────────────────────────────────

interface GenerateVariantsFormProps {
  productUuid: string
  onSuccess:   () => void
  onClose:     () => void
}

function GenerateVariantsForm({ productUuid, onSuccess, onClose }: GenerateVariantsFormProps) {
  const { data: grids = [], isLoading: gridsLoading } = useGrids()
  const { mutate: generate, isPending }                = useGenerateVariants()

  const [gridId,     setGridId]     = useState('')
  const [skuPrefix,  setSkuPrefix]  = useState('')
  const [basePrice,  setBasePrice]  = useState('')

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!gridId || !skuPrefix || !basePrice) return

    const payload: GenerateVariantsRequest = {
      product_id:       productUuid,
      grid_id:          gridId,
      sku_prefix:       skuPrefix,
      base_price_cents: Math.round(parseFloat(basePrice) * 100),
    }

    generate(payload, {
      onSuccess: () => {
        toast.success('Variantes geradas com sucesso.')
        onSuccess()
        onClose()
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao gerar variantes.')
      },
    })
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div className="bg-background rounded-lg border p-6 w-full max-w-sm space-y-4 shadow-xl">
        <h3 className="font-semibold text-base">Gerar Variantes</h3>
        <form onSubmit={handleSubmit} className="space-y-3">
          <div className="space-y-1.5">
            <label className="text-sm font-medium">Variação *</label>
            <select
              disabled={gridsLoading || isPending}
              value={gridId}
              onChange={(e) => setGridId(e.target.value)}
              className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
              required
            >
              <option value="">Selecione a variação…</option>
              {grids.map((g) => (
                <option key={g.uuid} value={g.uuid}>{g.name}</option>
              ))}
            </select>
          </div>
          <div className="space-y-1.5">
            <label className="text-sm font-medium">Prefixo SKU *</label>
            <input
              type="text"
              value={skuPrefix}
              onChange={(e) => setSkuPrefix(e.target.value)}
              placeholder="Ex.: PORTO-001"
              required
              className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
            />
          </div>
          <div className="space-y-1.5">
            <label className="text-sm font-medium">Preço Base (R$) *</label>
            <input
              type="number"
              min="0"
              step="0.01"
              value={basePrice}
              onChange={(e) => setBasePrice(e.target.value)}
              placeholder="0,00"
              required
              className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
            />
          </div>
          <div className="flex justify-end gap-2 pt-1">
            <Button type="button" variant="outline" size="sm" onClick={onClose} disabled={isPending}>
              Cancelar
            </Button>
            <Button type="submit" size="sm" disabled={isPending || !gridId}>
              {isPending ? 'Gerando…' : 'Gerar'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  )
}

// ── Variant Table ─────────────────────────────────────────────────────────────

interface VariantTableProps {
  variants:    ProductVariant[]
  productUuid: string
  onRefresh:   () => void
}

export function VariantTable({ variants, productUuid, onRefresh }: VariantTableProps) {
  const [deleteUuid,   setDeleteUuid]   = useState<string | null>(null)
  const [showGenerate, setShowGenerate] = useState(false)

  const { mutate: deleteVariant, isPending: isDeleting } = useDeleteVariant(productUuid)

  function handleDeleteConfirm() {
    if (!deleteUuid) return
    deleteVariant(deleteUuid, {
      onSuccess: () => {
        toast.success('Variante excluída.')
        setDeleteUuid(null)
        onRefresh()
      },
      onError: (err) => {
        toast.error(err instanceof Error ? err.message : 'Erro ao excluir variante.')
        setDeleteUuid(null)
      },
    })
  }

  return (
    <div className="space-y-3">
      <div className="flex justify-end">
        <Button
          variant="outline"
          size="sm"
          onClick={() => setShowGenerate(true)}
        >
          <Zap className="mr-1.5 h-3.5 w-3.5" />
          Gerar Variantes
        </Button>
      </div>

      {variants.length === 0 ? (
        <p className="text-sm text-muted-foreground text-center py-8 border rounded-md">
          Nenhuma variante cadastrada.
        </p>
      ) : (
        <div className="rounded-md border overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50 text-left">
                <th className="px-4 py-3 font-medium text-muted-foreground">SKU</th>
                <th className="px-4 py-3 font-medium text-muted-foreground">Nome / Atributos</th>
                <th className="px-4 py-3 font-medium text-muted-foreground">Preço Venda</th>
                <th className="px-4 py-3 font-medium text-muted-foreground">Custo</th>
                <th className="px-4 py-3 font-medium text-muted-foreground">Ativo</th>
                <th className="px-4 py-3 font-medium text-muted-foreground">Ações</th>
              </tr>
            </thead>
            <tbody>
              {variants.map((variant) => (
                <tr key={variant.uuid} className="border-b last:border-0 hover:bg-muted/50 transition-colors">
                  <td className="px-4 py-3 font-mono text-xs text-muted-foreground">{variant.sku}</td>
                  <td className="px-4 py-3">
                    <div className="font-medium">{variant.name ?? getAttributeLabel(variant)}</div>
                    {variant.name && variant.grid_combination && variant.grid_combination.length > 0 && (
                      <div className="text-xs text-muted-foreground">{getAttributeLabel(variant)}</div>
                    )}
                  </td>
                  <td className="px-4 py-3">{formatBRL(variant.sale_price)}</td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {variant.cost_price != null ? formatBRL(variant.cost_price) : '—'}
                  </td>
                  <td className="px-4 py-3">
                    {variant.is_active ? (
                      <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">
                        Ativo
                      </Badge>
                    ) : (
                      <Badge variant="outline" className="bg-gray-100 text-gray-600 border-gray-200">
                        Inativo
                      </Badge>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <Button
                      variant="ghost"
                      size="icon"
                      className="text-destructive hover:text-destructive"
                      onClick={() => setDeleteUuid(variant.uuid)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <ConfirmDialog
        open={Boolean(deleteUuid)}
        onOpenChange={(open) => { if (!open) setDeleteUuid(null) }}
        onConfirm={handleDeleteConfirm}
        loading={isDeleting}
        title="Excluir variante?"
        description="Esta ação não pode ser desfeita."
        confirmLabel="Excluir"
      />

      {showGenerate && (
        <GenerateVariantsForm
          productUuid={productUuid}
          onSuccess={onRefresh}
          onClose={() => setShowGenerate(false)}
        />
      )}
    </div>
  )
}
