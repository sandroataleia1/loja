import type { Metadata } from 'next'
import Link from 'next/link'
import { Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { RolesList } from '@/features/rbac/components/roles-list'
import { ROUTES } from '@/constants'

export const metadata: Metadata = { title: 'Perfis de Acesso' }

export default function RolesPage() {
  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Perfis de Acesso"
        description="Gerencie os perfis de acesso e suas permissões."
        actions={
          <Button asChild>
            <Link href={`${ROUTES.ROLES}/create`}>
              <Plus className="mr-2 h-4 w-4" />
              Novo Perfil
            </Link>
          </Button>
        }
      />
      <RolesList />
    </div>
  )
}
