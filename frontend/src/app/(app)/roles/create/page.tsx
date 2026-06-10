import type { Metadata } from 'next'
import Link from 'next/link'
import { ChevronLeft } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { RoleForm } from '@/features/rbac/components/role-form'
import { ROUTES } from '@/constants'

export const metadata: Metadata = { title: 'Novo Perfil de Acesso' }

export default function CreateRolePage() {
  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Novo Perfil de Acesso"
        description="Defina um novo perfil de acesso para o seu tenant."
        actions={
          <Button variant="outline" asChild>
            <Link href={ROUTES.ROLES}>
              <ChevronLeft className="mr-1.5 h-4 w-4" />
              Voltar
            </Link>
          </Button>
        }
      />
      <RoleForm />
    </div>
  )
}
