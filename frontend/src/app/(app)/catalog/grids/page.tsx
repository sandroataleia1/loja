'use client'

import { useState } from 'react'
import { Plus, Trash2, PlusCircle } from 'lucide-react'
import { toast } from 'sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { useGrids, useCreateGrid, useAttributeGroups } from '@/features/catalog/hooks'

// ── Types ─────────────────────────────────────────────────────────────────────

interface Dimension {
  groupId:       string
  selectedAttrs: string[]
}

// ── Create Grid Form ──────────────────────────────────────────────────────────

function CreateGridForm({ onClose }: { onClose: () => void }) {
  const { mutate: createGrid, isPending } = useCreateGrid()
  const { data: groups = [], isLoading }  = useAttributeGroups()

  const [name,       setName]       = useState('')
  const [desc,       setDesc]       = useState('')
  const [dimensions, setDimensions] = useState<Dimension[]>([{ groupId: '', selectedAttrs: [] }])

  // IDs de grupos já usados em outras dimensões (evita duplicatas)
  function usedGroupIds(excludeIdx: number) {
    return dimensions.filter((_, i) => i !== excludeIdx).map((d) => d.groupId).filter(Boolean)
  }

  function setDimensionGroup(idx: number, groupId: string) {
    setDimensions((prev) =>
      prev.map((d, i) => (i === idx ? { groupId, selectedAttrs: [] } : d)),
    )
  }

  function toggleAttr(dimIdx: number, attrUuid: string) {
    setDimensions((prev) =>
      prev.map((d, i) => {
        if (i !== dimIdx) return d
        return {
          ...d,
          selectedAttrs: d.selectedAttrs.includes(attrUuid)
            ? d.selectedAttrs.filter((a) => a !== attrUuid)
            : [...d.selectedAttrs, attrUuid],
        }
      }),
    )
  }

  function addDimension() {
    setDimensions((prev) => [...prev, { groupId: '', selectedAttrs: [] }])
  }

  function removeDimension(idx: number) {
    setDimensions((prev) => prev.filter((_, i) => i !== idx))
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()

    const allAttrIds = dimensions.flatMap((d) => d.selectedAttrs)

    if (!name.trim() || allAttrIds.length === 0) return

    // Garante ao menos 1 atributo por dimensão configurada
    const hasEmptyDimension = dimensions.some((d) => d.groupId && d.selectedAttrs.length === 0)
    if (hasEmptyDimension) {
      toast.error('Selecione ao menos um atributo em cada dimensão configurada.')
      return
    }

    // Para variação simples (1 dimensão), mantém backward compat enviando attribute_group_id
    const isSingleDimension = dimensions.filter((d) => d.groupId).length === 1
    const primaryGroupId    = isSingleDimension ? dimensions.find((d) => d.groupId)?.groupId : undefined

    createGrid(
      {
        name:               name.trim(),
        attribute_group_id: primaryGroupId,
        description:        desc.trim() || undefined,
        attribute_ids:      allAttrIds,
      },
      {
        onSuccess: () => {
          toast.success('Variação criada com sucesso.')
          onClose()
        },
        onError: (err) => toast.error(err instanceof Error ? err.message : 'Erro ao criar variação.'),
      },
    )
  }

  const isMultiDim    = dimensions.filter((d) => d.groupId).length > 1
  const totalSelected = dimensions.reduce((n, d) => n + d.selectedAttrs.length, 0)
  const totalCombos   = dimensions.reduce(
    (n, d) => (d.selectedAttrs.length > 0 ? n * d.selectedAttrs.length : n),
    1,
  )

  return (
    <AppCard title="Nova Variação">
      <form onSubmit={handleSubmit} className="space-y-5 max-w-xl">
        <div className="space-y-1.5">
          <Label htmlFor="grid_name">Nome *</Label>
          <Input
            id="grid_name"
            placeholder="Ex.: Variação Cor × Tamanho"
            value={name}
            onChange={(e) => setName(e.target.value)}
            required
          />
        </div>

        {/* Dimensões */}
        <div className="space-y-3">
          <Label>
            Dimensões *
            {isMultiDim && totalSelected > 0 && (
              <span className="ml-2 text-xs font-normal text-muted-foreground">
                {totalCombos} combinações geradas
              </span>
            )}
          </Label>

          {dimensions.map((dim, idx) => {
            const group   = groups.find((g) => g.uuid === dim.groupId)
            const blocked = usedGroupIds(idx)

            return (
              <div key={idx} className="rounded-md border p-3 space-y-2 bg-muted/30">
                <div className="flex items-center gap-2">
                  <select
                    value={dim.groupId}
                    onChange={(e) => setDimensionGroup(idx, e.target.value)}
                    disabled={isLoading}
                    className="flex-1 rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
                  >
                    <option value="">Selecione o grupo de atributos…</option>
                    {groups.map((g) => (
                      <option key={g.uuid} value={g.uuid} disabled={blocked.includes(g.uuid)}>
                        {g.name}{blocked.includes(g.uuid) ? ' (já adicionado)' : ''}
                      </option>
                    ))}
                  </select>

                  {dimensions.length > 1 && (
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8 text-destructive shrink-0"
                      onClick={() => removeDimension(idx)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  )}
                </div>

                {group && group.attributes && group.attributes.length > 0 && (
                  <div className="flex flex-wrap gap-2 pt-1">
                    {group.attributes.map((attr) => (
                      <button
                        key={attr.uuid}
                        type="button"
                        onClick={() => toggleAttr(idx, attr.uuid)}
                        className={[
                          'rounded-full border px-2.5 py-1 text-xs font-medium transition-colors',
                          dim.selectedAttrs.includes(attr.uuid)
                            ? 'bg-primary text-primary-foreground border-primary'
                            : 'bg-background hover:bg-muted',
                        ].join(' ')}
                      >
                        {attr.label}
                      </button>
                    ))}
                  </div>
                )}
              </div>
            )
          })}

          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={addDimension}
            disabled={dimensions.length >= groups.length}
          >
            <PlusCircle className="mr-1.5 h-4 w-4" />
            Adicionar dimensão
          </Button>
        </div>

        <div className="space-y-1.5">
          <Label htmlFor="grid_desc">Descrição</Label>
          <Input
            id="grid_desc"
            placeholder="Descrição opcional"
            value={desc}
            onChange={(e) => setDesc(e.target.value)}
          />
        </div>

        <div className="flex gap-2">
          <Button
            type="submit"
            size="sm"
            disabled={isPending || !name.trim() || totalSelected === 0}
          >
            {isPending ? 'Salvando…' : 'Criar Variação'}
          </Button>
          <Button type="button" variant="outline" size="sm" onClick={onClose}>
            Cancelar
          </Button>
        </div>
      </form>
    </AppCard>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function GridsPage() {
  const { data: grids = [], isLoading } = useGrids()
  const [showCreate, setShowCreate]     = useState(false)

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Variações"
        description="Gerencie as variações de produto. Variações multi-dimensionais geram combinações automáticas (ex: Cor × Tamanho)."
        actions={
          <Button onClick={() => setShowCreate(true)} disabled={showCreate}>
            <Plus className="mr-2 h-4 w-4" />
            Nova Variação
          </Button>
        }
      />

      {showCreate && <CreateGridForm onClose={() => setShowCreate(false)} />}

      {isLoading ? (
        <div className="space-y-4">
          {Array.from({ length: 3 }).map((_, i) => (
            <Skeleton key={i} className="h-24 w-full rounded-lg" />
          ))}
        </div>
      ) : grids.length === 0 && !showCreate ? (
        <p className="text-center text-sm text-muted-foreground py-12">
          Nenhuma variação cadastrada.
        </p>
      ) : (
        <div className="space-y-4">
          {grids.map((grid) => {
            // Detecta se é multi-dimensional agrupando por attribute_group_id
            const groups = grid.attributes
              ? [...new Set(grid.attributes.map((a: { attribute_group_id?: string }) => a.attribute_group_id).filter(Boolean))]
              : []
            const isMulti = groups.length > 1

            return (
              <AppCard
                key={grid.uuid}
                title={grid.name}
                description={grid.description ?? undefined}
              >
                <div className="space-y-2">
                  {isMulti && (
                    <p className="text-xs text-muted-foreground">
                      {groups.length} dimensões · {grid.attributes?.length ?? 0} atributos
                    </p>
                  )}
                  {grid.attributes && grid.attributes.length > 0 ? (
                    <div className="flex flex-wrap gap-2">
                      {grid.attributes.map((attr: { uuid: string; label: string }) => (
                        <Badge key={attr.uuid} variant="secondary" className="text-xs">
                          {attr.label}
                        </Badge>
                      ))}
                    </div>
                  ) : (
                    <p className="text-xs text-muted-foreground">Nenhum atributo nesta variação.</p>
                  )}
                </div>
              </AppCard>
            )
          })}
        </div>
      )}
    </div>
  )
}
