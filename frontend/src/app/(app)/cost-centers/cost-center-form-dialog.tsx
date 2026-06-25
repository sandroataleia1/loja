'use client'

import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { useCreateCostCenter, useUpdateCostCenter } from '@/features/cost-centers/hooks'
import {
  costCentersService,
  COST_CENTER_TYPE_LABELS,
  type CreateCostCenterPayload,
  type CostCenterType,
  type CostCenter,
} from '@/services/cost-centers.service'
import { useQuery } from '@tanstack/react-query'

interface Props {
  open:            boolean
  costCenterUuid:  string | null
  tree:            CostCenter[]
  onClose:         () => void
}

type FormValues = CreateCostCenterPayload & { is_active: boolean }

function flattenTree(nodes: CostCenter[], depth = 0): { uuid: string; label: string }[] {
  return nodes.flatMap((n) => [
    { uuid: n.uuid, label: '—'.repeat(depth) + ' ' + n.name },
    ...flattenTree(n.children ?? [], depth + 1),
  ])
}

export function CostCenterFormDialog({ open, costCenterUuid, tree, onClose }: Props) {
  const isEdit = !!costCenterUuid

  const { data: existing } = useQuery({
    queryKey:  ['cost-centers', costCenterUuid],
    queryFn:   () => costCentersService.getCostCenter(costCenterUuid!),
    enabled:   !!costCenterUuid,
    staleTime: 0,
  })

  const { register, handleSubmit, reset, setValue, watch } = useForm<FormValues>({
    defaultValues: { type: 'ADMINISTRATIVE', is_active: true },
  })

  useEffect(() => {
    if (existing) {
      reset({
        parent_id:  existing.parent_id ?? '',
        code:       existing.code,
        name:       existing.name,
        type:       existing.type,
        is_active:  existing.is_active,
      })
    } else if (!isEdit) {
      reset({ type: 'ADMINISTRATIVE', is_active: true })
    }
  }, [existing, isEdit, reset])

  const { mutate: createFn, isPending: isCreating } = useCreateCostCenter()
  const { mutate: updateFn, isPending: isUpdating }  = useUpdateCostCenter()
  const isPending = isCreating || isUpdating

  function onSubmit(values: FormValues) {
    const payload: CreateCostCenterPayload = {
      ...values,
      parent_id: values.parent_id || undefined,
    }
    if (isEdit) {
      updateFn({ uuid: costCenterUuid!, data: payload }, { onSuccess: onClose })
    } else {
      createFn(payload, { onSuccess: onClose })
    }
  }

  const type     = watch('type')
  const parentId = watch('parent_id')
  const flatList = flattenTree(tree).filter((n) => n.uuid !== costCenterUuid)

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) onClose() }}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{isEdit ? 'Editar Centro de Custo' : 'Novo Centro de Custo'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Centro de Custo Pai</Label>
            <Select value={parentId ?? ''} onValueChange={(v) => setValue('parent_id', v || '')}>
              <SelectTrigger><SelectValue placeholder="Nenhum (raiz)" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="">Nenhum (raiz)</SelectItem>
                {flatList.map((n) => (
                  <SelectItem key={n.uuid} value={n.uuid}>{n.label}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <Label>Código <span className="text-destructive">*</span></Label>
              <Input placeholder="Ex: 1.1" {...register('code', { required: true })} />
            </div>
            <div className="space-y-1.5">
              <Label>Tipo <span className="text-destructive">*</span></Label>
              <Select value={type} onValueChange={(v) => setValue('type', v as CostCenterType)}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {(Object.entries(COST_CENTER_TYPE_LABELS) as [CostCenterType, string][]).map(([k, label]) => (
                    <SelectItem key={k} value={k}>{label}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="space-y-1.5">
            <Label>Nome <span className="text-destructive">*</span></Label>
            <Input {...register('name', { required: true })} />
          </div>

          <div className="flex items-center gap-2">
            <Switch
              id="cc-active"
              checked={watch('is_active')}
              onCheckedChange={(v) => setValue('is_active', v)}
            />
            <Label htmlFor="cc-active">Ativo</Label>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={onClose} disabled={isPending}>Cancelar</Button>
            <Button type="submit" disabled={isPending}>{isPending ? 'Salvando...' : isEdit ? 'Salvar' : 'Criar'}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
