'use client'

import { useState } from 'react'
import { Plus, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import {
  useAttributeGroups,
  useCreateAttributeGroup,
  useCreateAttribute,
  useDeleteAttribute,
} from '@/features/catalog/hooks'
import type { AttributeType } from '@store/shared-types'

// ── Add Attribute Form ────────────────────────────────────────────────────────

function AddAttributeForm({ groupUuid, onClose }: { groupUuid: string; onClose: () => void }) {
  const { mutate: createAttribute, isPending } = useCreateAttribute(groupUuid)
  const [value,    setValue]    = useState('')
  const [label,    setLabel]    = useState('')
  const [colorHex, setColorHex] = useState('')

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!value.trim() || !label.trim()) return
    createAttribute(
      { value: value.trim(), label: label.trim(), color_hex: colorHex || undefined },
      {
        onSuccess: () => {
          toast.success('Atributo adicionado.')
          onClose()
        },
        onError: (err) => toast.error(err instanceof Error ? err.message : 'Erro ao adicionar atributo.'),
      },
    )
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-wrap items-end gap-2 mt-2 p-3 border rounded-md bg-muted/30">
      <div className="space-y-1">
        <Label className="text-xs">Valor *</Label>
        <Input className="h-8 text-xs" placeholder="P" value={value} onChange={(e) => setValue(e.target.value)} required />
      </div>
      <div className="space-y-1">
        <Label className="text-xs">Rótulo *</Label>
        <Input className="h-8 text-xs" placeholder="Pequeno" value={label} onChange={(e) => setLabel(e.target.value)} required />
      </div>
      <div className="space-y-1">
        <Label className="text-xs">Cor (hex)</Label>
        <Input className="h-8 text-xs w-28" placeholder="#000000" value={colorHex} onChange={(e) => setColorHex(e.target.value)} />
      </div>
      <Button type="submit" size="sm" disabled={isPending}>
        {isPending ? 'Salvando…' : 'Adicionar'}
      </Button>
      <Button type="button" variant="ghost" size="sm" onClick={onClose}>Cancelar</Button>
    </form>
  )
}

// ── Create Group Form ─────────────────────────────────────────────────────────

function CreateGroupForm({ onClose }: { onClose: () => void }) {
  const { mutate: createGroup, isPending } = useCreateAttributeGroup()
  const [name, setName] = useState('')
  const [type, setType] = useState<AttributeType>('text')

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!name.trim()) return
    createGroup(
      { name: name.trim(), type },
      {
        onSuccess: () => {
          toast.success('Grupo criado com sucesso.')
          onClose()
        },
        onError: (err) => toast.error(err instanceof Error ? err.message : 'Erro ao criar grupo.'),
      },
    )
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-wrap items-end gap-2 p-4 border rounded-md bg-muted/30">
      <div className="space-y-1">
        <Label className="text-xs">Nome do Grupo *</Label>
        <Input className="h-8 text-xs" placeholder="Ex.: Tamanho, Cor" value={name} onChange={(e) => setName(e.target.value)} required />
      </div>
      <div className="space-y-1">
        <Label className="text-xs">Tipo</Label>
        <select
          value={type}
          onChange={(e) => setType(e.target.value as AttributeType)}
          className="h-8 rounded-md border bg-background px-2 text-xs outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="text">Texto</option>
          <option value="color">Cor</option>
          <option value="number">Número</option>
          <option value="boolean">Booleano</option>
        </select>
      </div>
      <Button type="submit" size="sm" disabled={isPending}>{isPending ? 'Salvando…' : 'Criar'}</Button>
      <Button type="button" variant="ghost" size="sm" onClick={onClose}>Cancelar</Button>
    </form>
  )
}

// ── Delete Attribute Handler ──────────────────────────────────────────────────

interface DeleteAttrInfo {
  groupUuid: string
  attrUuid:  string
}

