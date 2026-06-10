import type { Metadata } from 'next'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { EmptyState } from '@/components/states/empty-state'
import { FileText } from 'lucide-react'

export const metadata: Metadata = { title: 'Fiscal' }

export default function FiscalPage() {
  return (
    <div className="space-y-6">
      <AppPageHeader title="Fiscal" description="Emissão de notas fiscais e obrigações fiscais." />
      <AppCard>
        <EmptyState
          icon={<FileText className="h-8 w-8 text-muted-foreground" />}
          title="Módulo em desenvolvimento"
          description="O módulo fiscal estará disponível em breve."
        />
      </AppCard>
    </div>
  )
}
