import { cn } from '@/lib/utils'

interface DashboardChartCardProps {
  title:     string
  subtitle?: string
  children:  React.ReactNode
  className?: string
  isLoading?: boolean
}

export function DashboardChartCard({ title, subtitle, children, className, isLoading }: DashboardChartCardProps) {
  if (isLoading) {
    return (
      <div className={cn('rounded-xl border bg-card p-5 animate-pulse', className)}>
        <div className="mb-1 h-5 w-1/3 rounded bg-muted" />
        <div className="mb-4 h-3 w-1/2 rounded bg-muted" />
        <div className="h-48 rounded bg-muted" />
      </div>
    )
  }

  return (
    <div className={cn('rounded-xl border bg-card p-5', className)}>
      <div className="mb-4">
        <h3 className="text-sm font-semibold">{title}</h3>
        {subtitle && <p className="text-xs text-muted-foreground mt-0.5">{subtitle}</p>}
      </div>
      {children}
    </div>
  )
}
