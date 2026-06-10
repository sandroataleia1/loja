'use client'

import Link from 'next/link'
import { ChevronLeft } from 'lucide-react'
import { use } from 'react'
import { Button } from '@/components/ui/button'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { RoleForm } from '@/features/rbac/components/role-form'
import { Skeleton } from '@/components/ui/skeleton'
import { useRole } from '@/features/rbac/hooks'
import { ROUTES } from '@/constants'

interface EditRolePageProps {
  params: Promise<{ uuid: string }>
}

function EditRoleContent({ uuid }: { uuid: string }) {
  const { data: role, isLoading } = useRole(uuid)

  if (isLoading) {
    return <Skeleton className="h-48 w-full rounded-lg" />
  }

  return <RoleForm role={role} />
}

export default function EditRolePage({ params }: EditRolePageProps) {
  const { uuid } = use(params)

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Editar Perfil de Acesso"
        description="Atualize o nome e a descrição deste perfil de acesso."
        actions={
          <Button variant="outline" asChild>
            <Link href={`${ROUTES.ROLES}/${uuid}`}>
              <ChevronLeft className="mr-1.5 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />
      <EditRoleContent uuid={uuid} />
    </div>
  )
}
