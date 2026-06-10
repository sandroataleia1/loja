import { AlertTriangle, AlertCircle, Info, CheckCircle2 } from 'lucide-react'
import Link from 'next/link'
import { cn } from '@/lib/utils'
import type { DashboardAlert } from '../types'

const ALERT_CONFIG = {
  warning: {
    icon:      AlertTriangle,
    classes:   'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30',
    iconClass: 'text-amber-500',
    titleClass:'text-amber-800 dark:text-amber-300',
    textClass: 'text-amber-700 dark:text-amber-400',
  },
  error: {
    icon:      AlertCircle,
    classes:   'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/30',
    iconClass: 'text-red-500',
    titleClass:'text-red-800 dark:text-red-300',
    textClass: 'text-red-700 dark:text-red-400',
  },
  info: {
    icon:      Info,
    classes:   'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/30',
    iconClass: 'text-blue-500',
    titleClass:'text-blue-800 dark:text-blue-300',
    textClass: 'text-blue-700 dark:text-blue-400',
  },
  success: {
    icon:      CheckCircle2,
    classes:   'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30',
    iconClass: 'text-emerald-500',
    titleClass:'text-emerald-800 dark:text-emerald-300',
    textClass: 'text-emerald-700 dark:text-emerald-400',
  },
} as const

interface AlertItemProps {
  alert: DashboardAlert
}

function AlertItem({ alert }: AlertItemProps) {
  const cfg  = ALERT_CONFIG[alert.type]
  const Icon = cfg.icon

  const inner = (
    <div className={cn('flex gap-3 rounded-lg border p-3', cfg.classes, alert.link && 'cursor-pointer hover:opacity-90 transition-opacity')}>
      <Icon className={cn('h-4 w-4 shrink-0 mt-0.5', cfg.iconClass)} />
      <div className="min-w-0">
        <p className={cn('text-xs font-semibold', cfg.titleClass)}>{alert.title}</p>
        <p className={cn('text-xs mt-0.5', cfg.textClass)}>{alert.message}</p>
      </div>
    </div>
  )

  if (alert.link) {
    return <Link href={alert.link}>{inner}</Link>
  }
  return inner
}

interface DashboardAlertCardProps {
  alerts:     DashboardAlert[]
  isLoading?: boolean
}

export function DashboardAlertCard({ alerts, isLoading }: DashboardAlertCardProps) {
  if (isLoading) {
    return (
      <div className="rounded-xl border bg-card p-5 animate-pulse space-y-2">
        <div className="h-5 w-1/3 rounded bg-muted" />
        {Array.from({ length: 3 }).map((_, i) => (
          <div key={i} className="h-14 rounded bg-muted" />
        ))}
      </div>
    )
  }

  return (
    <div className="rounded-xl border bg-card p-5">
      <h3 className="mb-3 text-sm font-semibold">Alertas</h3>
      {alerts.length === 0 ? (
        <p className="text-xs text-muted-foreground">Nenhum alerta no momento.</p>
      ) : (
        <div className="space-y-2">
          {alerts.map((alert) => (
            <AlertItem key={alert.id} alert={alert} />
          ))}
        </div>
      )}
    </div>
  )
}
