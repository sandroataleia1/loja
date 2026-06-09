'use client'

import Link from 'next/link'
import { Pencil, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { AppCard } from '@/components/shared/app-card'
import { useRoles, useDeleteRole } from '../hooks'
import { ROUTES } from '@/constants'
import type { Role } from '@store/shared-types'

function RoleCard({ role, onDelete }: { role: Role; onDelete: (uuid: string) => void }) {
  return (
    <AppCard
      title={role.name}
      description={role.description ?? undefined}
      actions={
        <div className="flex items-center gap-1">
          <Button variant="ghost" size="icon" asChild title="Editar permissoes">
            <Link href={`${ROUTES.ROLES}/${role.uuid}`} aria-label="Editar perfil">
              <Pencil className="h-4 w-4" />
            </Link>
          </Button>
          {!role.is_system && (
            <Button
              variant="ghost"
              size="icon"
              className="text-destructive hover:text-destructive"
              aria-label="Excluir role"
              onClick={() => onDelete(role.uuid)}
            >
              <Trash2 className="h-4 w-4" />
            </Button>
          )}
        </div>
      }
    >
      <div className="flex flex-wrap gap-2 items-center">
        <span className="text-sm text-muted-foreground">
          {role.permissions.length} permissao(oes)
        </span>
        <Badge variant="secondary" className="font-mono text-xs">{role.slug}</Badge>
      </div>
    </AppCard>
  )
}

export function RolesList() {
  const { data: roles, isLoading, isError } = useRoles()
  const { mutate: deleteRole, isPending } = useDeleteRole()

  function handleDelete(uuid: string) {
    deleteRole(uuid, {
      onSuccess: () => toast.success('Role excluído com sucesso.'),
      onError:   (err) => toast.error(err instanceof Error ? err.message : 'Erro ao excluir role.'),
    })
  }

  if (isLoading) {
    return (
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {Array.from({ length: 4 }).map((_, i) => (
          <Skeleton key={i} className="h-32 rounded-lg" />
        ))}
      </div>
    )
  }

  if (isError) {
    return (
      <p className="text-sm text-muted-foreground text-center py-8">
        Erro ao carregar roles.
      </p>
    )
  }

  if (!roles || roles.length === 0) {
    return (
      <p className="text-sm text-muted-foreground text-center py-8">
        Nenhum role encontrado.
      </p>
    )
  }

  return (
    <div className={`grid gap-4 sm:grid-cols-2 lg:grid-cols-3 ${isPending ? 'opacity-70 pointer-events-none' : ''}`}>
      {roles.map((role) => (
        <RoleCard key={role.uuid} role={role} onDelete={handleDelete} />
      ))}
    </div>
  )
}
