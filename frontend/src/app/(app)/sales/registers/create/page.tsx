'use client'

import { useRouter } from 'next/navigation'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { useCreateCashRegister } from '@/features/sales/hooks'
import { useStores } from '@/features/inventory/hooks'
import { ROUTES } from '@/constants'

const schema = z.object({
  store_id:  z.string().uuid('Selecione uma loja'),
  code:      z.string().min(1, 'Código obrigatório').max(30),
  name:      z.string().min(1, 'Nome obrigatório').max(100),
  is_active: z.boolean().optional(),
})

type FormValues = z.infer<typeof schema>

export default function CreateCashRegisterPage() {
  const router = useRouter()
  const { data: stores = [] } = useStores()
  const { mutate: create, isPending } = useCreateCashRegister()

  const { register, handleSubmit, formState: { errors } } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { is_active: true },
  })

  function onSubmit(data: FormValues) {
    create(data, {
      onSuccess: () => { toast.success('Caixa criado com sucesso.'); router.push(ROUTES.CASH_REGISTERS) },
      onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao criar caixa.'),
    })
  }

  return (
    <div className="space-y-6 max-w-lg">
      <AppPageHeader title="Novo Caixa PDV" />
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        <div className="space-y-1">
          <Label>Loja</Label>
          <select {...register('store_id')} className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="">Selecione...</option>
            {stores.map((s) => <option key={s.uuid} value={s.uuid}>{s.name}</option>)}
          </select>
          {errors.store_id && <p className="text-xs text-destructive">{errors.store_id.message}</p>}
        </div>

        <div className="space-y-1">
          <Label>Código</Label>
          <Input {...register('code')} placeholder="PDV01" />
          {errors.code && <p className="text-xs text-destructive">{errors.code.message}</p>}
        </div>

        <div className="space-y-1">
          <Label>Nome</Label>
          <Input {...register('name')} placeholder="Caixa Principal" />
          {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
        </div>

        <div className="flex items-center gap-2">
          <input type="checkbox" id="is_active" {...register('is_active')} className="rounded" />
          <Label htmlFor="is_active">Ativo</Label>
        </div>

        <Button type="submit" disabled={isPending}>
          {isPending ? 'Criando...' : 'Criar Caixa'}
        </Button>
      </form>
    </div>
  )
}
