'use client'

import { useState } from 'react'
import { Plus, Edit2, Trash2, ChevronRight, ChevronDown, Landmark } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { ConfirmDialog } from '@/components/shared/confirm-dialog'
import { useCostCenterTree, useDeleteCostCenter } from '@/features/cost-centers/hooks'
import type { CostCenter } from '@/services/cost-centers.service'
import { CostCenterFormDialog } from './cost-center-form-dialog'

function CostCenterRow({
  cc,
  depth = 0,
  onEdit,
  onDelete,
}: {
  cc:       CostCenter
  depth?:   number
  onEdit:   (uuid: string) => void
  onDelete: (uuid: string) => void
}) {
  const [open, setOpen] = useState(true)
  const hasChildren = (cc.children?.length ?? 0) > 0

  return (
    <>
      <tr className="hover:bg-muted/50 transition-colors">
        <td className="px-4 py-2.5">
          <div className="flex items-center gap-1" style={{ paddingLeft: depth * 20 }}>
            {hasChildren ? (
              <button
                onClick={() => setOpen(v => !v)}
                className="text-muted-foreground hover:text-foreground"
              >
                {open ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
              </button>
            ) : (
              <span className="w-4 h-4 inline-block" />
            )}
            <Landmark className="h-3.5 w-3.5 text-muted-foreground ml-1" />
            <span className="font-medium ml-1">{cc.name}</span>
          </div>
        </td>
        <td className="px-4 py-2.5 text-muted-foreground font-mono text-xs">{cc.code}</td>
        <td className="px-4 py-2.5">
          <Badge variant="outline">{cc.type_label}</Badge>
        </td>
        <td className="px-4 py-2.5">
          <Badge variant={cc.is_active ? 'default' : 'secondary'}>
            {cc.is_active ? 'Ativo' : 'Inativo'}
          </Badge>
        </td>
        <td className="px-4 py-2.5">
          <div className="flex items-center gap-1">
            <Button size="icon" variant="ghost" className="h-7 w-7" onClick={() => onEdit(cc.uuid)}>
              <Edit2 className="h-3.5 w-3.5" />
            </Button>
            {!hasChildren && (
              <Button size="icon" variant="ghost" className="h-7 w-7 text-destructive hover:text-destructive" onClick={() => onDelete(cc.uuid)}>
                <Trash2 className="h-3.5 w-3.5" />
              </Button>
            )}
          </div>
        </td>
      </tr>
      {open && cc.children?.map((child) => (
        <CostCenterRow key={child.uuid} cc={child} depth={depth + 1} onEdit={onEdit} onDelete={onDelete} />
      ))}
    </>
  )
}

export default function CostCentersPage() {
  const [deleteUuid, setDeleteUuid] = useState<string | null>(null)
  const [editUuid,   setEditUuid]   = useState<string | null>(null)
  const [showCreate, setShowCreate] = useState(false)

  const { data: tree = [], isLoading } = useCostCenterTree()
  const { mutate: deleteFn, isPending: isDeleting } = useDeleteCostCenter()

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Centros de Custo"
        description="Gerencie a hierarquia de centros de custo da empresa."
        actions={
          <Button onClick={() => setShowCreate(true)}>
            <Plus className="mr-2 h-4 w-4" />
            Novo Centro de Custo
          </Button>
        }
      />

      <div className="rounded-lg border bg-card">
        {isLoading ? (
          <div className="p-8 text-center text-sm text-muted-foreground">Carregando...</div>
        ) : tree.length === 0 ? (
          <div className="p-8 text-center text-sm text-muted-foreground">Nenhum centro de custo cadastrado.</div>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b text-left text-xs text-muted-foreground">
                <th className="px-4 py-3 font-medium">Nome</th>
                <th className="px-4 py-3 font-medium">Código</th>
                <th className="px-4 py-3 font-medium">Tipo</th>
                <th className="px-4 py-3 font-medium">Status</th>
                <th className="px-4 py-3 font-medium w-20"></th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {tree.map((cc) => (
                <CostCenterRow
                  key={cc.uuid}
                  cc={cc}
                  onEdit={setEditUuid}
                  onDelete={setDeleteUuid}
                />
              ))}
            </tbody>
          </table>
        )}
      </div>

      {(showCreate || editUuid !== null) && (
        <CostCenterFormDialog
          open
          costCenterUuid={editUuid}
          tree={tree}
          onClose={() => { setShowCreate(false); setEditUuid(null) }}
        />
      )}

      <ConfirmDialog
        open={!!deleteUuid}
        onOpenChange={(v) => { if (!v) setDeleteUuid(null) }}
        title="Remover Centro de Custo"
        description="Esta ação não pode ser desfeita."
        confirmLabel="Remover"
        loading={isDeleting}
        onConfirm={() => {
          if (!deleteUuid) return
          deleteFn(deleteUuid, { onSuccess: () => setDeleteUuid(null) })
        }}
      />
    </div>
  )
}
