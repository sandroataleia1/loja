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
import { useCreateCarrier, useUpdateCarrier } from '@/features/carriers/hooks'
import { carriersService, type CreateCarrierPayload } from '@/services/carriers.service'
import { useQuery } from '@tanstack/react-query'

interface Props {
  open:         boolean
  supplierUuid: string | null
  onClose:      () => void
}

type FormValues = CreateCarrierPayload & { is_active: boolean }

export function CarrierFormDialog({ open, supplierUuid: carrierUuid, onClose }: Props) {
  const isEdit = !!carrierUuid

  const { data: existing } = useQuery({
    queryKey: ['carriers', carrierUuid],
    queryFn:  () => carriersService.getCarrier(carrierUuid!),
    enabled:  !!carrierUuid,
    staleTime: 0,
  })

  const { register, handleSubmit, reset, setValue, watch } = useForm<FormValues>({
    defaultValues: { is_active: true },
  })

  useEffect(() => {
    if (existing) {
      reset({
        name:       existing.name,
        trade_name: existing.trade_name ?? '',
        cnpj:       existing.cnpj ?? '',
        ie:         existing.ie ?? '',
        email:      existing.email ?? '',
        phone:      existing.phone ?? '',
        notes:      existing.notes ?? '',
        is_active:  existing.is_active,
      })
    } else if (!isEdit) {
      reset({ is_active: true })
    }
  }, [existing, isEdit, reset])

  const { mutate: createFn, isPending: isCreating } = useCreateCarrier()
  const { mutate: updateFn, isPending: isUpdating }  = useUpdateCarrier()
  const isPending = isCreating || isUpdating

  function onSubmit(values: FormValues) {
    if (isEdit) {
      updateFn({ uuid: carrierUuid!, data: values }, { onSuccess: onClose })
    } else {
      createFn(values, { onSuccess: onClose })
    }
  }

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) onClose() }}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{isEdit ? 'Editar Transportadora' : 'Nova Transportadora'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Razão Social <span className="text-destructive">*</span></Label>
            <Input {...register('name', { required: true })} />
          </div>
          <div className="space-y-1.5">
            <Label>Nome Fantasia</Label>
            <Input {...register('trade_name')} />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <Label>CNPJ</Label>
              <Input placeholder="00.000.000/0000-00" {...register('cnpj')} />
            </div>
            <div className="space-y-1.5">
              <Label>Inscrição Estadual</Label>
              <Input {...register('ie')} />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <Label>E-mail</Label>
              <Input type="email" {...register('email')} />
            </div>
            <div className="space-y-1.5">
              <Label>Telefone</Label>
              <Input {...register('phone')} />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Observações</Label>
            <Textarea rows={2} {...register('notes')} />
          </div>
          <div className="flex items-center gap-2">
            <Switch
              id="carrier-active"
              checked={watch('is_active')}
              onCheckedChange={(v) => setValue('is_active', v)}
            />
            <Label htmlFor="carrier-active">Ativa</Label>
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
