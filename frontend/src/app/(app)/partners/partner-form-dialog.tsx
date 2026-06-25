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
import { useCreatePartner, useUpdatePartner } from '@/features/partners/hooks'
import {
  partnersService,
  PARTNER_TYPE_LABELS,
  type CreatePartnerPayload,
  type PartnerType,
} from '@/services/partners.service'
import { useQuery } from '@tanstack/react-query'

interface Props {
  open:        boolean
  partnerUuid: string | null
  onClose:     () => void
}

type FormValues = CreatePartnerPayload & { is_active: boolean }

export function PartnerFormDialog({ open, partnerUuid, onClose }: Props) {
  const isEdit = !!partnerUuid

  const { data: existing } = useQuery({
    queryKey:  ['partners', partnerUuid],
    queryFn:   () => partnersService.getPartner(partnerUuid!),
    enabled:   !!partnerUuid,
    staleTime: 0,
  })

  const { register, handleSubmit, reset, setValue, watch } = useForm<FormValues>({
    defaultValues: { type: 'OTHER', is_active: true },
  })

  useEffect(() => {
    if (existing) {
      reset({
        name:      existing.name,
        type:      existing.type,
        document:  existing.document ?? '',
        email:     existing.email ?? '',
        phone:     existing.phone ?? '',
        notes:     existing.notes ?? '',
        is_active: existing.is_active,
      })
    } else if (!isEdit) {
      reset({ type: 'OTHER', is_active: true })
    }
  }, [existing, isEdit, reset])

  const { mutate: createFn, isPending: isCreating } = useCreatePartner()
  const { mutate: updateFn, isPending: isUpdating }  = useUpdatePartner()
  const isPending = isCreating || isUpdating

  function onSubmit(values: FormValues) {
    if (isEdit) {
      updateFn({ uuid: partnerUuid!, data: values }, { onSuccess: onClose })
    } else {
      createFn(values, { onSuccess: onClose })
    }
  }

  const type = watch('type')

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) onClose() }}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{isEdit ? 'Editar Parceiro' : 'Novo Parceiro'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <div className="space-y-1.5">
            <Label>Nome <span className="text-destructive">*</span></Label>
            <Input {...register('name', { required: true })} />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <Label>Tipo <span className="text-destructive">*</span></Label>
              <Select value={type} onValueChange={(v) => setValue('type', v as PartnerType)}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {(Object.entries(PARTNER_TYPE_LABELS) as [PartnerType, string][]).map(([k, label]) => (
                    <SelectItem key={k} value={k}>{label}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>CPF / CNPJ</Label>
              <Input {...register('document')} />
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
              id="partner-active"
              checked={watch('is_active')}
              onCheckedChange={(v) => setValue('is_active', v)}
            />
            <Label htmlFor="partner-active">Ativo</Label>
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
