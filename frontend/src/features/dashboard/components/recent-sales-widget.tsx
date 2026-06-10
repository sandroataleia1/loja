'use client'

import { cn } from '@/lib/utils'
import { DashboardTableCard } from './dashboard-table-card'
import { useRecentSales } from '../hooks'
import type { DashboardRecentSale } from '../types'

const STATUS_CONFIG = {
  completed: { label: 'Concluído',  classes: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400' },
  pending:   { label: 'Pendente',   classes: 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400'         },
  cancelled: { label: 'Cancelado',  classes: 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400'                 },
} as const

function fmtCurrency(v: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v)
}

function SaleRow({ sale }: { sale: DashboardRecentSale }) {
  const status = STATUS_CONFIG[sale.status]
  return (
    <div className="flex items-center justify-between py-2.5 border-b last:border-0">
      <div className="min-w-0">
        <p className="truncate text-xs font-medium">{sale.customerName}</p>
        <p className="truncate text-xs text-muted-foreground">{sale.channel}</p>
      </div>
      <div className="ml-4 flex shrink-0 flex-col items-end gap-1">
        <span className="text-xs font-semibold">{fmtCurrency(sale.amount)}</span>
        <span className={cn('rounded-full px-1.5 py-0.5 text-[10px] font-medium', status.classes)}>
          {status.label}
        </span>
      </div>
    </div>
  )
}

export function RecentSalesWidget() {
  const { recentSales, isLoading } = useRecentSales()

  return (
    <DashboardTableCard title="Vendas Recentes" subtitle="Últimas transações do dia" isLoading={isLoading}>
      {recentSales.map((sale) => (
        <SaleRow key={sale.id} sale={sale} />
      ))}
    </DashboardTableCard>
  )
}