function DeleteAttrDialog({
  deleteInfo,
  onClose,
}: {
  deleteInfo: DeleteAttrInfo | null
  onClose:    () => void
}) {
  const { mutate: deleteAttr, isPending } = useDeleteAttribute(deleteInfo?.groupUuid ?? '')

  return (
    <ConfirmDialog
      open={Boolean(deleteInfo)}
      onOpenChange={(open) => { if (!open) onClose() }}
      onConfirm={() => {
        if (!deleteInfo) return
        deleteAttr(deleteInfo.attrUuid, {
          onSuccess: () => {
            toast.success('Atributo removido.')
            onClose()
          },
          onError: (err) => {
            toast.error(err instanceof Error ? err.message : 'Erro ao remover atributo.')
            onClose()
          },
        })
      }}
      loading={isPending}
      title="Remover atributo?"
      description="O atributo será removido do grupo."
      confirmLabel="Remover"
    />
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function AttributesPage() {
  const { data: groups = [], isLoading }      = useAttributeGroups()
  const [showCreateGroup, setShowCreateGroup] = useState(false)
  const [addingToGroup,   setAddingToGroup]   = useState<string | null>(null)
  const [deleteInfo, setDeleteInfo]           = useState<DeleteAttrInfo | null>(null)

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Atributos"
        description="Gerencie grupos de atributos e seus valores."
        actions={
          <Button onClick={() => setShowCreateGroup(true)} disabled={showCreateGroup}>
            <Plus className="mr-2 h-4 w-4" />
            Novo Grupo
          </Button>
        }
      />

      {showCreateGroup && (
        <CreateGroupForm onClose={() => setShowCreateGroup(false)} />
      )}

      {isLoading ? (
        <div className="space-y-4">
          {Array.from({ length: 3 }).map((_, i) => (
            <Skeleton key={i} className="h-24 w-full rounded-lg" />
          ))}
        </div>
      ) : groups.length === 0 ? (
        <p className="text-center text-sm text-muted-foreground py-12">
          Nenhum grupo de atributos cadastrado.
        </p>
      ) : (
        <div className="space-y-4">
          {groups.map((group) => (
            <AppCard
              key={group.uuid}
              title={group.name}
              description={`Tipo: ${group.type}`}
              actions={
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setAddingToGroup(addingToGroup === group.uuid ? null : group.uuid)}
                >
                  <Plus className="mr-1.5 h-3.5 w-3.5" />
                  Adicionar
                </Button>
              }
            >
              <div className="space-y-2">
                <div className="flex flex-wrap gap-2">
                  {!group.attributes || group.attributes.length === 0 ? (
                    <p className="text-xs text-muted-foreground">Nenhum atributo neste grupo.</p>
                  ) : (
                    group.attributes.map((attr) => (
                      <Badge
                        key={attr.uuid}
                        variant="secondary"
                        className="gap-1 pr-1 text-xs"
                      >
                        {attr.color_hex && (
                          <span
                            className="inline-block h-3 w-3 rounded-full border"
                            style={{ backgroundColor: attr.color_hex }}
                          />
                        )}
                        {attr.label}
                        <button
                          type="button"
                          onClick={() => setDeleteInfo({ groupUuid: group.uuid, attrUuid: attr.uuid })}
                          className="ml-0.5 p-0.5 rounded hover:text-destructive transition-colors"
                          aria-label={`Remover ${attr.label}`}
                        >
                          <Trash2 className="h-3 w-3" />
                        </button>
                      </Badge>
                    ))
                  )}
                </div>
                {addingToGroup === group.uuid && (
                  <AddAttributeForm
                    groupUuid={group.uuid}
                    onClose={() => setAddingToGroup(null)}
                  />
                )}
              </div>
            </AppCard>
          ))}
        </div>
      )}

      <DeleteAttrDialog
        deleteInfo={deleteInfo}
        onClose={() => setDeleteInfo(null)}
      />
    </div>
  )
}
