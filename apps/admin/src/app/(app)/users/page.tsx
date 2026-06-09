import type { Metadata } from 'next'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { UsersTableWrapper } from '@/features/rbac/components/users-table-wrapper'

export const metadata: Metadata = { title: 'Usuários' }

export default function UsersPage() {
  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Usuários"
        description="Gerencie os usuários e suas permissões dentro do tenant."
      />
      <AppCard>
        <UsersTableWrapper />
      </AppCard>
    </div>
  )
}
