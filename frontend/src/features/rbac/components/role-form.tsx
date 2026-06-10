'use client'

import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { useRouter } from 'next/navigation'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AppCard } from '@/components/shared/app-card'
import { useCreateRole, useUpdateRole } from '../hooks'
import { ROUTES } from '@/constants'
import type { Role } from '@store/shared-types'
import type { CreateRoleRequest } from '@store/contracts'

interface RoleFormValues {
  name:        string
  description: string
}

interface RoleFormProps {
  /** When provided the form is in edit mode */
  role?: Role
}

export function RoleForm({ role }: RoleFormProps) {
  const router  = useRouter()
  const isEdit  = Boolean(role)

  const { register, handleSubmit, reset, formState: { errors, isSubmitting } } =
    useForm<RoleFormValues>({
      defaultValues: {
        name:        role?.name        ?? '',
        description: role?.description ?? '',
      },
    })

  const createMutation = useCreateRole()
  const updateMutation = useUpdateRole(role?.uuid ?? '')

  useEffect(() => {
    if (role) {
      reset({ name: role.name, description: role.description ?? '' })
    }
  }, [role, reset])

  async function onSubmit(values: RoleFormValues) {
    const payload: CreateRoleRequest = {
      name:        values.name,
      description: values.description || undefined,
    }

    if (isEdit && role) {
      updateMutation.mutate(payload, {
        onSuccess: () => {
          toast.success('Role atualizado com sucesso.')
          router.push(`${ROUTES.ROLES}/${role.uuid}`)
        },
        onError: (err) => toast.error(err instanceof Error ? err.message : 'Erro ao atualizar role.'),
      })
    } else {
      createMutation.mutate(payload, {
        onSuccess: (created) => {
          toast.success('Role criado com sucesso.')
          router.push(`${ROUTES.ROLES}/${created.uuid}`)
        },
        onError: (err) => toast.error(err instanceof Error ? err.message : 'Erro ao criar role.'),
      })
    }
  }

  const isPending = createMutation.isPending || updateMutation.isPending || isSubmitting

  return (
    <AppCard title={isEdit ? 'Editar Role' : 'Novo Role'}>
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 max-w-lg">
        <div className="space-y-1.5">
          <Label htmlFor="name">Nome *</Label>
          <Input
            id="name"
            placeholder="Ex.: Supervisor"
            {...register('name', { required: 'Nome é obrigatório' })}
          />
          {errors.name && (
            <p className="text-xs text-destructive">{errors.name.message}</p>
          )}
        </div>

        <div className="space-y-1.5">
          <Label htmlFor="description">Descrição</Label>
          <Input
            id="description"
            placeholder="Descreva as responsabilidades deste role…"
            {...register('description')}
          />
        </div>

        <div className="flex gap-2 pt-2">
          <Button type="submit" disabled={isPending}>
            {isPending ? 'Salvando…' : isEdit ? 'Salvar alterações' : 'Criar role'}
          </Button>
          <Button
            type="button"
            variant="outline"
            onClick={() => router.push(ROUTES.ROLES)}
          >
            Cancelar
          </Button>
        </div>
      </form>
    </AppCard>
  )
}
