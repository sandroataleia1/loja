import type { Metadata } from 'next'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { StoreAccessList } from '@/features/rbac/components/store-access-list'

export const metadata: Metadata = { title: 'Acesso por Loja' }

export default function StoreAccessPage() {
  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Acesso por Loja"
        description="Visualize e gerencie quais usuários têm acesso a cada loja."
      />
      <StoreAccessList />
    </div>
  )
}
