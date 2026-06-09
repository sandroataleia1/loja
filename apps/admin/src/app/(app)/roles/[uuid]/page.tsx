import type { Metadata } from 'next'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { RoleDetail } from '@/features/rbac/components/role-detail'

export const metadata: Metadata = { title: 'Editar Perfil de Acesso' }

interface RolePageProps {
  params: Promise<{ uuid: string }>
}

export default async function RolePage({ params }: RolePageProps) {
  const { uuid } = await params

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Editar Perfil de Acesso"
        description="Altere o nome, descricao e as permissoes deste perfil de acesso."
      />
      <RoleDetail uuid={uuid} />
    </div>
  )
}
