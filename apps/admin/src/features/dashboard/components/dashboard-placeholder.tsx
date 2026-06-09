import { LayoutDashboard } from 'lucide-react'
import { AppCard } from '@/components/shared/app-card'
import { AppPageHeader } from '@/components/shared/app-page-header'

export function DashboardPlaceholder() {
  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Dashboard"
        description="Visão geral da sua operação."
      />

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {['Vendas hoje', 'Clientes ativos', 'Produtos', 'Estoque baixo'].map((label) => (
          <AppCard key={label} title={label}>
            <div className="h-8 w-24 bg-muted animate-pulse rounded" />
          </AppCard>
        ))}
      </div>

      <AppCard title="Atividade recente">
        <div className="flex flex-col items-center justify-center gap-3 py-12 text-muted-foreground">
          <LayoutDashboard className="h-10 w-10 opacity-30" />
          <p className="text-sm">Dashboard completo disponível em breve.</p>
        </div>
      </AppCard>
    </div>
  )
}
