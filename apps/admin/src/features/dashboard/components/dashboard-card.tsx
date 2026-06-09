import {
  DollarSign,
  ShoppingCart,
  Receipt,
  Users,
  TrendingUp,
  TrendingDown,
  Minus,
  Package,
} from 'lucide-react'
import { cn } from '@/lib/utils'
import type { DashboardKpi } from '../types'

const ICON_MAP: Record<string, React.ComponentType<{ className?: string }>> = {
  DollarSign,
  ShoppingCart,
  Receipt,
  Users,
  TrendingUp,
  Package,
}

interface DashboardCardProps {
  kpi:       DashboardKpi
  isLoading?: boolean
}

export function DashboardCard({ kpi, isLoading }: DashboardCardProps) {
  if (isLoading) {
    return (
      <div className="rounded-xl border bg-card p-5 animate-pulse">
        <div className="mb-3 h-4 w-1/2 rounded bg-muted" />
        <div className="mb-2 h-8 w-2/3 rounded bg-muted" />
        <div className="h-3 w-1/3 rounded bg-muted" />
      </div>
    )
  }

  const Icon       = ICON_MAP[kpi.icon] ?? Package
  const isPositive = kpi.trend === 'up'
  const isNegative = kpi.trend === 'down'

  return (
    <div className="rounded-xl border bg-card p-5 transition-shadow hover:shadow-md">
      <div className="flex items-start justify-between">
        <p className="text-sm font-medium text-muted-foreground">{kpi.label}</p>
        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
          <Icon className="h-4 w-4 text-primary" />
        </div>
      </div>

      <p className="mt-2 text-2xl font-bold tracking-tight">{kpi.value}</p>

      <div className="mt-1 flex items-center gap-1 text-xs">
        {isPositive && <TrendingUp className="h-3 w-3 text-emerald-500" />}
        {isNegative && <TrendingDown className="h-3 w-3 text-red-500" />}
        {kpi.trend === 'neutral' && <Minus className="h-3 w-3 text-muted-foreground" />}
        <span
          className={cn(
            'font-medium',
            isPositive && 'text-emerald-500',
            isNegative && 'text-red-500',
            kpi.trend === 'neutral' && 'text-muted-foreground',
          )}
        >
          {kpi.change > 0 ? '+' : ''}{kpi.change}%
        </span>
        <span className="text-muted-foreground">{kpi.changePeriod}</span>
      </div>
    </div>
  )
}
