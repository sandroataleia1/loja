'use client'

import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { useCreateSeller, useUpdateSeller } from '@/features/sellers/hooks'
import { sellersService, type CreateSellerPayload } from '@/services/sellers.service'
import { useQuery } from '@tanstack/react-query'
import { useUsers } from '@/features/rbac/hooks'

interface Props {
  open:       boolean
  sellerUuid: string | null
  onClose:    () => void
}

type FormValues = CreateSellerPayload & { is_active: boolean }

export function SellerFormDialog({ open, sellerUuid, onClose }: Props) {
  const isEdit = !!sellerUuid

  const { data: usersData } = useUsers()
  const users = usersData ?? []

  const { data: existing } = useQuery({
    queryKey:  ['sellers', sellerUuid],
    queryFn:   () => sellersService.getSeller(sellerUuid!),
    enabled:   !!sellerUuid,
    staleTime: 0,
  })

  const { register, handleSubmit, reset, setValue, watch } = useForm<FormValues>({
    defaultValues: { is_active: true },
  })

  useEffect(() => {
    if (existing) {
      reset({
        user_id:         existing.user_id,
        code:            existing.code ?? '',
        commission_rate: existing.commission_rate,
        is_active:       existing.is_active,
        notes:           existing.notes ?? '',
      })
    } else if (!isEdit) {
      reset({ is_active: true })
    }
  }, [existing, isEdit, reset])

  const { mutate: createFn, isPending: isCreating } = useCreateSeller()
  const { mutate: updateFn, isPending: isUpdating }  = useUpdateSeller()
  const isPending = isCreating || isUpdating

  function onSubmit(values: FormValues) {
    const payload = {
      ...values,
      commission_rate: values.commission_rate != null ? Number(values.commission_rate) : undefined,
    }
    if (isEdit) {
      updateFn({ uuid: sellerUuid!, data: payload }, { onSuccess: onClose })
    } else {
      createFn(payload, { onSuccess: onClose })
    }
  }

  const userId = watch('user_id')

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) onClose() }}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{isEdit ? 'Editar Vendedor' : 'Habilitar Vendedor'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          {!isEdit && (
            <div className="space-y-1.5">
              <Label>Usuário <span className="text-destructive">*</span></Label>
              <Select value={userId ?? ''} onValueChange={(v) => setValue('user_id', v)}>
                <SelectTrigger>
                  <SelectValue placeholder="Selecione um usuário..." />
                </SelectTrigger>
                <SelectContent>
                  {users.map((u) => (
                    <SelectItem key={u.uuid} value={u.user_id ?? u.uuid}>
                      {u.name ?? u.email ?? u.uuid}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          )}

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <Label>Código</Label>
              <Input placeholder="Ex: V001" {...register('code')} />
            </div>
            <div className="space-y-1.5">
              <Label>Comissão (%)</Label>
              <Input type="number" step="0.01" min="0" max="100" {...register('commission_rate')} />
            </div>
          </div>

          <div className="space-y-1.5">
            <Label>Observações</Label>
            <Textarea rows={2} {...register('notes')} />
          </div>

          <div className="flex items-center gap-2">
            <Switch
              id="seller-active"
              checked={watch('is_active')}
              onCheckedChange={(v) => setValue('is_active', v)}
            />
            <Label htmlFor="seller-active">Ativo</Label>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={onClose} disabled={isPending}>Cancelar</Button>
            <Button type="submit" disabled={isPending}>{isPending ? 'Salvando...' : isEdit ? 'Salvar' : 'Habilitar'}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
