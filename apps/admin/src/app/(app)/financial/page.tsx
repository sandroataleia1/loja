import type { Metadata } from 'next'
import { AppPageHeader } from '@/components/shared/app-page-header'
import { AppCard } from '@/components/shared/app-card'
import { EmptyState } from '@/components/states/empty-state'
import { Banknote } from 'lucide-react'

export const metadata: Metadata = { title: 'Financeiro' }

export default function FinancialPage() {
  return (
    <div className="space-y-6">
      <AppPageHeader title="Financeiro" description="Controle financeiro e fluxo de caixa." />
      <AppCard>
        <EmptyState
          icon={<Banknote className="h-8 w-8 text-muted-foreground" />}
          title="Módulo em desenvolvimento"
          description="O módulo financeiro estará disponível em breve."
        />
      </AppCard>
    </div>
  )
}
