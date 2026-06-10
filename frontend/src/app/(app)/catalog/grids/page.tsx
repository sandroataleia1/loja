'use client'

import { useState } from 'react'
import { Plus } from 'lucide-react'
import { toast } from 'sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { useGrids, useCreateGrid, useAttributeGroups } from '@/features/catalog/hooks'

// ── Create Grid Form ──────────────────────────────────────────────────────────

function CreateGridForm({ onClose }: { onClose: () => void }) {
  const { mutate: createGrid, isPending }  = useCreateGrid()
  const { data: groups = [], isLoading }   = useAttributeGroups()

  const [name,    setName]    = useState('')
  const [groupId, setGroupId] = useState('')
  const [desc,    setDesc]    = useState('')
  const [selectedAttrs, setSelectedAttrs] = useState<string[]>([])

  const selectedGroup = groups.find((g) => g.uuid === groupId)

  function toggleAttr(uuid: string) {
    setSelectedAttrs((prev) =>
      prev.includes(uuid) ? prev.filter((a) => a !== uuid) : [...prev, uuid],
    )
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!name.trim() || !groupId || selectedAttrs.length === 0) return
    createGrid(
      {
        name:               name.trim(),
        attribute_group_id: groupId,
        description:        desc.trim() || undefined,
        attribute_ids:      selectedAttrs,
      },
      {
        onSuccess: () => {
          toast.success('Grade criada com sucesso.')
          onClose()
        },
        onError: (err) => toast.error(err instanceof Error ? err.message : 'Erro ao criar grade.'),
      },
    )
  }

  return (
    <AppCard title="Nova Grade">
      <form onSubmit={handleSubmit} className="space-y-4 max-w-xl">
        <div className="space-y-1.5">
          <Label htmlFor="grid_name">Nome *</Label>
          <Input
            id="grid_name"
            placeholder="Ex.: Grade de Tamanhos P-M-G"
            value={name}
            onChange={(e) => setName(e.target.value)}
            required
          />
        </div>

        <div className="space-y-1.5">
          <Label htmlFor="grid_group">Grupo de Atributos *</Label>
          <select
            id="grid_group"
            disabled={isLoading}
            value={groupId}
            onChange={(e) => { setGroupId(e.target.value); setSelectedAttrs([]) }}
            className="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring"
            required
          >
            <option value="">Selecione o grupo…</option>
            {groups.map((g) => (
              <option key={g.uuid} value={g.uuid}>{g.name}</option>
            ))}
          </select>
        </div>

        {selectedGroup && selectedGroup.attributes && selectedGroup.attributes.length > 0 && (
          <div className="space-y-1.5">
            <Label>Atributos incluídos *</Label>
            <div className="flex flex-wrap gap-2">
              {selectedGroup.attributes.map((attr) => (
                <button
                  key={attr.uuid}
                  type="button"
                  onClick={() => toggleAttr(attr.uuid)}
                  className={[
                    'rounded-full border px-2.5 py-1 text-xs font-medium transition-colors',
                    selectedAttrs.includes(attr.uuid)
                      ? 'bg-primary text-primary-foreground border-primary'
                      : 'bg-background hover:bg-muted',
                  ].join(' ')}
                >
                  {attr.label}
                </button>
              ))}
            </div>
          </div>
        )}

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
            disabled={isPending || !name.trim() || !groupId || selectedAttrs.length === 0}
          >
            {isPending ? 'Salvando…' : 'Criar Grade'}
          </Button>
          <Button type="button" variant="outline" size="sm" onClick={onClose}>Cancelar</Button>
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
        title="Grades"
        description="Gerencie as grades de variação (tamanhos, cores, etc.)."
        actions={
          <Button onClick={() => setShowCreate(true)} disabled={showCreate}>
            <Plus className="mr-2 h-4 w-4" />
            Nova Grade
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
          Nenhuma grade cadastrada.
        </p>
      ) : (
        <div className="space-y-4">
          {grids.map((grid) => (
            <AppCard
              key={grid.uuid}
              title={grid.name}
              description={grid.description ?? undefined}
            >
              <div className="space-y-2">
                {grid.attributes && grid.attributes.length > 0 ? (
                  <div className="flex flex-wrap gap-2">
                    {grid.attributes.map((attr) => (
                      <Badge key={attr.uuid} variant="secondary" className="text-xs">
                        {attr.label}
                      </Badge>
                    ))}
                  </div>
                ) : (
                  <p className="text-xs text-muted-foreground">Nenhum atributo nesta grade.</p>
                )}
              </div>
            </AppCard>
          ))}
        </div>
      )}
    </div>
  )
}
