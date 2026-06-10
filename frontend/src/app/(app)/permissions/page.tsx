import type { Metadata } from 'next'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { PermissionsMatrix } from '@/features/rbac/components/permissions-matrix'

export const metadata: Metadata = { title: 'Permissões' }

export default function PermissionsPage() {
  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Permissões"
        description="Todas as permissões disponíveis no sistema, agrupadas por módulo."
      />
      <AppCard>
        <PermissionsMatrix />
      </AppCard>
    </div>
  )
}
