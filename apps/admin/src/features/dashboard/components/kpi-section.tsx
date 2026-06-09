'use client'

import { DashboardCard } from './dashboard-card'
import { useKpis } from '../hooks'

export function KpiSection() {
  const { kpis, isLoading } = useKpis()

  return (
    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6">
      {isLoading
        ? Array.from({ length: 6 }).map((_, i) => (
            <DashboardCard key={i} kpi={{} as never} isLoading />
          ))
        : kpis.map((kpi) => (
            <DashboardCard key={kpi.id} kpi={kpi} />
          ))}
    </div>
  )
}
