import type { Metadata } from 'next'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { EmptyState } from '@/components/states/empty-state'
import { HeartHandshake } from 'lucide-react'

export const metadata: Metadata = { title: 'CRM' }

export default function CrmPage() {
  return (
    <div className="space-y-6">
      <AppPageHeader title="CRM" description="Relacionamento com clientes e campanhas." />
      <AppCard>
        <EmptyState
          icon={<HeartHandshake className="h-8 w-8 text-muted-foreground" />}
          title="Módulo em desenvolvimento"
          description="O módulo de CRM estará disponível em breve."
        />
      </AppCard>
    </div>
  )
}
