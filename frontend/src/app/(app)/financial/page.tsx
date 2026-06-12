'use client'

import Link from 'next/link'
import { TrendingDown, TrendingUp, AlertTriangle, Wallet, Plus, ArrowRight } from 'lucide-react'
import { AppPageHeader }   from '@/components/shared/app-page-header'
import { AppCard }         from '@/components/shared/app-card'
import { Button }          from '@/components/ui/button'
import { Skeleton }        from '@/components/ui/skeleton'
import { EntryStatusBadge } from '@/features/financial/components'
import { useFinancialEntries } from '@/features/financial/hooks'
import { ROUTES }          from '@/constants'

function fmt(cents: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

function fmtDate(iso: string | null) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('pt-BR')
}

function KpiCard({ label, value, icon: Icon, colorClass, isLoading }: {
  label:      string
  value:      string
  icon:       React.ComponentType<{ className?: string }>
  colorClass: string
  isLoading:  boolean
}) {
  return (
    <div className="rounded-lg border bg-card p-5 flex items-center gap-4">
      <div className={`flex h-12 w-12 items-center justify-center rounded-full ${colorClass}`}>
        <Icon className="h-6 w-6" />
      </div>
      <div>
        <p className="text-sm text-muted-foreground">{label}</p>
        {isLoading ? (
          <Skeleton className="mt-1 h-6 w-28" />
        ) : (
          <p className="text-xl font-bold">{value}</p>
        )}
      </div>
    </div>
  )
}

export default function FinancialPage() {
  const { data: payableData,    isLoading: loadingPayable }    = useFinancialEntries({ type: 'expense', status: 'pending', per_page: 100 })
  const { data: receivableData, isLoading: loadingReceivable } = useFinancialEntries({ type: 'income',  status: 'pending', per_page: 100 })
  const { data: overdueData,    isLoading: loadingOverdue }    = useFinancialEntries({ status: 'overdue', per_page: 100 })
  const { data: recentData,     isLoading: loadingRecent }     = useFinancialEntries({ per_page: 5, page: 1 })

  const totalPayable    = (payableData?.data    ?? []).reduce((s, e) => s + e.amount_cents, 0)
  const totalReceivable = (receivableData?.data ?? []).reduce((s, e) => s + e.amount_cents, 0)
  const totalOverdue    = (overdueData?.data    ?? []).reduce((s, e) => s + e.amount_cents, 0)
  const saldo           = totalReceivable - totalPayable

  const isLoading = loadingPayable || loadingReceivable || loadingOverdue

  return (
    <div className="space-y-6">
      <AppPageHeader
        title="Financeiro"
        description="Visão geral do fluxo financeiro."
        actions={
          <div className="flex gap-2">
            <Button asChild>
              <Link href={ROUTES.FINANCIAL_PAYABLE + '/create'}>
                <Plus className="h-4 w-4 mr-1.5" />
                A Pagar
              </Link>
            </Button>
            <Button variant="outline" asChild>
              <Link href={ROUTES.FINANCIAL_RECEIVABLE + '/create'}>
                <Plus className="h-4 w-4 mr-1.5" />
                A Receber
              </Link>
            </Button>
          </div>
        }
      />

      {/* KPIs */}
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard
          label="Total a Receber"
          value={fmt(totalReceivable)}
          icon={TrendingUp}
          colorClass="bg-green-100 text-green-600"
          isLoading={loadingReceivable}
        />
        <KpiCard
          label="Total a Pagar"
          value={fmt(totalPayable)}
          icon={TrendingDown}
          colorClass="bg-red-100 text-red-600"
          isLoading={loadingPayable}
        />
        <KpiCard
          label="Vencidos"
          value={fmt(totalOverdue)}
          icon={AlertTriangle}
          colorClass="bg-orange-100 text-orange-600"
          isLoading={loadingOverdue}
        />
        <KpiCard
          label="Saldo Líquido"
          value={fmt(saldo)}
          icon={Wallet}
          colorClass={saldo >= 0 ? 'bg-blue-100 text-blue-600' : 'bg-red-100 text-red-600'}
          isLoading={isLoading}
        />
      </div>

      {/* Lançamentos recentes */}
      <AppCard
        title="Lançamentos Recentes"
        actions={
          <Button variant="ghost" size="sm" asChild>
            <Link href={ROUTES.FINANCIAL_PAYABLE}>
              Ver todos <ArrowRight className="ml-1 h-3 w-3" />
            </Link>
          </Button>
        }
      >
        {loadingRecent ? (
          <div className="space-y-3">
            {Array.from({ length: 5 }).map((_, i) => (
              <div key={i} className="flex items-center justify-between py-2">
                <Skeleton className="h-4 w-48" />
                <Skeleton className="h-4 w-24" />
              </div>
            ))}
          </div>
        ) : (recentData?.data ?? []).length === 0 ? (
          <p className="text-sm text-muted-foreground py-4 text-center">Nenhum lançamento ainda.</p>
        ) : (
          <div className="divide-y">
            {(recentData?.data ?? []).map((entry) => (
              <div key={entry.uuid} className="flex items-center justify-between py-3">
                <div className="flex items-center gap-3 min-w-0">
                  <div className="min-w-0">
                    <p className="text-sm font-medium truncate">{entry.description ?? 'Sem descrição'}</p>
                    <p className="text-xs text-muted-foreground">
                      Venc. {fmtDate(entry.due_date)} · {entry.category?.name ?? '—'}
                    </p>
                  </div>
                </div>
                <div className="flex items-center gap-3 shrink-0 ml-4">
                  <EntryStatusBadge status={entry.status} />
                  <span className={`text-sm font-semibold ${entry.type === 'income' ? 'text-green-600' : 'text-red-600'}`}>
                    {entry.type === 'income' ? '+' : '-'}{fmt(entry.amount_cents)}
                  </span>
                  <Button variant="ghost" size="sm" asChild>
                    <Link href={`/financial/entries/${entry.uuid}`}>Ver</Link>
                  </Button>
                </div>
              </div>
            ))}
          </div>
        )}
      </AppCard>

      {/* Atalhos */}
      <div className="grid gap-4 sm:grid-cols-3">
        <Link href={ROUTES.FINANCIAL_PAYABLE} className="rounded-lg border bg-card p-4 hover:bg-muted/50 transition-colors">
          <TrendingDown className="h-5 w-5 text-red-500 mb-2" />
          <p className="font-medium text-sm">Contas a Pagar</p>
          <p className="text-xs text-muted-foreground mt-0.5">Gerencie suas despesas</p>
        </Link>
        <Link href={ROUTES.FINANCIAL_RECEIVABLE} className="rounded-lg border bg-card p-4 hover:bg-muted/50 transition-colors">
          <TrendingUp className="h-5 w-5 text-green-500 mb-2" />
          <p className="font-medium text-sm">Contas a Receber</p>
          <p className="text-xs text-muted-foreground mt-0.5">Acompanhe suas receitas</p>
        </Link>
        <Link href={ROUTES.FINANCIAL_TRANSFERS} className="rounded-lg border bg-card p-4 hover:bg-muted/50 transition-colors">
          <Wallet className="h-5 w-5 text-blue-500 mb-2" />
          <p className="font-medium text-sm">Transferências</p>
          <p className="text-xs text-muted-foreground mt-0.5">Movimente entre contas</p>
        </Link>
      </div>
    </div>
  )
}
