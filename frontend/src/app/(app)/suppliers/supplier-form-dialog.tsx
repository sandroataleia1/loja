'use client'

import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'
import { useCreateSupplier, useUpdateSupplier } from '@/features/purchasing/hooks'
import { purchasingService, type CreateSupplierPayload } from '@/services/purchasing.service'
import { useQuery } from '@tanstack/react-query'

interface Props {
  open:         boolean
  supplierUuid: string | null
  onClose:      () => void
}

type FormValues = CreateSupplierPayload & { is_active: boolean }

export function SupplierFormDialog({ open, supplierUuid, onClose }: Props) {
  const isEdit = !!supplierUuid

  const { data: existing } = useQuery({
    queryKey:  ['suppliers', supplierUuid],
    queryFn:   () => purchasingService.getSupplier(supplierUuid!),
    enabled:   !!supplierUuid,
    staleTime: 0,
  })

  const { register, handleSubmit, reset, setValue, watch, formState: { errors } } = useForm<FormValues>({
    defaultValues: { person_type: 'COMPANY', is_active: true },
  })

  useEffect(() => {
    if (existing) {
      reset({
        person_type: existing.person_type as 'INDIVIDUAL' | 'COMPANY',
        name:        existing.name,
        trade_name:  existing.trade_name ?? '',
        document:    existing.document ?? '',
        ie:          existing.ie ?? '',
        im:          existing.im ?? '',
        email:       existing.email ?? '',
        phone:       existing.phone ?? '',
        notes:       existing.notes ?? '',
        is_active:   existing.is_active,
      })
    } else if (!isEdit) {
      reset({ person_type: 'COMPANY', is_active: true })
    }
  }, [existing, isEdit, reset])

  const { mutate: createFn, isPending: isCreating } = useCreateSupplier()
  const { mutate: updateFn, isPending: isUpdating }  = useUpdateSupplier()
  const isPending = isCreating || isUpdating

  function onSubmit(values: FormValues) {
    const payload = { ...values }
    if (isEdit) {
      updateFn({ uuid: supplierUuid!, data: payload }, { onSuccess: onClose })
    } else {
      createFn(payload, { onSuccess: onClose })
    }
  }

  const personType = watch('person_type')

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) onClose() }}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{isEdit ? 'Editar Fornecedor' : 'Novo Fornecedor'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <Label>Tipo de Pessoa</Label>
              <Select value={personType} onValueChange={(v) => setValue('person_type', v as 'INDIVIDUAL' | 'COMPANY')}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="COMPANY">Pessoa Jurídica</SelectItem>
                  <SelectItem value="INDIVIDUAL">Pessoa Física</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>CNPJ / CPF</Label>
              <Input placeholder={personType === 'COMPANY' ? '00.000.000/0000-00' : '000.000.000-00'} {...register('document')} />
            </div>
          </div>

          <div className="space-y-1.5">
            <Label>Razão Social / Nome <span className="text-destructive">*</span></Label>
            <Input {...register('name', { required: 'Nome obrigatório' })} />
            {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
          </div>

          <div className="space-y-1.5">
            <Label>Nome Fantasia</Label>
            <Input {...register('trade_name')} />
          </div>

          {personType === 'COMPANY' && (
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-1.5">
                <Label>Inscrição Estadual</Label>
                <Input placeholder="IE" {...register('ie')} />
              </div>
              <div className="space-y-1.5">
                <Label>Inscrição Municipal</Label>
                <Input placeholder="IM" {...register('im')} />
              </div>
            </div>
          )}

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
              id="supplier-active"
              checked={watch('is_active')}
              onCheckedChange={(v) => setValue('is_active', v)}
            />
            <Label htmlFor="supplier-active">Ativo</Label>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={onClose} disabled={isPending}>
              Cancelar
            </Button>
            <Button type="submit" disabled={isPending}>
              {isPending ? 'Salvando...' : isEdit ? 'Salvar' : 'Criar'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
